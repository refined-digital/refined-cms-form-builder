# Form Builder — Hand-over

`refineddigital/cms-form-builder` @ **v1.13.5** (branch `master`).

A Form Builder module for **RefinedCMS** (a Laravel CMS shipped as composer packages). It is **not a standalone app** — it auto-registers into a host Laravel/RefinedCMS app via Laravel package auto-discovery (`FormBuilderServiceProvider`). PHP 8.2+, depends on `refineddigital/cms`.

Admins build forms in the CMS admin using a **visual drag-and-drop editor** (Vue). Site visitors fill them out on the front end. Submissions run through **integrations** (e.g. payments), then fire **email notifications**, then produce an on-screen/redirect outcome — all configurable per form.

> **History note:** the `overhaul` rewrite (visual editor, integrations, notifications, reCAPTCHA v3) was merged into `master` at v1.13.0 (PR #30). If you read older docs describing a `form_action` 1/2/3 switch, a `callback` class, or a `saveToModel` flow, that is the **pre-1.13** design and no longer how it works. This document describes the current shipped code.

---

## 1. How it fits together

```
Host Laravel app (RefinedCMS)
 └── requires refineddigital/cms-form-builder  (composer)
     └── FormBuilderServiceProvider  (auto-discovered)
          ├── registers admin + front-end routes
          ├── registers view namespace `formBuilder` (3-level override chain)
          ├── force-registers honeypot (msurguy/honeypot)
          ├── publishes/merges config/form-builder.php
          └── loads migrations + seeds field types
```

Two distinct sides:

- **Admin** — CRUD + a Vue visual editor for building forms, managing fields, email notifications, and integrations.
- **Front end** — a single POST submit endpoint; rendering is done in Blade via the `forms()` helper (no GET route).

---

## 2. Routes

Registered by the provider from `src/Module/Http/routes.php` (admin) and `src/Module/Http/public-routes.php` (front end).

### Admin (`form-builder` prefix, inherits admin web/auth middleware)

| Method | URI | Name | Controller |
|---|---|---|---|
| resource | `form-builder` | `form-builder.*` | `FormBuilderController` (index/create/store/edit/update/destroy) |
| GET | `form-builder/{form}/duplicate` | `form-builder.duplicate` | `FormBuilderController@duplicate` |
| GET | `form-builder/{form}/export` | `form-builder.export` | `FormBuilderController@export` (CSV stream) |
| GET | `form-builder/{form}/submissions` | `form-builder.submissions` | `FormBuilderController@submissions` |
| GET | `form-builder/submissions/{token}` | `form-builder.submissions.show` | `FormBuilderController@submissionShow` |

### Admin JSON API (`form-builder/{form}/api`, name prefix `form-builder.api.`)

Backs the Vue editor. `{form}` is route-model-bound.

- Fields: `GET field-types`, `GET fields`, `POST fields`, `POST fields/reorder`, `PUT fields/{field}`, `DELETE fields/{field}`
- Form: `PUT /` (`updateForm`)
- Notifications: `GET|POST notifications`, `POST notifications/reorder`, `PUT|DELETE notifications/{notification}`
- Integrations: `GET integrations`, `PUT integrations/{key}`

All handled by `FormBuilderApiController`.

### Front end

| Method | URI | Name | Controller |
|---|---|---|---|
| POST | `forms/{form}/submit` | `form-builder.submit` | `FormBuilderController@submit` |

---

## 3. Controllers

### `FormBuilderController` (admin + submit)
`src/Module/Http/Controllers/FormBuilderController.php`

- `setup()` / `create()` / `edit()` — wire up the admin edit UI (the Vue editor mounts here).
- `store()` / `update()` / `destroy()` — form CRUD.
- `duplicate($originalId)` — deep-copies a form (delegates to the repo).
- `export(Form $form)` — streams submissions as CSV.
- `submissions(Form $form)` — the visual submissions browser (grouped list).
- `submissionShow($token)` — one grouped submission (form derived from the submission group).
- **`submit(FormSubmitRequest $request, Form $form)`** — the front-end handler. See §5.

### `FormBuilderApiController` (Vue editor backend)
Thin JSON endpoints for fields, notifications, and integrations. Delegates most work to `FormBuilderRepository`. `updateIntegration` does an `updateOrCreate` on `FormIntegration` keyed by `integration_key`.

---

## 4. Data model

`src/Module/Models/`. Both `Form` and `FormField` extend RefinedCMS `CoreModel`, use `SoftDeletes`, and implement Spatie `Sortable` (position-ordered).

```
Form ──hasMany──> FormField ──hasMany──> FormFieldOption   (select/radio/checkbox values)
 │
 ├──hasMany──> FormEmailNotification   (ordered by position)
 └──hasMany──> FormIntegration

FormFieldType   (lookup table — the 23 seeded field types)
```

- **`Form`** — `use Fields` trait (eager-loads `fields` ordered by position via a global scope). Relations `notifications()`, `integrations()`. Behaviour columns: `submit_action` (`message` | `redirect_page` | `redirect_url`), `redirect_page`, `redirect_url`, `confirmation`, plus `recaptcha` on/off toggle. `$formFields` is the declarative admin-UI layout array consumed by the CMS Vue admin.
- **`FormField`** — `use FieldType` trait (see §6). `field_name` is always `field{id}` — that is the HTML input name and the request/DB key, **not** the human label.
- **`FormEmailNotification`** — one configurable email per row: `name`, `to`, `cc`, `bcc`, `reply_to`, `subject`, `content`, `active`, `position`. Recipient fields may contain `field<id>` tokens (swapped for submitted values). See §5.
- **`FormIntegration`** — per-form integration config: `integration_key`, `enabled`, `send_email`, `config` (JSON). Wired to host-registered processors via the core `FormBuilderIntegrationAggregate`. Used by e.g. the Payments package.

---

## 5. Submission flow — `submit()`

`FormBuilderController@submit` (`FormBuilderController.php:266`) → orchestrated by `FormBuilderRepository` (`src/Module/Http/Repositories/FormBuilderRepository.php`).

1. **Validate** — `FormSubmitRequest` builds rules dynamically (see §7). Honeypot rules (`hname`/`htime`) always added; reCAPTCHA v3 rule added when the form has `recaptcha` on.

2. **Run integrations** — `runIntegrations($request, $form)` (`:479`):
   - Resolves each enabled `FormIntegration` against the core `FormBuilderIntegrationAggregate` by `integration_key`, then calls `$processor->process($request, $form, $settings)`.
   - **A failure result (or a thrown exception) aborts the whole submission** — no notifications, no redirect. Returns 422 (JSON) or redirects back with a `form` error. This is how Payments halts on a declined charge.

3. **Send notifications** — unless an enabled integration opted out (`send_email = false`, checked by `shouldSendNotifications()` at `:517`), `compileAndSend()` (`:530`) runs:
   - One `EmailSubmission` row is written **per active notification** (via the core `EmailRepository`), sharing a single `submission_group` UUID so the admin can regroup them into one submission. This is what keeps CSV export reliable, and it is written synchronously even when delivery is queued.
   - Recipients / `reply_to` resolve `field<id>` tokens to submitted values (empty ones dropped).
   - Body supports `[[fields]]` / `{{fields}}` (replaced with the rendered form) and `[[field:id]]` / `[Form Name]` tokens (via `EmailRepository::replaceTokens`). Uploaded files are attached.
   - Delivery is queued when `form-builder.queue_emails` is true **and** `queue.default !== 'sync'`.

4. **Outcome** — dispatch on `$form->submit_action` (default `message`):
   - `redirect_page` → resolves the stored page link and redirects.
   - `redirect_url` → redirects to the literal URL.
   - `message` (default) → redirect back with `complete`/`form` flash (or JSON `{confirmation, id}` when `expectsJson()`).

> **There is no more `form_action` integer switch, `callback` class, or `saveToModel`.** "Callback" behaviour is now an **integration** (a host processor registered on the aggregate); "save to model" is likewise an integration concern. Email is a **notification**, not an action.

---

## 6. Field type system — the core abstraction

Field types are identified by **hardcoded integer IDs** (1–23) seeded in `FieldTypeTableSeeder`. These IDs are load-bearing magic numbers referenced by `==`/`switch`/config throughout.

| ID | Type | ID | Type | ID | Type |
|----|------|----|------|----|------|
| 1 | Text | 9 | Tel | 17 | File |
| 2 | Textarea | 10 | Password | 18 | Multiple Files |
| 3 | Select | 11 | Password w/ Confirmation | 19 | Static |
| 4 | Radio | 12 | Hidden | 20 | Custom |
| 5 | Checkbox | 13 | YesNo Select | 21 | DOB |
| 6 | Single Checkbox | 14 | Country Select | 22 | Group Start |
| 7 | Number | 15 | Date | 23 | Group End |
| 8 | Email | 16 | Date Time | | |

Special-cased IDs: `12` hidden (skips class/required), `11` renders twice (confirmation), `17`/`18` files (→ `enctype=multipart/form-data`), `22`/`23` wrap fields in a `<section>`, `20` custom (host-resolved), `19`/`12` skip required validation (`config('form-builder.skip_validation')`).

### Field class resolution
Each field renders and validates through a `FormField_<Type>` class in `src/Module/Fields/`.

- **Built-in** — resolved from the `config('form-builder.field_classes')` **id → class map** (single source of truth; type 20 intentionally absent).
- **Custom (type 20)** — the field's `custom_field_class` resolves to a host-app class `App\RefinedCMS\Forms\<Name>\FormField_<Name>`.

Each class extends the base `FormField` (`src/Module/Fields/FormField.php`, not abstract). The base does the heavy lifting via hooks:
- Rendering: `inputType()` / `options()` collapse the ~14 near-identical simple inputs/selects (each subclass is ~3 lines); `htmlAttributes()` builds per-type HTML attrs + CSS classes.
- Validation: `rules()` / `messages()` / `extraRules()` (password-confirm synthetic sibling) / `wantsGibberish()` (text/textarea) / `isArrayField()` (multiple-files `.*`).

### render() → Blade → on-disk cache
`render()` returns a **Blade template string** (heredoc, using the CMS `html()` form builder helper). `renderView()` (`FormField.php:186`) compiles that string to a temp on-disk Blade view **cached by `sha1` of the contents** under the compiled-views dir (`FormField.php:223–230`), then renders it with `$field` + `$value` in scope. Changing a `render()` string produces a **new** cached file; stale ones are harmless. Look at `FormField_Text.php` for the minimal pattern.

### `FieldType` trait (on the `FormField` model)
`src/Module/Traits/FieldType.php` appends computed attributes used everywhere (`FieldType.php:37`):
- `field_name` (always `field{id}`), `view` (resolved class/blade), `attributes` (per-type HTML attrs + CSS classes), `options` / `select_options`, `value` (from `old()`), and `label_position` (forced to top for certain types, `:160`).

---

## 7. Validation — `FormSubmitRequest`

Builds rules by looping the form's fields and asking **each field's class** for its rules (`rules()` + `extraRules()`), rather than a big central `switch`. Always appends honeypot rules; appends the reCAPTCHA v3 rule when `recaptcha` is on. Country select uses a `not0` rule (a `Validator::extend` in core — **not** a Rule class). Custom (type 20) fields go through a compat shim translating the legacy `getValidationRules()` shape.

Anti-spam layers, all front + back:
- **Honeypot** (`msurguy/honeypot`, force-registered in the provider) — `hname`/`htime`.
- **reCAPTCHA v3** — score-thresholded (`config('form-builder.recaptcha_threshold')`, default 0.5). On/off per form via `recaptcha`.
- **Gibberish** — text/textarea heuristic (`config('form-builder.gibberish')`, mirrored in `resources/js/front-end/gibberish.js`).
- **Strong password** — opt-in per password field; config `form-builder.password` rules replace `min:5` and drive a live front-end checklist.

---

## 8. Front-end rendering

`forms()` (`src/Module/Helpers/helpers.php:6`) returns a `FormsRepository` (`src/Module/Http/Repositories/FormsRepository.php`). Fluent builder culminating in `render()` (`:48`), which returns the `front-end.form` view.

Typical host usage:
```php
forms()->load($idOrName)->render();
```

Setters (all chainable):
`load`, `setTemplate`, `setTemplateNamespace`, `setButtonText`, `setButtonLoadingText`, `setDefaultFields`, `setAdditionalFields`, `setAdditionalHiddenFields`, `setSelectFieldsOverride($key,$values)`, `setReplacementElement`, `setAttributes`.

Front-end blade lives in `src/Module/Resources/views/front-end/` (`form.blade.php` + `elements/` + `includes/`).

### View override chain
The `formBuilder` namespace is registered with a **3-level override chain** (host wins):
1. `resources/views/forms` (host app)
2. `app_path('RefinedCMS/Forms')` (host app)
3. this package's `src/Module/Resources/views`

**Don't assume this package's blade is what renders in production** — a host may shadow any front-end view.

---

## 9. Config & env

`config/form-builder.php` — key entries:
- `countries` — ISO3 → name list (Country Select).
- `field_classes` — id → renderer/validator class map (see §6). **Keep in sync with the seeder.**
- `skip_validation` — `[19, 12]` (Static, Hidden — required flag ignored).
- `accepted_mime_types`, `date_format`, `datetime_format`, `timezone` (`FORM_BUILDER_TIMEZONE` → `APP_TIMEZONE` → UTC).
- `queue_emails` (default true; only effective when queue is not sync).
- `email.accent_colour` / `email.logo_url` — notification branding, passed through to the core email template.
- `recaptcha_threshold` (0.5), `gibberish.*`, `password.*` (strong-password rules + live checklist).

Env vars: `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`, `RECAPTCHA_SKIP_IP` (IP that bypasses reCAPTCHA), `FORM_BUILDER_TIMEZONE`, `QUEUE_CONNECTION`.

---

## 10. Support directories (`src/Module/`)

- `Fields/` — the `FormField_*` renderer/validator classes + base `FormField`.
- `Http/` — `Controllers/`, `Repositories/` (`FormBuilderRepository`, `FormsRepository`), route files.
- `Models/`, `Traits/` (`Fields`, `FieldType`), `Scopes/` (position/global scopes), `Rules/` (validation rules incl. reCAPTCHA), `Contracts/`, `Enums/`, `Providers/`, `Resources/` (views + assets), `Helpers/`.

---

## 11. Build & release

Assets are compiled by **Vite** (the shipped state — the old Laravel Mix `form.scss` pipeline is superseded):

```bash
npm run dev      # watch admin editor bundle
npm run build    # admin bundle + front-end (form.css, front-end JS)
```

The admin editor bundle shares core's Vue via externals (`window.RefinedCMSVue`); core exposes `registerComponents`/`boot`/`Vue` and mounts on `DOMContentLoaded`.

Release (git tag is the distribution mechanism for composer **and** npm):

```bash
npm run version:patch   # also :minor / :major — bumps, tags, pushes
```

No test suite/linter/CI in this repo (a minimal Pest/testbench smoke suite exists under `tests/` but testbench has no Laravel 13 build yet, so it can't run in the L13 sandbox).

---

## 12. Installation into a host app

Run **from the host**, not from this package:

```bash
php artisan refinedCMS:install-form-builder
```

`src/Commands/Install.php` — `handle()`:
1. `askQuestions()` — prompts for reCAPTCHA **Site Key** and **Secret Key** (both required), and timezone.
2. `migrate()` — runs the package migrations (`src/Database/`).
3. `seed()` — runs `RefinedDigital\FormBuilder\Database\Seeds\DatabaseSeeder` (field types + a default email notification).
4. Symlinks assets; writes `RECAPTCHA_*` to `.env`.

The provider auto-registers the install only when the host DB has no `forms` table.

---

## 13. Gotchas / conventions

- **Never reuse or renumber a field-type ID.** Append new types at the end of the seeder and wire every site: `config('form-builder.field_classes')`, the field class, `skip_validation` if relevant, and any `==`/`in_array` check. Audit with:
  ```
  grep -rn "form_field_type_id ==" src
  grep -rn "in_array.*form_field_type_id" src
  ```
- `field_name` is always `field{id}` — request keys and CSV columns use this, not the label. CSV export reads `$entry->data->data` keyed by `field<id>`.
- Field render strings are Blade-in-heredoc, **content-hash cached on disk** — editing one produces a new cached file; stale ones are harmless.
- Notifications write **one `EmailSubmission` row each**, grouped by a `submission_group` UUID. Don't assume one row == one submission.
- An integration returning a failure **halts everything** (payments rely on this). New integrations must return a truthy `success` (or nothing) on success.
- reCAPTCHA is v3, score-thresholded; `RECAPTCHA_SKIP_IP` bypasses it (useful for local/QA).
- Host apps can shadow any front-end blade via the namespace override chain — verify what actually renders in the host before debugging markup here.

# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`refineddigital/cms-form-builder` — a Form Builder module for **RefinedCMS** (a Laravel CMS distributed as composer packages). It is NOT a standalone app; it auto-registers into a host Laravel/RefinedCMS application via `FormBuilderServiceProvider` (Laravel package auto-discovery). PHP 8.2+, depends on `refineddigital/cms`.

Admins build forms in the CMS admin using a **visual drag-and-drop editor** (Vue). Site visitors fill them out on the front end. Each submission runs the form's enabled **integrations**, then sends its active **email notifications**, then produces an outcome (on-screen confirmation / page / URL redirect). Recipients and email bodies live on per-form notification rows, not on the form itself.

> The visual-editor rewrite (integrations, notifications, reCAPTCHA v3) shipped to `master` at **v1.13.0** (PR #30). If you see references anywhere to a `form_action` 1/2/3 switch, a `callback` class, `saveToModel`, or `FormPaymentTransaction`, that is the pre-1.13 design and is gone. See `docs/HANDOVER.md` for the full current walkthrough.

## Commands

Asset build (Vite — admin editor bundle + front-end CSS/JS; there is no Laravel Mix / `form.scss` pipeline anymore):

```bash
npm run dev        # watch the admin editor bundle (rebuilds front-end once first)
npm run build      # build both admin bundle and front-end (form.css + front-end JS)
npm run prod       # alias of build
```

The admin editor bundle shares core's Vue via externals (`window.RefinedCMSVue`); core exposes `registerComponents`/`boot`/`Vue` and mounts on `DOMContentLoaded`.

Releasing (bumps npm version, tags, pushes — git tag is the distribution mechanism for both composer and npm):

```bash
npm run version:patch   # also minor / major
```

Node is pinned to v24.10.0 (`.nvmrc`). A minimal Pest/testbench smoke suite exists under `tests/`, but testbench has no Laravel 13 build yet so it can't run in the L13 sandbox; there is no linter or CI.

Installation into a host app (run from the host, not here): `php artisan refinedCMS:install-form-builder` — asks for reCaptcha keys (and optional timezone), migrates, seeds field types + a default email notification, symlinks assets, writes `RECAPTCHA_*` to `.env`. The Install command auto-registers only when the host DB has no `forms` table.

## Architecture

### Two sides
- **Admin:** `src/Module/Http/routes.php` registers `Route::resource('form-builder', FormBuilderController)` plus a JSON API under `form-builder/{form}/api/...` (`FormBuilderApiController`) that backs the Vue editor — fields, email notifications, and integrations. `FormBuilderController` extends RefinedCMS's `CoreController`; its `edit()` mounts the visual editor and autosaves through the API. There is **no** `FormFieldsController` and no declarative `$formFields` edit-tab array driving the UI anymore (`Form::$formFields` is empty; `FormField::$formFields` still exists but is superseded by the editor).
- **Front end:** `src/Module/Http/public-routes.php` registers a single POST `forms/{form}/submit` → `FormBuilderController@submit`. Rendering is done in Blade via the `forms()` helper, not a GET route.

### Rendering a form on a site
`forms()` (helper in `src/Module/Helpers/helpers.php`) returns a `FormsRepository`. Typical usage in a host view: `forms()->load($idOrName)->render()`. `FormsRepository` is a fluent builder — `setTemplate`, `setButtonText`, `setDefaultFields`, `setAdditionalFields`/`setAdditionalHiddenFields`, `setSelectFieldsOverride`, `setReplacementElement`, etc. — culminating in `render()`, which returns the `front-end.form` view.

Views resolve through the `formBuilder` namespace, which is registered with a **3-level override chain** (host overrides win):
1. `resources/views/forms` (host app)
2. `app_path('RefinedCMS/Forms')` (host app)
3. this package's `src/Module/Resources/views`

### Field type system — the core abstraction
Each field belongs to a `FormFieldType`. **Field types are identified by hardcoded integer IDs** seeded in `FieldTypeTableSeeder.php`, and those IDs are referenced by `==`/`switch` throughout the code. Know this table:

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

These IDs are load-bearing magic numbers. When adding a field type you must keep the seeder, the `switch`/`==` checks (validation, attributes, scopes), and config in sync. Notable special-cased IDs: `12` (hidden — skips class/required), `11` (renders twice for confirmation), `17`/`18` (file fields → trigger `enctype=multipart/form-data`), `22`/`23` (group start/end → wrap fields in a `<section>`), `20` (custom field class). `config('form-builder.skip_validation')` (`[19,12]`) lists IDs whose required flag is ignored during validation.

**Field class resolution** (`FormsRepository::getFieldClass` / `getFieldClassInstance`): a field renders **and validates** via a PHP class named `FormField_<Type>`. The single source of truth is the id→class map in **`config('form-builder.field_classes')`** (falls back to legacy name-derivation only if unpublished).
- Built-in: `RefinedDigital\FormBuilder\Module\Fields\FormField_<CamelType>` (one file per type in `src/Module/Fields/`).
- Custom (type 20): intentionally absent from the map; the field's `custom_field_class` resolves to `App\RefinedCMS\Forms\<Name>\FormField_<Name>` in the **host app**.

Each `FormField_*` class extends the base `FormField` (`src/Module/Fields/FormField.php`) and implements `render()`, which returns a **Blade template string** (heredoc). `FormField::renderView()` compiles that string to a temporary on-disk Blade view (cached by `sha1` of the contents under the compiled-views dir) and renders it with `$field` + `$value` in scope. Templates use the CMS `html()` form builder helper. Look at `FormField_Text.php` for the minimal pattern.

`FieldType` trait (on the `FormField` model) appends computed attributes used everywhere: `field_name` (always `field{id}` — this is the HTML input name), `view` (resolved field class or blade view), `attributes` (per-type HTML attrs incl. per-type CSS classes), `options`/`select_options`, `value` (from `old()`), `label_position` (forced to top for certain types). The `Fields` trait on the `Form` model eager-loads `fields` (ordered by position) via a global scope.

### Submission flow (`FormBuilderController@submit`)
There is **no `form_action` 1/2/3 switch**. `submit()` orchestrates via `FormBuilderRepository`:
1. `FormSubmitRequest` builds validation rules dynamically by looping the form's fields and asking **each field's class** for its rules (`rules()`/`optionalRules()`/`extraRules()`/`messages()`) — no central type switch. Skips `config('form-builder.skip_validation')` (`[19,12]`) and fields hidden by `ConditionEvaluator`. Always appends honeypot rules (`hname`/`htime` via `msurguy/honeypot`) and, when `$form->recaptcha`, a v3 `ReCaptcha` rule. A field's `error_message` overrides its generated messages.
2. `runIntegrations($request, $form)` — runs each enabled `FormIntegration`'s `process()` (resolved via core's `FormBuilderIntegrationAggregate` by `integration_key`). **A failure result or thrown exception aborts everything** — no notifications, no redirect (422 JSON or redirect-back-with-errors). This is how Payments halts on a declined charge.
3. `shouldSendNotifications($form)` (false if any enabled integration has `send_email = false`) → `compileAndSend($request, $form)`: for each **active `FormEmailNotification`**, resolves recipients/`reply_to` (`field<id>` tokens swap to submitted values), replaces `[[fields]]`/`{{fields}}` with the rendered form, applies `[[field:id]]`/`[Form Name]` tokens, attaches files, and sends via the core `EmailRepository`. **One `EmailSubmission` row is written per notification**, all sharing one `submission_group` UUID (so the admin regroups them into one submission; written synchronously even when delivery is queued via `form-builder.queue_emails`).
4. Outcome from `$form->submit_action` (default `message`): `redirect_page` → resolves the stored page link; `redirect_url` → literal URL; `message` → redirect back with `complete`/`form` flash (or JSON `{confirmation, id}` when `expectsJson()`).

Submissions are stored/exported through the CMS `EmailRepository` (`EmailSubmission` rows keyed by `field<id>`) — `FormBuilderController@export` streams a CSV; `submissions()`/`submissionShow()` power the visual submissions browser (grouped by `submission_group`).

### Data model
`Form` hasMany `FormField` hasMany `FormFieldOption` (select/radio/checkbox values). `Form` also hasMany `FormEmailNotification` (per-form emails: `to`/`cc`/`bcc`/`reply_to`/`subject`/`content`/`active`/`position`) and hasMany `FormIntegration` (`integration_key`/`enabled`/`send_email`/`config`). `FormFieldType` is the lookup table. There is **no `FormPaymentTransaction`** in this package (payments are a separate integration package). Migrations + seeds live in `src/Database/`. `Form`/`FormField`/`FormEmailNotification` extend RefinedCMS `CoreModel`, use `SoftDeletes`, and implement Spatie `Sortable`; `FormIntegration` is a plain model. Legacy `forms` columns (`form_action`, `email_to`, `callback`, `model`, `message`, `receipt*`, …) remain in `$fillable` but are vestigial.

## Conventions specific to this codebase

- **Never reuse or renumber a field-type ID.** Append new types at the end of the seeder and wire up every `switch`/`==` site. Audit with: `grep -rn "form_field_type_id ==" src` and `grep -rn "in_array.*form_field_type_id" src`.
- Field render strings are Blade-in-PHP-heredoc. They are compiled and **cached on disk by content hash** — changing a `render()` string produces a new cached file; stale ones are harmless.
- `field_name` is always `field{id}`; request keys and DB column references use this, not the human field name.
- The package overrides views via namespace, so host apps may shadow any front-end blade. Don't assume this package's blade is what renders in production.
- reCaptcha (v3, score-thresholded via `form-builder.recaptcha_threshold`) and an IP skip are configured through `.env` (`RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`, `RECAPTCHA_SKIP_IP`); `recaptcha` on a form is the on/off toggle.
- Honeypot anti-spam (`msurguy/honeypot`) is force-registered in the service provider and its rules are always added in `FormSubmitRequest`. Text/textarea fields also get a gibberish check (`form-builder.gibberish`); password fields can opt into strong-password rules (`form-builder.password`, front + back).
- **Notifications write one `EmailSubmission` row each**, grouped by a `submission_group` UUID — don't assume one row == one submission.
- **Integrations halt on failure:** a `FormIntegration` processor returning failure (or throwing) aborts the whole submission. New integrations register via core's `FormBuilderIntegrationAggregate`, implement `FormBuilderIntegrationInterface::process()`, and must return truthy `success` (or nothing) on success. `send_email = false` on an enabled integration suppresses notifications.

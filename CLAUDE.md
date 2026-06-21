# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`refineddigital/cms-form-builder` — a Form Builder module for **RefinedCMS** (a Laravel CMS distributed as composer packages). It is NOT a standalone app; it auto-registers into a host Laravel/RefinedCMS application via `FormBuilderServiceProvider` (Laravel package auto-discovery). PHP 8.2+, depends on `refineddigital/cms`.

Admins build forms in the CMS admin; site visitors fill them out on the front end; submissions are emailed, passed to a callback, or saved to a model.

## Commands

Asset build (compiles `resources/sass/form.scss` → `assets/css/form.css` via Laravel Mix; there is no JS build):

```bash
npm run dev        # development build
npm run watch      # watch + rebuild
npm run prod        # production build
```

Releasing (bumps npm version, tags, pushes — git tag is the distribution mechanism for both composer and npm):

```bash
npm run version:patch   # also minor / major
```

Node is pinned to v12.20.2 (`.nvmrc`). There is no test suite, linter, or CI in this repo.

Installation into a host app (run from the host, not here): `php artisan refinedCMS:install-form-builder` — asks for reCaptcha keys, migrates, seeds field types, symlinks assets, writes `RECAPTCHA_*` to `.env`. The Install command auto-registers only when the host DB has no `forms` table.

## Architecture

### Two sides
- **Admin (CRUD):** `routes.php` registers resource controllers under the `form-builder` prefix. `FormBuilderController` manages forms; `FormFieldsController` manages a form's fields (nested resource). Both extend RefinedCMS's `CoreController`; the admin edit UI is driven by the `public $formFields` array on the `Form` / `FormField` models (declarative field layout consumed by the CMS's Vue admin — note the `v-if`/`v-model` strings in those arrays).
- **Front end:** `public-routes.php` registers a single POST `forms/{form}/submit` → `FormBuilderController@submit`. Rendering is done in Blade via the `forms()` helper, not a route.

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

**Field class resolution** (`FormsRepository::getFieldClass`): a field renders via a PHP class named `FormField_<Type>`.
- Built-in: `RefinedDigital\FormBuilder\Module\Fields\FormField_<CamelType>` (one file per type in `src/Module/Fields/`).
- Custom (type 20): the field's `custom_field_class` resolves to `App\RefinedCMS\Forms\<Name>\FormField_<Name>` in the **host app**.

Each `FormField_*` class extends the base `FormField` (`src/Module/Fields/FormField.php`) and implements `render()`, which returns a **Blade template string** (heredoc). `FormField::renderView()` compiles that string to a temporary on-disk Blade view (cached by `sha1` of the contents under the compiled-views dir) and renders it with `$field` + `$value` in scope. Templates use the CMS `html()` form builder helper. Look at `FormField_Text.php` for the minimal pattern.

`FieldType` trait (on the `FormField` model) appends computed attributes used everywhere: `field_name` (always `field{id}` — this is the HTML input name), `view` (resolved field class or blade view), `attributes` (per-type HTML attrs incl. per-type CSS classes), `options`/`select_options`, `value` (from `old()`), `label_position` (forced to top for certain types). The `Fields` trait on the `Form` model eager-loads `fields` (ordered by position) via a global scope.

### Submission flow (`FormBuilderController@submit`)
1. `FormSubmitRequest` builds validation rules dynamically by looping the form's fields and switching on `form_field_type_id` (email, min, confirmed, mimes, country `not0`, custom-class rules). Always appends honeypot rules (`hname`/`htime` via `msurguy/honeypot`) and, if `recaptcha`, a `ReCaptcha` rule.
2. Dispatch on `$form->form_action`:
   - `1` (default) → `FormBuilderRepository::compileAndSend` → builds HTML/plain-text body via the CMS `EmailRepository`, sends, persists submission, optionally sends a receipt to the submitter.
   - `2` → `emailInCallback`: instantiates `$form->callback` (a host class) and calls `->run($request, $form)`; falls back to compileAndSend if no callback.
   - `3` → `saveToModel`: updates the host model named in `$form->model` using fields that have a `merge_field` mapping (`formatWithMergeFields`).
3. Responds with JSON when `expectsJson()`, else redirect-back with `complete`/`form` flash, or to `redirect_page`.

Email templates support `[[fields]]` placeholder (replaced with rendered form fields) and per-field `merge_field` placeholders. Submissions are stored/exported through the CMS `EmailRepository` — `FormBuilderController@export` streams a CSV.

### Data model
`Form` hasMany `FormField` hasMany `FormFieldOption` (for select/radio/checkbox values). `FormFieldType` is the lookup table. `FormPaymentTransaction` exists for payment-enabled forms. Migrations + seeds live in `src/Database/`. Both `Form` and `FormField` extend RefinedCMS `CoreModel`, use `SoftDeletes`, and implement Spatie `Sortable` (position-ordered).

## Conventions specific to this codebase

- **Never reuse or renumber a field-type ID.** Append new types at the end of the seeder and wire up every `switch`/`==` site. Audit with: `grep -rn "form_field_type_id ==" src` and `grep -rn "in_array.*form_field_type_id" src`.
- Field render strings are Blade-in-PHP-heredoc. They are compiled and **cached on disk by content hash** — changing a `render()` string produces a new cached file; stale ones are harmless.
- `field_name` is always `field{id}`; request keys and DB column references use this, not the human field name.
- The package overrides views via namespace, so host apps may shadow any front-end blade. Don't assume this package's blade is what renders in production.
- reCaptcha and an IP skip are configured purely through `.env` (`RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`, `RECAPTCHA_SKIP_IP`); `recaptcha` on a form is the on/off toggle.
- Honeypot anti-spam (`msurguy/honeypot`) is force-registered in the service provider and its rules are always added in `FormSubmitRequest`.

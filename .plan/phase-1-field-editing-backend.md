# Phase 1 — Field Editing Backend (data model + JSON API)

> **This is the first phase being built.** It delivers the server-side foundation the
> visual editor (Phase 2) consumes: the extended field data model and the JSON API for
> creating, updating, reordering and deleting fields. No Vue/UI work here.

## Goal

Extend the `form_fields` data model with the new per-field settings, and expose a clean
JSON API so the editor can manage a form's fields without per-field page loads.

> **Breaking change is allowed** (see [README](README.md)). Reusing the existing columns,
> `settings` JSON, field-type IDs and repository methods below is the *pragmatic default*
> because it's the least work — not a compatibility mandate. If a cleaner schema or a
> rewritten repository/controller serves the new design better, do that and drop the old.
> Preserving existing data is optional.

## What already exists (do not re-add)

`form_fields` already has columns for: `name` (the label), `placeholder`, `required`,
`label_position`, `note`, `note_position`, `custom_class`, `merge_field`, `store_in`,
`hidden_field_value`, `autocomplete`, `show_label`, `data`, and `settings` (longText, cast
as `object`). Field options live in `form_field_options`, synced by
`FormBuilderRepository::syncOptions` (delete-all-then-recreate).

`settings` sub-keys (e.g. `settings[file_types]`) persist automatically via Laravel array
request parsing + the `object` cast — no manual JSON encode/decode.

## 1. Migration — new columns on `form_fields`

New dated migration in `src/Database/Migrations/`:

| Column | Type | Default | Purpose |
|--------|------|---------|---------|
| `default_value` | text, nullable | — | Pre-fill value when no submitted/old value |
| `error_message` | string, nullable | — | Custom validation message; empty = default |
| `include_in_email` | boolean | `1` | Whether the value appears in email notifications |
| `visibility` | string | `'visible'` | `visible` \| `hidden` \| `disabled` \| `readonly` |
| `visibility_rules` | longText/json, nullable | — | Conditional-logic rules (see shape below) |

Plus a per-field **gibberish opt-out** (Phase 9) stored in the existing `settings` JSON
(`settings.gibberish_check`, default on) rather than a dedicated column — applies to
Text/Textarea fields. The field modal Settings tab exposes it.

Update `src/Module/Models/FormField.php`:
- Add the new columns to `$fillable`.
- Add casts: `include_in_email => integer`, `visibility_rules => array`.

### Submit button text — new column on `forms`

The submit button text currently has **no DB column**. `FormsRepository::render()` defaults
`$form->submitText` to `'Submit'` at render time (and it can only be overridden in PHP via
`forms()->setButtonText(...)`). To make it editable + persistent in the visual editor:

- New migration adds `submit_text` (string, nullable) to the `forms` table.
- Add `submit_text` to `Form::$fillable`.
- `FormsRepository::render()` should prefer `$form->submit_text` (falling back to the
  existing `setButtonText()` override, then `'Submit'`). Keep `loadingText` behaviour as-is.
- The `submit.blade.php` view already reads `$form->submitText` — point that at the new
  column via the repository default chain (no view change needed beyond the fallback wiring).

### Label Position "Floating" — clear placeholder on save

Label Position gains a **Floating** option (UI in Phase 2; style in Phase 3) reusing the
legacy `label_position = 2` value. When a field is saved with `label_position = 2` (Floating),
the **placeholder is cleared**. The current `FormBuilderRepository::storeField`/`updateField`
already special-cases this by setting `placeholder = ' '` when `label_position == 2`; change
this to **clear** the placeholder (empty/null) on save for Floating, and apply it in the new
JSON field create/update endpoints too. No schema change (uses existing `placeholder` +
`label_position`).

## 2. Conditions data shape (`visibility_rules`)

```json
{
  "action": "show|hide|enable|disable",
  "logic": "and|or",
  "rules": [
    { "field": <field_id>, "operator": "equals|not_equals|contains|empty|not_empty|checked|unchecked|gt|lt", "value": "..." }
  ]
}
```

Stored this phase; **evaluated** in Phase 4.

## 3. JSON API endpoints

Add admin JSON routes in `src/Module/Http/routes.php`, scoped to a form, e.g. under
`form-builder/{form}/api/...`. All return JSON.

| Method | Path | Action |
|--------|------|--------|
| GET | `fields` | List the form's fields with all settings + options |
| POST | `fields` | Create a field (type defaults applied) |
| PUT | `fields/{id}` | Update a field (all settings + options) |
| DELETE | `fields/{id}` | Soft-delete a field (and its options) |
| POST | `fields/reorder` | Persist new order: array of `{id, position}` |
| GET | `field-types` | Palette metadata: `{id, name, group, icon}` per type |
| PUT | `form` | Save form-level settings (later phases) |

Implementation notes:
- Back these with `FormBuilderRepository`. **Reuse** `storeField`, `updateField`,
  `syncOptions`, `destroyField`. Add a `reorder($positions)` method using the existing
  `position` column / Spatie Sortable.
- Use a dedicated controller (`FormBuilderApiController`) or thin JSON methods on
  `FormFieldsController`. The old HTML field-CRUD screens/routes may be removed — they do
  not need to keep working.
- `field-types` should derive group + icon from a server-side map keyed by the existing
  field-type IDs (see palette mapping in the master README).
- Authorisation/CSRF: these are admin routes — keep them behind the same auth as the
  existing `form-builder` admin routes.

## Critical files

- `src/Database/Migrations/<new>_add_field_settings_to_form_fields.php` (new)
- `src/Module/Models/FormField.php` (`$fillable`, `$casts`)
- `src/Module/Http/routes.php` (new API routes)
- `src/Module/Http/Controllers/FormBuilderApiController.php` (new) or `FormFieldsController.php`
- `src/Module/Http/Repositories/FormBuilderRepository.php` (`reorder`, reuse store/update/sync)
- `src/Database/Seeds/FieldTypeTableSeeder.php` (reference only — group/icon map lives in code)

## Verification

- Run the new migration; confirm columns exist with correct types/defaults.
- Via Tinker/`curl` against a seeded form, exercise each endpoint:
  - create a field → returns it with defaults; row persisted.
  - update a field including `settings[...]`, options, and each new column → round-trips.
  - reorder → positions persisted in order.
  - delete → field + options soft-deleted.
  - `field-types` → returns grouped palette metadata with icons.
- New fields are created with sensible defaults (`visibility='visible'`, `include_in_email=1`).
  (No regression requirement against the old HTML screens — they may be removed.)
- **Tests (establish the suite here):** PHPUnit/Pest feature tests covering each endpoint —
  create/update (incl. new columns + `settings` sub-keys + options sync), reorder, delete,
  `field-types` payload, and the Floating→clear-placeholder rule. This is the package's first
  test suite (none today).

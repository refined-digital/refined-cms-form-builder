# Phase 3 — Front-end Rendering Modernisation + New Settings

> Makes the public form honour the new per-field settings, and consolidates the rendering.

## Goal

Thread the Phase 1 settings (visibility, default value, label/note position, error message,
include-in-email) through public rendering and validation; reduce duplication in the
per-type render classes without renumbering field-type IDs.

## 1. Thread new settings through

- **`visibility`**: `hidden` → render as hidden / omit; `disabled` → add `disabled`;
  `readonly` → add `readonly`; `visible` → normal. Apply in `getAttributesAttribute`.
- **`default_value`**: seed the field value when there's no `old()`/posted value — extend
  `getValueAttribute` in `FieldType.php`.
- **`label_position`** Default/Top/Bottom/**Floating**/Hidden: reconcile with the existing
  0/1/2 + `show_label` logic in `front-end/elements/row.blade.php`. **Floating** = the legacy
  `label_position = 2`: the existing `form__row--floating-label` wrapper class already drives
  this; it's primarily a front-end style (label sits over the input and animates up on
  focus/value). The placeholder is cleared on save (Phase 1), so Floating relies on the label,
  not a placeholder. Ensure the consolidated render keeps emitting `form__row--floating-label`
  and the SCSS in `resources/sass/form.scss` styles it.
- **`note_position`** Above/Below the input.
- **`error_message`**: when set, override the generated `.required` / type-specific messages
  in `FormSubmitRequest`.
- **`include_in_email`**: filter fields when building the email body
  (`EmailRepository::makeHtml/makeText`, `[[fields]]`), and likely the CSV export.

## Export submissions (CSV) — preserve

The existing **Export submissions → CSV download** must keep working through the rewrite
(`{form}/export` → `FormBuilderController@export`, streaming a CSV via core
`EmailRepository::getFormSubmissions` / `formatFields`, reading `email_submissions`). One row
per submission, a column per field. When `include_in_email` filtering is added, decide whether
excluded fields are also omitted from the export (default: keep them in the export — export is
the full record — unless we choose otherwise). See [README](README.md) → "Functionality that
MUST be preserved". If `FormBuilderController@export` is rewritten, re-implement the same
CSV-download behaviour.

## 2. Consolidate render classes

This is a **breaking change** (see [README](README.md)): the `FormField_<Type>` render
classes and the front-end blade may be rewritten or replaced wholesale rather than
extended. Prefer a clean, consolidated rendering layer where every type reads the new
settings uniformly. The integer field-type IDs may be reworked if it produces a cleaner
design — only keep them where it's genuinely simpler, and update every dependent site
(`FieldType.php`, `FormSubmitRequest.php`, `FormBuilderRepository.php`) accordingly.

## Critical files

- `src/Module/Resources/views/front-end/elements/row.blade.php`
- `src/Module/Traits/FieldType.php`
- `src/Module/Http/Requests/FormSubmitRequest.php`
- `src/Module/Http/Repositories/FormBuilderRepository.php` (email/export)
- `src/Module/Fields/*`

## Verification

Render a form exercising each visibility/label/note/default/error setting; submit and
confirm validation messages and email contents respect `error_message` and `include_in_email`.

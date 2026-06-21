# Phase 2 — Visual Editor (Vue) — Fields Tab

> The user-facing editor: canvas + palette + drag reorder + per-field modal. Depends on
> Phase 0 (build/hook) and Phase 1 (data model + API).

## Goal

Replace the separate fields CRUD screen with a single visual editor: live canvas on the
left, field-type palette on the right, drag-to-reorder, and a tabbed per-field settings
modal. Consumes the Phase 1 JSON API.

## 1. Editor screen blade

New override `src/Module/Resources/views/forms/edit.blade.php` rendering the editor mount
point and bootstrapping initial data (form + fields + field-type palette) via `json_encode`
into a prop, plus the CSRF token for API calls.

## 2. Vue components

Under `form-builder/resources/js/components/`, registered as `rd-fb-*`:

- **`FormBuilderEditor.vue`** — top level. Top tabs (Fields / Behaviour /
  Email Notifications / Integrations / Settings). Holds form + fields state; talks to the
  Phase 1 API (axios via `window.axios`). Phase 2 fills the **Fields** tab; **Behaviour** and
  **Email Notifications** are built in [Phase 6](phase-6-behaviour-email-notifications.md);
  **Integrations** is built in [Phase 7](phase-7-integrations.md); **Settings** is built in
  [Phase 8](phase-8-settings-tab.md).
- **`EditorCanvas.vue`** — left preview. `vuedraggable` list of `EditorFieldRow.vue`;
  click a field → open modal; reorder → `POST fields/reorder`. Render Group Start/End
  (22/23) as nested sections like the front end. **Also renders the submit button** at the
  bottom of the canvas (after the fields, not part of the draggable field list) — see below.
- **`FieldPalette.vue`** — right palette, grouped Basic/Option/Advanced from
  `GET field-types`. Click/drag to add a field → `POST fields` with type defaults.
- **`FieldModal.vue`** — tabbed modal (General/Settings/Appearance/Conditions). Follow the
  existing ui-store modal pattern (boolean flag + `body-has-modal` class). On save →
  `PUT fields/{id}`.

Reuse existing core patterns: `vuedraggable`, `window.dragula`, `window.swal` for
confirms, the `FormOptions` pattern for select/radio/checkbox options.

## 3. Field modal tab contents

- **General**: Label (=`name`, required, note "The field that describes the field");
  Placeholder (note "shown if the field doesn't have a value"); Default Value
  (note "Set a default value for the field").
- **Settings**: Required Field (Yes/No → `required`); Error Message (`error_message`,
  note "...Leave empty to use the default message"); Include in Email Notifications
  (Yes/No → `include_in_email`).
- **Appearance**: Visibility (Select Visible/Hidden/Disabled/Readonly → `visibility`);
  Label Position (Select Default/Top/Bottom/**Floating**/Hidden → map to `label_position` +
  `show_label`); Note (Textarea → `note`); Note Position (Select Above/Below the input →
  `note_position`).
  - **Floating** reuses the legacy `label_position = 2` value (existing
    `form__row--floating-label` style). Selecting Floating **clears the placeholder on save**
    (Phase 1 save behaviour). Primarily a front-end style decision (Phase 3).
- **Conditions**: rule builder writing `visibility_rules` (evaluated in Phase 4).
- Type-specific extras (Options for select/radio/checkbox; file settings for 17/18) appear
  contextually within General/Settings.
- **Merge Field** input is added to the modal in [Phase 7](phase-7-integrations.md) — shown
  only when an integration is enabled for the form (maps to `form_fields.merge_field`).

## 3a. Submit button on the canvas

The submit button is shown in the canvas (below the fields, fixed — not draggable, not
removable). It behaves like a field for editing only:

- Clicking it opens a modal (same modal pattern as fields) with a **single tab**.
- The **only** control is **Button Text** (required field). It maps to the new
  `forms.submit_text` column (Phase 1).
- Save → `PUT form` (the form-level settings endpoint), updating `submit_text`.
- The canvas button label reflects the current `submit_text` (default `'Submit'`).

No other submit-button controls are exposed (no loading text, styling, etc.).

## 4. Routing

Point `FormBuilderController@edit` / `FormFieldsController@index` at the new editor screen.
The old per-field CRUD routes/screens may be **removed** (breaking change — no transition
period required).

## Critical files

- `form-builder/resources/js/components/*.vue` (new)
- `form-builder/resources/js/admin.js`
- `src/Module/Resources/views/forms/edit.blade.php` (new override)
- `src/Module/Http/Controllers/FormBuilderController.php`, `FormFieldsController.php`

## Verification

Open a form in admin; add fields from the palette, drag to reorder, edit each modal tab,
save; reload and confirm persistence; confirm the live preview matches the configured fields.

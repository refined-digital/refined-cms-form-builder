# Phase 8 — Settings Tab

> Fills the editor's **Settings** top tab (the last shell from Phase 2). Form-level settings
> that don't belong in Fields/Behaviour/Email Notifications/Integrations.

## Goal

A simple form-level settings panel in the editor, saved via the existing `PUT form`
form-level API (Phase 1).

## Settings

- **Name** — required. Note: "The name of the form". → existing `forms.name` column. This is
  the form's name used throughout (and substituted into the `[Form Name]` subject token in
  Phase 6).
- **Use Recaptcha** — Yes/No toggle, **default false**. Note: "Use Google Recaptcha to
  protect form". → existing `forms.recaptcha` column.

> Both columns already exist. Note: the legacy `forms.recaptcha` default is `1` in the
> original migration — the new spec wants the default **off (false)**. Set the editor/UI
> default to off, and use a migration to change the column default to `0` (and/or default new
> forms to `0` in the model). Recaptcha still also requires `RECAPTCHA_SITE_KEY` /
> `RECAPTCHA_SECRET_KEY` in `.env` to actually engage (unchanged behaviour).

## UI / API

- `SettingsTab.vue` — renders the Name input + Use Recaptcha toggle.
- Save via the Phase 1 `PUT form` endpoint (`name`, `recaptcha`).
- Name is required — validate in the editor and on save.

## Critical files

- `form-builder/resources/js/components/SettingsTab.vue` (new)
- `src/Module/Models/Form.php` (`$fillable`/defaults already cover `name`/`recaptcha`;
  adjust recaptcha default)
- migration to change `forms.recaptcha` default to `0` (optional but matches the spec)
- `FormBuilderController`/repository `PUT form` handler (Phase 1) — accept `name`/`recaptcha`

## Verification

- Open the Settings tab; Name shows the current form name and is required; saving an empty
  name is blocked.
- Use Recaptcha toggle persists; a brand-new form defaults to recaptcha **off**.
- With recaptcha on (and `.env` keys set), the public form enforces recaptcha; with it off,
  it does not.

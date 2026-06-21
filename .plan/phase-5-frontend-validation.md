# Phase 5 — Front-end Live Validation (Zod) + Submit Rewrite

> Replaces the existing core front-end form scripts with our own front-end module that does
> Zod-based live/blur validation and the AJAX submit. **We are NOT using the existing core
> files** `core/resources/js/front-end/plugins/FormValidate.js` and
> `core/resources/js/front-end/modules/FormBuilder.js`.

## Goal

On the public form: validate each field **live (on input)** and **on blur**; mark invalid
fields with a **red border** plus an **inline error message** under the field. Validation
uses **Zod**. The same module owns submission (block on invalid, then AJAX submit), so the
two halves can't disagree on submit-time validation.

## What we are replacing (do not reuse)

- `core/.../front-end/plugins/FormValidate.js` — regex, submit-time only, `alert()` popups,
  toggles `form__row--has-error`.
- `core/.../front-end/modules/FormBuilder.js` — `ApiClient` (FormData → `fetch`, 419 CSRF
  refresh/retry, 422 error collection, recaptcha `grecaptcha.execute`, confirmation HTML
  injection / `data-replacement`). We re-implement the equivalent behaviour ourselves.

> Re-implement the genuinely-needed submit behaviours from the old module: CSRF refresh on
> 419 + retry, recaptcha token flow (**reCAPTCHA v3** `grecaptcha.execute` → `_captcha` field;
> see Phase 9 § E), confirmation injection (`response.confirmation`, `response.url`,
> `data-replacement`), loading state on the submit button. Drop the `alert()` UX entirely.

## Build

- **New Vite front-end entry** in form-builder: `resources/js/front-end/form.js` bundling
  Zod, output to `assets/js/form.js`. (Matches the admin Vite build added in Phase 0; the
  existing laravel-mix SCSS build for `form.css` stays.)
- Enqueue via `front-end/includes/scripts.blade.php` (currently an empty `@section`).
  The form view already guards against double-loading per form id (`loaded_forms` session).

## Validation approach — infer Zod from HTML

Schemas are built **at runtime from each input's HTML** (type + attributes), not from a
server-emitted rule blob. For this to mirror the server rules in `FormSubmitRequest`, the
rendered markup must carry enough signal:

- `required` attribute → Zod required (non-empty).
- input `type` (`email`, `number`, `tel`, `date`, `password`) → matching Zod refinement
  (e.g. email format; number numeric; password `min(5)` to match server `min:5`).
- `minlength` / `maxlength` / `min` / `max` / `pattern` → corresponding Zod constraints.
- `<select>` required → value must not be `0` / `"Please Select"` (matches server `not0`
  for country, id 14).
- Password-with-confirmation (id 11) → cross-field "matches" refinement between the field
  and its `_confirmation` sibling.
- Checkbox/radio groups & single checkbox (ids 4/5/6) → at least one checked / checked.

> **Constraint to verify in Phase 3 rendering:** the front-end render must output these
> attributes on the controls so inference is accurate. Where an attribute can't express a
> rule (e.g. custom-class field rules, mimes), accept that client validation is best-effort
> and the server remains the source of truth (422 still handled — see below).

> **Structure & scope:** the overall form markup (three sections), multi-form isolation, the
> submit-button enable rule, reCAPTCHA **v3**, honeypot and the gibberish filter are specified
> in [Phase 9](phase-9-frontend-form-generation.md). This module is instantiated **once per
> form element** (no page-global state) and implements those behaviours.

## Behaviour

- **On input**: validate the changed field against its Zod schema; toggle red border +
  inline message immediately (debounced lightly if needed); re-evaluate the submit-button
  enable rule (Phase 9 § C).
- **On blur**: validate the field (covers fields the user tabs through).
- **On submit**: validate the whole form; if invalid, block submit, mark all invalid fields,
  focus the first. If valid, run recaptcha (when present) and AJAX submit.
- **Server 422**: map returned field errors back onto the matching inputs as red border +
  inline message (server stays authoritative for rules the client can't express).

## Error display

- Red border on the invalid control (new CSS in `resources/sass/form.scss`).
- Inline message element beneath the field: use the field's configured `error_message`
  (Phase 1/3) when present, else a sensible default per rule. No `alert()`.
- Reuse/repurpose the existing `form__row--has-error` class for the border, add a
  `form__error` message element.

## Critical files

- `form-builder/resources/js/front-end/form.js` (new) + Vite config entry
- `form-builder/resources/sass/form.scss` (red border + `form__error` styles)
- `src/Module/Resources/views/front-end/includes/scripts.blade.php` (enqueue the bundle)
- `src/Module/Resources/views/front-end/elements/row.blade.php` (ensure inputs carry the
  attributes Zod inference needs; error message container) — coordinate with Phase 3
- Reference only (NOT reused): the two core front-end files above

## Verification

- Load a public form: type into a required/email/number field and confirm the border turns
  red live and clears when valid; blur an empty required field and confirm it flags.
- Submit an invalid form: submission is blocked, all invalid fields flagged, first focused.
- Submit a valid form: recaptcha runs (if enabled), AJAX submit succeeds, confirmation
  renders (and `data-replacement` / redirect honoured).
- Force a server 422 (e.g. a rule the client can't express): confirm the error maps back
  onto the field as a red border + message.

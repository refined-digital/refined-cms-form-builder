# Phase 9 — Front-end Form Generation

> The public-facing form markup + submit UX. Tightly coupled with [Phase 3](phase-3-frontend-rendering.md)
> (per-field rendering + settings) and [Phase 5](phase-5-frontend-validation.md) (Zod
> validation + AJAX submit). This phase defines the **overall form structure**, **multi-form
> isolation**, **submit-button enable/loading behaviour**, **invisible reCAPTCHA v3**,
> **honeypot**, and the **gibberish anti-spam filter**. Where this overlaps Phase 3/5, this
> file is the authority on the structure and those phases implement the details.

## Goal

Generate a robust, self-contained public form that: supports **multiple forms on one page**
(fully isolated validation/state/ids), disables the submit button until the form is fillable,
submits via AJAX with a loading state, handles message/redirect outcomes, and resists spam
(honeypot + reCAPTCHA v3 + gibberish filter).

## A. Markup structure — three sections

Each form renders **three distinct sections**, in order:

1. **Hidden fields section** — all hidden inputs. Sources:
   - form fields whose type is Hidden, **and** any field whose `visibility = hidden`
     (Phase 1/3), **and**
   - markup contributed by enabled integrations via the generic **front-end-injection hook**
     (Phase 7) — hidden inputs (e.g. tracking fields) go here; visible UI such as a payment
     card element goes near the submit (see Phase 7 + [Phase 10](phase-10-payments-integration.md)).
   - plus framework hidden inputs (CSRF, honeypot — see D, recaptcha token field — see C).
2. **Visible fields section** — all other fields, **excluding** anything in the hidden
   section. Group Start/End (22/23) render as nested sections here.
3. **Submit section** — the submit button only (text from `forms.submit_text`, Phase 1/2).

This maps onto the existing includes (`front-end/includes/fields.blade.php` already splits
hidden vs fields; `buttons.blade.php`/`submit.blade.php` is the submit section) — restructure
them to these three explicit sections and let integrations contribute hidden inputs.

## B. Uniqueness — multiple forms per page

Every form on a page must be independently valid and non-colliding:

- **Row ids:** `form__field--{id}` (field id) — already the convention; keep it.
- **Field name attribute:** `field{id}` (e.g. `field12`) — keep this. Rationale (user): fields
  that share a human name don't override each other, and it's unique per field row.
- **Form element:** keep the existing `form--{formId}` class; scope all JS by the specific
  form element (the Phase 5 module already selects `form.form--\d+` and instantiates per form).
- The front-end JS (Phase 5) must instantiate **one isolated validator/controller per form
  element** — no shared/global mutable state keyed by anything page-wide. Confirm Zod schemas,
  error state, and the submit handler are per-form instances.

## C. Submit button — enable rule + loading + AJAX + outcome

### Enable/disable rule
Button starts **disabled**. Re-evaluate on every input/change:
- If the form **has required fields** → enable only when **all required fields are valid**.
- If the form has **no required fields** → enable when **at least one field has (valid)
  content**.
- (Invalid content anywhere keeps it disabled — a field with content must be valid to count.)

### Loading + submit
- On click/submit: show the button **loading state** (the existing `submit.blade.php` already
  has `.form-button__loading` markup + spinner — reuse it; toggle `button--loading` + disable).
- **AJAX submit** (no page reload) — the Phase 5 module owns this (FormData → fetch, CSRF 419
  retry, 422 → field errors).

### Outcome handling
- **Show message:** on success, **remove the form from the page** and show the confirmation
  message in its place (honour `data-replacement` if set, else replace the form element).
- **Redirect:** on success, redirect the page to the given page/URL (`response.url`).
- (These outcomes come from the Behaviour tab, Phase 6; the server already returns
  `confirmation` / `url` / `id` for `expectsJson()`.)

## D. Honeypot — always on

Always include the honeypot on every form (as today: `Honeypot::generate('hname','htime')`
in the submit section, validated server-side via the `honeypot`/`honeytime` rules in
`FormSubmitRequest`). Not optional.

## E. reCAPTCHA v3 (invisible, score-based)

Standardise on **reCAPTCHA v3** — fully invisible, no user interaction.

- **Front-end:** load the v3 script with the site key; on submit (after local validation
  passes), call `grecaptcha.execute(siteKey, { action: 'submit' })`, put the token in the
  hidden `_captcha` field, then send. (Phase 5 already scaffolds the token flow via
  `form.dataset.red` + `_captcha`; switch it to v3 execute.)
- **Server:** update `src/Module/Rules/ReCaptcha.php` to verify the v3 response and check
  `success` **and** `score >= threshold` (configurable, e.g. 0.5) **and** matching `action`.
  Add the threshold to config (`config/form-builder.php`).
- Only engages when the form's `recaptcha` is on (Phase 8) and `RECAPTCHA_SITE_KEY` /
  `RECAPTCHA_SECRET_KEY` are set. Update `captcha.blade.php` accordingly.

## F. Gibberish anti-spam filter

A heuristic spam filter on text-like input, **front-end + back-end with the server
authoritative**.

- **Scope:** applies automatically to **single-line Text and Textarea** fields, with a
  **per-field opt-out** (new field setting — see Phase 1 note below). Skips fields where it's
  disabled (e.g. codes/serials that look random).
- **Heuristic (shared definition, implemented both sides):** flag values that look like
  keyboard-mashing — e.g. very low vowel/consonant ratio, long runs of repeated or alternating
  characters, long strings with no spaces/dictionary-like structure. Tune to minimise false
  positives; document the thresholds in one place so front and back match.
- **Front-end:** warn/block on submit (reuse the Phase 5 inline error display).
- **Back-end (authoritative):** re-check in `FormSubmitRequest` (a custom rule, sibling to
  `ReCaptcha.php`, e.g. `Rules/Gibberish.php`); reject with 422 + a field error so it can't be
  bypassed.

### Phase 1 dependency
Add a per-field **gibberish opt-out** setting (e.g. `settings.gibberish_check = false` in the
existing `settings` JSON column, default on). The field modal exposes it (Settings tab of the
modal); rendering emits it so the front-end knows to skip; the server rule reads it.

## Critical files

- `src/Module/Resources/views/front-end/form.blade.php` + `includes/fields.blade.php`,
  `includes/buttons.blade.php`, `includes/submit.blade.php`, `includes/captcha.blade.php` —
  restructure into the three sections; integration-injected hidden inputs.
- `src/Module/Resources/views/front-end/elements/row.blade.php` — ids/names/attrs (Phase 3).
- `form-builder/resources/js/front-end/form.js` (Phase 5) — per-form instance, enable rule,
  loading, AJAX, outcome handling, v3 execute, gibberish front-end check.
- `src/Module/Rules/ReCaptcha.php` (v3 score), `src/Module/Rules/Gibberish.php` (new),
  `src/Module/Http/Requests/FormSubmitRequest.php` (wire both rules + honeypot).
- `config/form-builder.php` (recaptcha threshold; gibberish thresholds).
- Phase 1 migration/model — per-field gibberish opt-out in `settings`.

## Verification

- Two forms on one page: fill/submit each independently; validation, errors and submit state
  never leak between them; ids (`form__field--{id}`) and names (`field{id}`) are unique.
- Submit button: disabled on load; with required fields it enables only when all required are
  valid; with no required fields it enables once any field has valid content.
- Submit: shows loading state, submits via AJAX (no reload); message outcome removes the form
  and shows the message; redirect outcome navigates to the page/URL.
- Honeypot present and rejects bot submissions server-side.
- reCAPTCHA v3: token fetched invisibly on submit; server rejects low-score/!success.
- Gibberish: a keyboard-mashed text value is flagged front-end and rejected server-side;
  a field with the opt-out set is not flagged; legitimate values pass.
- **Tests:** feature tests for the server-side rules — honeypot rejection, reCAPTCHA v3
  (success + score + action), and the `Gibberish` rule (flagged value rejected; opt-out field
  passes; legitimate value passes). Front-end behaviours are manual-verify.

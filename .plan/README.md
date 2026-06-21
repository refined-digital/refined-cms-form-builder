# Form Builder Overhaul — Master Plan

This directory holds the plan for the complete overhaul of the RefinedCMS form-builder
into a **visual, drag-and-drop form editor**.

## Goal

Replace the current two-screen field editing (a separate "edit form" settings page +
a separate per-field CRUD list) with a **single-screen visual editor** modelled on the
reference design ([`form-view.png`](form-view.png)):

- A live **canvas/preview** on the left where fields are dragged to reorder.
- A categorised **field-type palette** on the right (Basic / Option / Advanced).
- Top-level **tabs**: Fields, Behaviour, Email Notifications, Integrations, Settings.
  (No top-level "Appearance" tab — "Appearance" is a tab **inside the per-field modal**, not a
  form-level tab.)
- A per-field **settings modal** with tabs: General / Settings / Appearance / Conditions.
- **Conditional logic** (show/hide/enable/disable a field based on other fields' values).

The forms **list/index** screen is unchanged.

## ⚠️ Breaking change — greenfield licence

**This overhaul is an intentional breaking change.** We are **not** required to reuse,
preserve, or stay compatible with any existing form-builder code, data shapes, routes,
views, or front-end scripts. Where the existing implementation gets in the way, **write
new code** and remove the old.

This explicitly means we are free to:
- Rewrite or delete existing controllers, repositories, models, requests, views and the
  per-type `FormField_*` render classes rather than extending them.
- Change the database schema however suits the new design (new tables/columns, dropped
  columns, restructured `settings`) — a data migration for existing forms is **optional**,
  not a hard requirement.
- Redesign the field-type system, including moving away from the hardcoded integer IDs if
  that produces a cleaner result (earlier "keep the 23 integer IDs" guidance is **superseded**
  by this — preserve them only where it's genuinely simpler, not out of compatibility).
- Replace the existing admin field-CRUD routes/screens outright (no need to keep them
  working "during transition").
- Replace the core front-end form scripts entirely (already planned in Phase 5).

Reuse existing code only when it is genuinely the best option — not for backwards
compatibility. Prefer clarity and the target design over preserving the old behaviour.

## Confirmed architectural decisions

- **Editor JS lives in this package.** form-builder gains its own Vue 3 + Vite build
  (today it only compiles SCSS via laravel-mix).
- **Components register into core's single Vue app** via a new registration hook added
  to the `core` package (we update core in the same effort) — not a second Vue app.
- **Save via a JSON API** — new endpoints for field create/update/reorder/delete and
  form-level settings, replacing the per-field HTML-form POST for the editor.
- **Field-type system is open to redesign** (see breaking-change note above). Keeping the
  existing integer IDs / `settings` JSON column is acceptable where simpler, but not a
  compatibility requirement. Current data does not need to be preserved.
- **Full conditional logic** (editor UI + storage + front-end evaluation).
- **Modernise front-end rendering** while threading the new settings through.

### Functionality that MUST be preserved through the rewrite

Even though this is a breaking change, these existing capabilities are **kept** (re-implemented
if their surrounding code is rewritten):

- **Export submissions → CSV download.** The forms index already exposes an Export action
  (`{form}/export` → `FormBuilderController@export`) that streams a CSV of a form's
  submissions. This must remain: a CSV file download of the submitted data, one row per
  submission, columns per field. Reads stored submissions from the core `email_submissions`
  table via `EmailRepository::getFormSubmissions` / `formatFields`.
- **Use the core CMS email-sending service.** All email sending goes through the core
  module's email service `RefinedDigital\CMS\Modules\Core\Http\Repositories\EmailRepository`
  (`core/src/Modules/Core/Http/Repositories/EmailRepository.php`) — exactly as the current
  form builder does (`compileAndSend` → `EmailRepository::settingsFromForm`/`makeHtml`/
  `makeText`/`send`). Do **not** roll our own mailer; build per-notification `settings`
  objects and hand them to `EmailRepository::send()`. Sending also records each send to
  `email_submissions`, which is what the CSV export reads.
  - **Queueable.** Email notifications can be **queued** (global/config-driven via
    `QUEUE_CONNECTION` + a `form-builder.queue_emails` flag). Implemented by adding a queued
    send path to the **core `EmailRepository`** (still no custom mailer); the `EmailSubmission`
    record is written synchronously at submit time and only the mail delivery is queued, so the
    CSV export stays reliable. See [Phase 6](phase-6-behaviour-email-notifications.md).

## Phases

| # | Phase | Status | File |
|---|-------|--------|------|
| 1 | **Field editing backend** — data model + JSON API (this phase first) | Planned | [phase-1-field-editing-backend.md](phase-1-field-editing-backend.md) |
| 0 | Build & integration foundation (Vue/Vite in form-builder + core hook) | Planned | [phase-0-build-foundation.md](phase-0-build-foundation.md) |
| 2 | Visual editor (Vue) — Fields tab, canvas, palette, field modal | Planned | [phase-2-visual-editor.md](phase-2-visual-editor.md) |
| 3 | Front-end rendering modernisation + new settings | Planned | [phase-3-frontend-rendering.md](phase-3-frontend-rendering.md) |
| 4 | Conditional logic engine (Conditions tab live) | Planned | [phase-4-conditional-logic.md](phase-4-conditional-logic.md) |
| 5 | Front-end live validation (Zod) + submit rewrite | Planned | [phase-5-frontend-validation.md](phase-5-frontend-validation.md) |
| 6 | Behaviour tab + Email Notifications tab (multi-notification + tokens) | Planned | [phase-6-behaviour-email-notifications.md](phase-6-behaviour-email-notifications.md) |
| 7 | Integrations tab (self-registering packages, Enable/Send Email, merge field) | Planned | [phase-7-integrations.md](phase-7-integrations.md) |
| 8 | Settings tab (form Name, Use Recaptcha) | Planned | [phase-8-settings-tab.md](phase-8-settings-tab.md) |
| 9 | Front-end form generation (3-section markup, button enable, multi-form isolation, reCAPTCHA v3, honeypot, gibberish filter) | Planned | [phase-9-frontend-form-generation.md](phase-9-frontend-form-generation.md) |
| 10 | Payments as an installable integration (just-an-integration; no payment code in core) | Planned | [phase-10-payments-integration.md](phase-10-payments-integration.md) |

> **We are starting with Phase 1 (the field-editing backend).** Further changes from the
> user will be folded into the relevant phase files as they are defined.

### Sequencing / dependencies

Numbering is thematic, not strictly chronological. Practical build order & dependencies:

- **Phase 1** (data model + API) — start here; nearly everything depends on it.
- **Phase 0** (build foundation) — needed before any Vue (Phase 2+); can run in parallel with 1.
- **Phase 2** (editor Fields tab) — depends on 0 + 1.
- **Phase 3** (front-end rendering) — depends on 1; pairs with **Phase 9** (front-end structure)
  and **Phase 5** (front-end validation/submit). 3 + 5 + 9 are the public-form trio.
- **Phase 4** (conditional logic) — depends on 1 (storage), 2 (editor UI), 3/9 (render), 5 (JS).
- **Phase 6** (Behaviour + Email) — depends on 1 + 2; rewrites submit/send.
- **Phase 7** (Integrations) — depends on 1 + 2 + 6 (submit-flow gating); adds Merge Field to 2's modal.
- **Phase 8** (Settings tab) — depends on 1 + 2.
- **Phase 10** (Payments) — **just an installable integration** on Phase 7; depends on Phase 7
  delivering two *generic* capabilities (halt-on-failure + front-end markup injection). No
  payment-specific code in form-builder core; the old payment code is removed/moved out.

### Cross-cutting concerns (apply across phases)

- **Form duplication.** `FormBuilderController@duplicate` currently copies a form + its fields
  + options. It must also copy the new per-form children: **email notifications** (Phase 6) and
  **integrations** (Phase 7, incl. payment config Phase 10), plus the new field columns. Update
  duplicate whenever a new child table/column is added.
- **Form/field deletion cascade.** `FormBuilderRepository::destroy` cleans fields + options
  today; extend it to clean **notifications** and **integrations** rows too (soft-delete
  consistent with the rest).
- **Testing.** This is a breaking rewrite — add **PHPUnit/Pest feature tests** for the backend
  surface as each phase lands: the JSON API (Phase 1), submit flow + multi-notification send
  (Phase 6), integration registration/gating (Phase 7), validation rules incl. reCAPTCHA v3 &
  gibberish (Phase 9), payment charge gating (Phase 10). The package has no test suite today;
  establish one. Front-end behaviour stays manual-verify unless a JS test setup is added.

## Reference material

- Reference UI screenshot: [`form-view.png`](form-view.png) (in this `.plan/` folder)
- Codebase guide: [`../CLAUDE.md`](../CLAUDE.md)
- Core package (Vue/Vite admin app): `/Users/matthias/Dev/refined/refinedcms/core`

## Field-type → palette mapping

Existing field-type IDs mapped into the three palette groups (each gets a FontAwesome icon):

- **Basic**: Text(1) `fa-font`, Textarea(2) `fa-align-left`, Email(8) `fa-envelope`,
  Tel(9) `fa-phone`, Number(7) `fa-hashtag`.
- **Option**: Radio(4) `fa-dot-circle`, Checkbox(5) `fa-check-square`,
  Select(3) `fa-caret-square-down`, Single Checkbox/Agree(6) `fa-check`.
- **Advanced**: Date(15)/DateTime(16) `fa-calendar`, File(17)/Multiple Files(18) `fa-upload`,
  Password(10)/w-Confirm(11) `fa-key`, Hidden(12) `fa-eye-slash`, Country(14), YesNo(13),
  DOB(21), Static(19), Custom(20), Group Start/End(22/23).

> "Name", "Signature", "Address", "Calculations", "Payment" from the screenshot are
> **future** palette entries — they would add new field-type IDs and are out of scope for now.

## Global verification

- Build both packages (`form-builder`: new `npm run build`; `core`: `npm run build`), publish assets.
- End-to-end: create a form → configure Settings (name, recaptcha) → add/configure/reorder
  fields in the editor → set conditions → add email notification(s) → set a Behaviour action →
  enable an integration (and payment, if installed) → save → render public form → submit →
  confirm validation, conditional logic, notification email(s), integration processing,
  payment charge, redirect/message outcome, and CSV export all behave.
- Run the backend test suite (added during the rewrite — see Cross-cutting concerns).
- Because this is a breaking change, there is **no requirement** that pre-existing forms or
  data keep working. If we choose to preserve current data, verify migrations and that
  existing forms still render/submit; otherwise document the breaking upgrade steps.

## Out of scope (future)

- New compound field types (Name, Address, Signature, Calculations) — would add new
  field-type IDs. (Payment is now in scope as an integration — Phase 10.)
- A form-level **Appearance** tab (form styling/theme). Not planned; "Appearance" is only a
  field-modal tab.
- Migrating the existing front-end SCSS pipeline to Vite (optional).

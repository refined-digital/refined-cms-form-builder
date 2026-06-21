# Phase 6 — Behaviour Tab + Email Notifications Tab

> Fills in two of the editor's top tabs (shells created in Phase 2): **Behaviour**
> (on-submit action) and **Email Notifications** (a CRUD list of notifications, each edited
> in a modal). Introduces a one-to-many notifications table and a per-field email token
> system. This is a **breaking change** (see [README](README.md)) — the form's single-email
> columns and the old `form_action` (Email-in-Callback / Model) + receipt feature are retired.

## Editor top tabs (final set)

The editor's top tabs are: **Fields** (Phase 2), **Behaviour** + **Email Notifications**
(this phase), **Integrations** ([Phase 7](phase-7-integrations.md)), **Settings**
([Phase 8](phase-8-settings-tab.md)). There is **no** top-level Appearance tab.

---

## A. Behaviour tab — "Action on Submit"

A single setting group:

- **Action on Submit** — note: "When a form submits, I want to". Select with options:
  1. **Display a message** — show the on-screen confirmation message (existing
     `forms.confirmation`, edited here or in Settings).
  2. **Redirect to page** — show the **core content link field** (`<rd-link>` with
     `settings.simple = true`, exactly as `Form.php` currently uses for `redirect_page`).
     Stores the link JSON (`{ "url": ... }`).
  3. **Redirect to URL** — show a new text input labelled **Redirect URL**, validated to
     **start with `https://`** (client validation in the editor + server validation on save).

### Storage
Replace the legacy `form_action` enum (Email/Callback/Model) with a behaviour-focused field:
- `submit_action` (string/enum): `message` | `redirect_page` | `redirect_url`.
- Reuse `redirect_page` (link JSON) for the page option; add `redirect_url` (string,
  nullable) for the URL option, or store both under one column — **recommended:** keep
  `redirect_page` (JSON) for the page link and add `redirect_url` for the plain URL.
- `confirmation` (existing longText) for the message option.

### Submit-flow change (`FormBuilderController::submit`)
Rewrite the action switch around `submit_action` (drop `form_action` 1/2/3):
- Always send the active email notifications (section B) on submit.
- Then branch on `submit_action`: redirect to the link URL / the `https://` URL / fall back
  to the confirmation message. Keep the existing `expectsJson()` responses (`url`,
  `confirmation`, `id`) used by the front-end submit JS.

> Email-in-Callback and Save-to-Model are **removed** this phase. The callback mechanism is
> superseded by the self-registering Integrations system in
> [Phase 7](phase-7-integrations.md). Save-to-Model is out of scope (revisit as an
> integration if needed).

> **Send-gating note (coordinate with Phase 7):** whether the notifications actually send is
> gated by integrations — the active notifications send **unless** an enabled integration has
> its **Send Email** toggle off. See [Phase 7](phase-7-integrations.md) § E.

---

## B. Email Notifications tab — CRUD list + modal

A list of all notifications that will be sent for the form, with add / edit / delete.
Add and Edit open the **same modal**.

### New table: `form_email_notifications`
One-to-many with `forms`:

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `form_id` | unsigned int (FK) | |
| `position` | int | list order (Spatie Sortable) |
| `active` | boolean, default 1 | |
| `name` | string | internal name (required) |
| `to` | text | recipient emails (taggable) |
| `cc` | text, nullable | taggable |
| `bcc` | text, nullable | taggable |
| `reply_to` | string, nullable | an email-type field reference (see below) |
| `subject` | string | required (default token, see below) |
| `content` | longText | rich-text HTML with field tokens |
| timestamps + soft deletes | | |

New model `FormEmailNotification` (Spatie `Sortable`, `SoftDeletes`). `Form hasMany`.

### Modal fields
- **Name** — required. Note: "The internal name of the form". → `name`.
- **Recipient Emails** — required. Note: "Email addresses who will receive this email
  notification". **Taggable** (reuse core `<rd-form-email>`). → `to`.
- **CC** — taggable (same component). Note: "...receive a CC...". → `cc`.
- **BCC** — taggable. Note: "...receive a BCC...". → `bcc`.
- **Reply-To Email** — taggable, but **only lists form fields of email type**; if the form
  has no email field, **hide the Reply-To field entirely**. (Source the email-type fields
  like `FormsRepository::getReplyToOptions` does today.) → `reply_to`.
- **Subject** — required. Note: "The subject of the email notification". Default value:
  `A new submission from '[Form Name]'`. The **`[Form Name]`** token renders as a **chip/tag**
  in the field and is replaced with the form's name at send time. → `subject`.
- **Content** — **Simple Rich Text** (see section C). → `content`.

### CRUD API (extends the Phase 1 JSON API)
Under `form-builder/{form}/api/notifications`:
`GET` (list), `POST` (create), `PUT {id}` (update), `DELETE {id}`, `POST reorder`.
Plus a helper the modal needs: list of email-type fields for the Reply-To picker
(can come from the existing `field-types`/fields payload).

### Send-path change
Refactor `FormBuilderRepository::compileAndSend` to **loop active notifications**. For each,
build the core `EmailRepository` settings object from the notification (`to/cc/bcc/reply_to/
subject/body`) and call `EmailRepository::send($settings)` — the existing send path already
takes a per-`settings` object, so this is a loop, not a rewrite. One `EmailSubmission` row is
stored per notification (preserves the CSV export, which reads `email_submissions`).

> **Use the core email service — do not write a new mailer.** Sending must go through
> `RefinedDigital\CMS\Modules\Core\Http\Repositories\EmailRepository` (in the core package),
> as the current form builder does. We only assemble the per-notification `settings` object
> and call its `send()`. See [README](README.md) → "Functionality that MUST be preserved".

> The submission **data** (`$settings->data`) for the CSV export is the same form request for
> every notification, so export integrity is preserved.

### Queueable notifications

Email notifications can be sent on a **queue** so submission doesn't block on mail delivery.

- **Scope: global / config-driven.** No per-notification or per-form toggle. Queue behaviour
  follows the app's `QUEUE_CONNECTION` plus a package config flag (e.g.
  `config('form-builder.queue_emails')`, default true). With `QUEUE_CONNECTION=sync` it simply
  runs inline, so this is safe by default.
- **Where:** add a **queued send path to the core `EmailRepository`** (not a custom mailer / not
  a form-builder-only job) — e.g. `send($settings, $queue = false)` that uses
  `Mail::to(...)->queue($email)` when queued, else `->send(...)`. The `Notification` mailable
  already uses the `Queueable` trait, so this is a small core change that benefits all callers.
  form-builder passes the config flag when looping notifications.
- **Submission record timing:** the `EmailSubmission` row (which the **CSV export** reads) must
  be created **synchronously at submit time** — queue only the actual mail delivery. So split
  core `send()`: persist the `EmailSubmission` (+ activity log) inline, and queue just the
  `Mail::...->queue()`. This keeps export reliable even before/if the worker runs.
- **Attachments caveat:** queued mail is serialized to the worker, so file-upload attachments
  must resolve from a **persistent path**, not a temp upload path. Either persist uploaded files
  before queueing, or fall back to sending notifications-with-attachments inline. Decide at
  implementation; note it so files don't silently drop.

---

## C. Simple Rich Text with field tokens (new form-builder component)

A **new** simple rich-text Vue component (not core's full `rd-rich-text`), built on tiptap,
used for the notification **Content** (and powering the Subject chip for `[Form Name]`).

- **Toolbar (only):** Bold, Italic, Heading tags, Link / Unlink. (Mirror core's
  `RichText.vue` link modal via the shared `<rd-link-form>` if practical.)
- **Field tokens:** a custom tiptap **inline node** rendered as a **chip**. An insert menu
  lists every form field plus an **"All form fields"** option. Tokens serialise into the
  stored HTML as:
  - per field → `[[field:<id>]]`
  - all fields → `[[fields]]` (the existing bulk placeholder).
- Content is stored as an HTML string containing these placeholders.

### Server-side token replacement (extend core `EmailRepository::makeHtml`)
Today `makeHtml` only replaces `[[fields]]`/`{{fields}}` with the all-fields table. Extend it
(or wrap it in form-builder) to also replace **`[[field:<id>]]`** with that field's submitted
value (via `formatFields` keyed by `field->id`). Subject: replace **`[Form Name]`** with
`$form->name`.

> Since `EmailRepository` is in the core package, prefer extending it there (breaking change
> is allowed) or perform the per-field/`[Form Name]` substitution in form-builder before
> handing the body/subject to `send()`.

---

## Critical files

- New migration `…_create_form_email_notifications_table.php`; drop/retire legacy email +
  `form_action`/receipt columns on `forms` (or leave unused — decide in implementation).
- `src/Module/Models/FormEmailNotification.php` (new), `src/Module/Models/Form.php`
  (`hasMany`, `$fillable` for `submit_action`/`redirect_url`).
- `src/Module/Http/...` — notifications CRUD controller/endpoints + routes; rewrite
  `FormBuilderController::submit`; refactor `FormBuilderRepository::compileAndSend` to loop.
- `core/.../EmailRepository.php` — token replacement (`[[field:<id>]]`, `[Form Name]`); queued
  send path (`send($settings, $queue)`; persist `EmailSubmission` inline, `Mail::queue` the mail).
- `config/form-builder.php` — `queue_emails` flag (default true; effective only when
  `QUEUE_CONNECTION` is not `sync`).
- `form-builder/resources/js/components/` — `BehaviourTab.vue`, `NotificationsTab.vue`,
  `NotificationModal.vue`, `SimpleRichText.vue` (+ tiptap field-token node), reuse
  `<rd-form-email>`, `<rd-link>`, `<rd-link-form>`.

## Verification

- Behaviour: set each of the three actions; submit the public form and confirm message vs
  page-link redirect vs `https://` URL redirect; confirm the URL field rejects non-`https://`.
- Notifications: add multiple notifications, reorder, edit, delete; submit the form and
  confirm **each active notification** sends with its own to/cc/bcc/reply-to/subject/content.
- Tokens: a notification whose content has a per-field chip + an "all fields" chip and whose
  subject uses `[Form Name]` → received email shows the field value, the all-fields table,
  and the form name substituted.
- Reply-To: with no email field on the form the Reply-To control is hidden; with one it lists
  only email-type fields.
- Export: CSV still produces correct rows after multiple notifications send.
- **Duplicate/delete:** duplicating a form copies its notifications; deleting a form removes
  them (see README → Cross-cutting concerns).
- **Tests:** feature tests for the submit flow (each `submit_action` branch), multi-notification
  send (assert one `EmailSubmission` per active notification), and token replacement
  (`[[field:<id>]]`, `[[fields]]`, `[Form Name]`).
- **Queue:** with a real queue driver, submitting dispatches the mail to the queue (assert
  `Mail::queue`/`Queue` faked) while the `EmailSubmission` row exists immediately (export works
  before the worker runs); with `sync` it still sends. Attachments resolve on the worker.

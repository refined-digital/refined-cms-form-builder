# Phase 7 — Integrations Tab

> Fills the editor's **Integrations** top tab (shell from Phase 2). This is the modern,
> discoverable replacement for the legacy `forms.callback` class-path + `form_action == 2`
> ("Email in Callback") mechanism that Phase 6 retired. Installable packages self-register an
> integration that appears in the tab; the form controls each integration's settings.

## Context / why

Forms sometimes need to push submissions to external services (Mailchimp, Zoho, Salesforce,
…). Today that's done by typing a full class path into a `callback` field; the package's
class implements `FormBuilderCallbackInterface::run($request, $form)` and **sends the email
itself** via `compileAndSend`, then does its work using each field's `merge_field` mapping
(see `form-builder-mailchimp/.../Process.php`, `form-builder-zoho`, `form-builder-sales-force`).

The new model: a package **self-registers** an integration (no manual class path); it appears
in the Integrations tab; the form builder **owns email sending** (the per-integration
**Send Email** toggle), and the integration just **processes** the submission.

## A. Discovery — integration aggregate (in core)

Add a singleton aggregate in the **core** package, mirroring `PackageAggregate` /
`ModuleAggregate` (registered in `CMSServiceProvider`):

`FormBuilderIntegrationAggregate` with `register(string $key, array $config)`, `get($key)`,
`all()`, `has($key)`. A package registers from its service provider's `register()`:

```php
app(FormBuilderIntegrationAggregate::class)->register('mailchimp', [
    'name'        => 'Mailchimp',
    'icon'        => '<svg>…</svg>',
    'description' => 'Subscribe submitters to a Mailchimp audience',
    'processor'   => \RefinedDigital\Mailchimp\Module\Classes\Process::class,
    'settings'    => [                       // custom per-form fields (rendered generically)
        ['name' => 'list_id', 'label' => 'Audience / List ID', 'type' => 'text', 'required' => true],
    ],
]);
```

> **No special integration "types".** The same registry + contract serves every integration,
> including **Payments** ([Phase 10](phase-10-payments-integration.md)) — payments is just an
> installable integration package, with **zero** payment-specific code in form-builder core.
> The two generic capabilities payments needs (halt-on-failure and front-end markup injection)
> are part of the generic contract below, available to any integration.

> Aggregate lives in core because the form-builder editor (registered into core's Vue app)
> and the admin API both need it, and core is the shared dependency. Breaking change is
> allowed, so adding a new core aggregate is fine.

## B. New contract

New contract replacing `FormBuilderCallbackInterface`:

```php
interface FormBuilderIntegrationInterface {
    // process the submission; MUST NOT send the notification emails (the form owns that).
    // Return a result that may indicate failure to ABORT the submission (see below),
    // or return null/success to continue. May also throw to abort.
    public function process($request, $form, $settings);   // $settings = per-form integration config (incl. custom fields)
}
```

**Halt-on-failure (generic).** `process()` may return a failure result (e.g. an object/array
with `success = false` + a `message`/field errors) **or throw** to abort the whole submission
with a 422 — no notifications, no other integrations run after it, no redirect. This is a
generic capability (used by Payments to block on a declined charge, but available to any
integration). A null/success return continues the flow.

**Optional front-end injection (generic).** An integration may also contribute markup to the
public form (e.g. payment card UI, or hidden tracking inputs). Define an optional hook the
front-end renderer calls for each enabled integration — e.g. an interface method or an
aggregate-declared `view` — that returns markup placed in the appropriate
[Phase 9](phase-9-frontend-form-generation.md) section (hidden inputs → hidden section; visible
UI like a card element → near the submit). Core stays agnostic about what the markup is.

Keep / update `src/Module/Contracts/` accordingly. The old `FormBuilderCallbackInterface` and
`emailInCallback` are removed.

## C. Per-form storage — `form_integrations` table

One row per (form, integration):

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `form_id` | unsigned int (FK) | |
| `integration_key` | string | matches the aggregate key, e.g. `mailchimp` |
| `enabled` | boolean, default 0 | the **Enable** toggle |
| `send_email` | boolean, default 1 | the **Send Email** toggle |
| `config` | json, nullable | integration-specific custom settings (e.g. `list_id`) |
| timestamps | | |

New model `FormIntegration`; `Form hasMany`. A form only shows/persists rows for integrations
that are currently registered in the aggregate (installed packages).

## D. Integrations tab UI

For each **registered** integration, render a card/row with:
- **Enable** — Yes/No toggle → `enabled`.
- **Send Email** — Yes/No toggle → `send_email`.
- Any **custom settings fields** the package declared (rendered generically: text/select/
  toggle), saved into `config`. Shown when enabled.
- The integration's `name` / `icon` / `description` from the aggregate.

If no integration packages are installed, the tab shows an empty/placeholder state.

### API
Under `form-builder/{form}/api/integrations`:
- `GET` — registered integrations (from the aggregate) merged with this form's saved rows.
- `PUT {key}` — upsert the form's settings for one integration (`enabled`, `send_email`,
  `config`).

## E. Submit-flow integration

In the rewritten `FormBuilderController::submit` / `FormBuilderRepository` (Phase 6):

1. Determine **whether to send notifications**: send the active email notifications **unless**
   any **enabled** integration has `send_email = false`. (User rule: Send Email = no → send no
   notifications at all; Send Email = yes → notifications continue as normal.)
2. **Run each enabled integration**: instantiate its `processor` and call
   `process($request, $form, $config)`. Only enabled integrations run.
3. **Honour halt-on-failure**: if an integration's `process()` returns a failure (or throws),
   **abort** — return 422 to the front end (mapped like other errors), do **not** send
   notifications, do **not** run later integrations, do **not** redirect. (Payments uses this
   for declined charges; see [Phase 10](phase-10-payments-integration.md).)

> Ordering: run integrations that can halt (e.g. payments) **before** sending notifications and
> before applying the Behaviour outcome, so a failure cleanly blocks everything downstream.
> Non-fatal integration errors should be logged via the core activity/log path, not silently
> dropped.

## F. Field modal — "Merge Field"

Add a **Merge Field** input to the field modal (maps to the existing `form_fields.merge_field`
column — a single shared value per field, as today). It is **shown only when ≥1 integration is
enabled** for the form. Integrations read `field->merge_field` exactly as the current Process
classes do (e.g. Mailchimp maps `EMAIL`, `Description`).

> This reuses the existing `merge_field` column and the existing per-field mapping convention,
> so the 3 packages keep working with minimal change.

## G. Update the 3 existing integration packages

`form-builder-mailchimp`, `form-builder-zoho`, `form-builder-sales-force`:
- Register via the aggregate in their service providers (name/icon/description/processor/
  settings fields — e.g. Mailchimp `list_id`).
- Implement `FormBuilderIntegrationInterface::process()`; **remove their `compileAndSend`
  call** (the form now owns email sending).
- Read per-form config (`list_id`, etc.) from `$settings` instead of global config where
  appropriate; keep reading `merge_field` mappings.

## Critical files

- core: `…/Aggregates/FormBuilderIntegrationAggregate.php` (new), `CMSServiceProvider.php`
  (singleton registration).
- form-builder: new migration `…_create_form_integrations_table.php`;
  `src/Module/Models/FormIntegration.php` (new); `Form.php` (`hasMany`);
  `src/Module/Contracts/FormBuilderIntegrationInterface.php` (new, replaces callback contract);
  integrations CRUD controller/endpoints + routes; submit-flow + `compileAndSend` gating
  (coordinate with Phase 6); remove `emailInCallback`/`callback`.
- editor JS: `IntegrationsTab.vue`, `IntegrationCard.vue`; add Merge Field to `FieldModal.vue`
  (visible when an integration is enabled).
- satellite packages: `form-builder-mailchimp`, `form-builder-zoho`,
  `form-builder-sales-force` service providers + Process classes.

## Verification

- With an integration package installed, it appears in the Integrations tab with Enable +
  Send Email + its custom fields; toggling/saving persists to `form_integrations`.
- Uninstalled/unregistered integrations don't appear; empty state when none installed.
- Enable an integration, set Send Email = No → submit: integration runs, **no** notification
  emails sent. Set Send Email = Yes → submit: integration runs **and** notifications send.
- Disabled integration does not run.
- With an integration enabled, the field modal shows Merge Field; values persist to
  `merge_field` and the integration receives them.
- The 3 updated packages process submissions correctly against a live/sandbox service.
- **Duplicate/delete:** duplicating a form copies its `form_integrations` rows; deleting a form
  removes them (README → Cross-cutting concerns).
- **Tests:** feature tests for the aggregate registration/discovery, the integrations API
  upsert, and submit gating (enabled-only runs; Send Email=off suppresses notifications).

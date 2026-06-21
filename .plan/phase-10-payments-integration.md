# Phase 10 — Payments as an Installable Integration

> Payments is **just an integration** built on [Phase 7](phase-7-integrations.md) — an
> installable package that self-registers, with **zero payment-specific code in form-builder
> core**. This deliberately reduces complexity: no special integration "type", no payment
> branches in the submit flow, no payment columns/tables in form-builder.

## Principle

A Payments integration is indistinguishable, from form-builder's perspective, from Mailchimp or
Zoho. It uses only the **generic** Phase 7 capabilities:
- Self-registers via `FormBuilderIntegrationAggregate` (name/icon/description/processor/settings).
- Stores per-form config in `form_integrations.config` (Enable, Send Email, + its own fields
  like gateway, amount source, currency).
- Implements `FormBuilderIntegrationInterface::process()`.
- Uses the generic **halt-on-failure** (declined charge → `process()` returns failure/throws →
  submission aborts with 422; no notifications/redirect).
- Uses the generic **front-end injection** hook to render its card UI on the public form.

If Phase 7's generic contract covers those, **this phase is mostly "build the package," not
"change form-builder."**

## What the payment integration package owns (not form-builder)

Per the decision to keep form-builder payment-agnostic, the payment integration package (new or
the reworked existing gateway packages) owns:
- The gateway wiring (reuse the existing core `PaymentGatewayAggregate` +
  `PaymentGatewayInterface::process()` and the gateway packages' card views — these already
  exist and work).
- **Its own settings** (declared via Phase 7 `settings`): which gateway(s), **amount source**
  (fixed value or a chosen form field), **currency**.
- **Transaction storage:** move `FormPaymentTransaction` + its `form_payment_transactions`
  table/migration **out of form-builder into the payment package** (it's a payment concern).
- The **card UI** markup, rendered via the generic front-end-injection hook, plus any client
  tokenisation (e.g. Stripe.js → hidden token) run before the AJAX submit
  ([Phase 9](phase-9-frontend-form-generation.md)).
- The charge logic in `process()`: resolve gateway + amount + currency, charge, log the
  transaction, and **return failure on decline** so the generic halt-on-failure aborts the
  submit.

## Remove payment code from form-builder core

Strip everything payment-specific from form-builder (breaking change):
- `forms.payment` column + `Form::$fillable` entry.
- `hasPayments` / `setHasPayments` flag and its render plumbing in `FormsRepository`.
- `front-end/includes/payment-gateways.blade.php` (the payment package injects its own UI now).
- `FormPaymentTransaction` model + `2020_05_14_..._create_transactions_table` migration → move
  to the payment package.

## Amount trust (resolve in the package)

If the amount comes from a form field, the package must **not trust a client-sent amount
blindly** — validate/clamp server-side (min/max in the package config) or prefer fixed/config
amounts. This lives entirely in the payment package.

## Dependencies / sequencing

- Depends on **Phase 7** delivering the two generic capabilities (halt-on-failure + front-end
  injection). If those land in Phase 7, this phase adds **no** form-builder core changes beyond
  *removing* the old payment code.
- Note: product-manager currently does payment via the old callback; once callbacks are removed
  (Phase 7) it needs its own update — tracked separately, out of scope here.

## Critical files

- form-builder (removal only): `Form.php` (`payment`), `FormsRepository.php` (`hasPayments`),
  `front-end/includes/payment-gateways.blade.php`, `FormPaymentTransaction.php` +
  `…create_transactions_table.php` (move out).
- payment integration package (new/reworked): service-provider registration via
  `FormBuilderIntegrationAggregate`; a `Process` implementing
  `FormBuilderIntegrationInterface::process()` (charge + halt-on-failure); its settings schema;
  front-end card view via the injection hook; `FormPaymentTransaction` + migration.
- reuse: core `PaymentGatewayAggregate`, `PaymentGatewayInterface`, gateway packages' card views.

## Verification

- Install the payment integration package → it appears in the Integrations tab like any other,
  with Enable/Send Email + gateway/amount/currency settings; nothing payment-specific appears
  when it's **not** installed.
- Successful charge: transaction logged (in the package's table), notifications sent (unless
  Send Email off), Behaviour outcome applied.
- Declined charge: `process()` returns failure → submission aborts (422), no notifications, no
  redirect.
- form-builder core has **no** remaining references to payment/gateway/`hasPayments` (grep).
- **Tests:** in the payment package — charge success continues, decline halts; amount resolution
  (fixed vs field, server-trusted). form-builder's generic halt-on-failure is tested in Phase 7.

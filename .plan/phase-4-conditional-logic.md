# Phase 4 — Conditional Logic Engine (Conditions tab live)

> Makes the `visibility_rules` stored in Phase 1 and edited in Phase 2 actually drive
> show/hide/enable/disable behaviour on the public form.

## Goal

Evaluate each field's conditions on the public form (and enforce them server-side), and
finalise the Conditions tab rule-builder UI.

## 1. Front-end evaluation

- Ship a lightweight `conditions.js` with the public form (add a front-end JS build, or
  inline). On input change, evaluate each field's `visibility_rules` against current values
  and toggle visibility / enabled state.
- Emit the rules from `front-end/elements/row.blade.php` as a `data-*` attribute
  (`json_encode`) for the script to read.
- Support the operators defined in the data shape: `equals`, `not_equals`, `contains`,
  `empty`, `not_empty`, `checked`, `unchecked`, `gt`, `lt`; and `logic` `and`/`or`;
  and `action` `show`/`hide`/`enable`/`disable`.

## 2. Server-side enforcement

In `FormSubmitRequest`, skip validation for fields hidden by their conditions (mirror the
existing `skip_validation` concept) so hidden-but-required fields don't block submission.

## 3. Editor Conditions tab

Finalise the rule-builder UI in `FieldModal.vue`: a field picker limited to other fields in
the same form, with operator + value inputs that adapt to the referenced field's type.

## Data shape (recap)

```json
{
  "action": "show|hide|enable|disable",
  "logic": "and|or",
  "rules": [
    { "field": <field_id>, "operator": "equals|not_equals|contains|empty|not_empty|checked|unchecked|gt|lt", "value": "..." }
  ]
}
```

## Critical files

- `form-builder/resources/js/front-end/conditions.js` (new, + front-end build)
- `src/Module/Resources/views/front-end/elements/row.blade.php`
- `src/Module/Http/Requests/FormSubmitRequest.php`
- `FieldModal.vue` (Conditions panel)

## Verification

Build a form where field B shows only if field A == X; load the public form, toggle A,
confirm B shows/hides, and confirm submit validates correctly in both states.

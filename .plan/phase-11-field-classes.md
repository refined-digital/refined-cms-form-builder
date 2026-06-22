# Form Builder — Field Classes Own Rendering + Validation

> On approval, save this as `form-builder/.plan/phase-11-field-classes.md` (the repo `.plan/` folder
> is the canonical home, per the existing overhaul plan) so it can be tweaked alongside the others.

## Context

The form-builder front end currently spreads each field type's behaviour across **three places**:

1. **Rendering** — 19 `FormField_<Type>` classes in `src/Module/Fields/`, each returning a heredoc
   Blade string. The simple inputs (Text/Email/Tel/Number/Date/DateTime/DOB) are ~95% identical
   (differ only in the input-type string); selects ~85%; checkbox/radio ~90%. On top of that, the
   `FieldType` trait on the **model** has a second per-type `switch` in `getAttributesAttribute`
   (types 4,5,6,7,12,15,17,18) that builds HTML classes/attrs — render logic leaking into the model.
2. **Validation** — `FormSubmitRequest::rules()` is one big `switch ($field->form_field_type_id)`
   (branches for 8 email, 10 min, 11 confirmed+confirmation, 14 not0, 17/18 mimes), plus a separate
   type-20 block. This is the "stupid long-winded switch" the user wants gone.
3. **Class resolution** — `getFieldClassName()` derives the class from the DB `type->name` string
   (`'FormField_'.ucfirst(Str::camel($name))`) — brittle, depends on a seeded string.

**Goal:** each field type = **one class** that owns *both* how it renders *and* its validation rules.
The backend validation builder loops the form's fields and asks each field's class for its rules — no
type-ID switch anywhere. Field-type integer IDs (1–23) stay load-bearing and are never renumbered.

**Decisions (confirmed with user):**
- **Markup stays as heredoc Blade strings** (current sha1-cache mechanism kept). Base class owns the
  actual markup; simple types collapse to ~3-line declarations. Fewest files.
- **Scope = validation onto classes + collapse duplication.** Kill the `FormSubmitRequest` switch,
  collapse the 14 near-identical simple-input classes into the base, move per-type HTML attrs out of
  the model trait into the classes. **Leave** the bespoke renderers (checkbox/radio/file/password)
  and the row/label chrome (`row.blade.php`, `label.blade.php`, `fields.blade.php`, forceToTop/label
  logic) as-is.

**Hard constraint:** the front-end JS (`validation.js`, `conditions.js`, `gibberish.js`) infers
everything from the rendered HTML (`type`, `required`, `minlength`, `data-fb-*`). So the refactored
classes **must emit byte-identical HTML + the same `data-fb-*` attrs** — then no JS changes are
needed. Byte-stability is the acceptance test for the render half.

---

## Design

### 1. Interface + base class (`src/Module/Contracts/FormFieldInterface.php`, `src/Module/Fields/FormField.php`)

Both built-in and custom (type 20) classes converge on one contract so type 20 stops being special.

```php
interface FormFieldInterface {
    public function __construct($field, array $defaultFields = [], array $selectFieldsOverride = []);
    public function renderView(): string;     // rendered HTML
    public function htmlAttributes(): array;   // per-type input attrs/classes (was the model trait switch)
    public function rules(): array;            // Laravel rules for this field's input
    public function messages(): array;         // ['rule' => 'message'], keyed onto field{id}.rule by caller
    public function extraRules(): array;       // synthetic sibling fields, e.g. *_confirmation
    public function wantsGibberish(): bool;    // true only for Text/Textarea
    public function isArrayField(): bool;      // true for Multiple Files (validates as field{id}.*)
}
```

Base class (`FormField.php`) does the heavy lifting. Concrete classes override **one** render hook:

```php
abstract class FormField implements FormFieldInterface {
    // RENDER — concrete classes override inputType() OR options() OR render()
    protected function inputType(): ?string { return null; }   // 'text','email','number',...
    protected function options(): ?array    { return null; }    // selects/radio/checkbox
    protected function render(): string {
        // default: if inputType() set -> heredoc html()->input(type, field_name, value)->attributes(htmlAttributes())
        // selects override options() + render() to use html()->select(...)
    }
    public function renderView(): string { /* existing resolveView + sha1 cache + view()->make, unchanged */ }

    // HTML ATTRS — base provides common (class, id, required, placeholder, autocomplete, visibility);
    // concrete classes array_merge their type-specific bit (radio class, number inputmode, file multiple, etc.)
    public function htmlAttributes(): array { /* the common base attrs */ }

    // VALIDATION — base = none; concrete classes add
    public function rules(): array { return []; }
    public function messages(): array { return []; }
    public function extraRules(): array { return []; }
    public function wantsGibberish(): bool { return false; }
    public function isArrayField(): bool { return false; }
}
```

### 2. Example concrete classes

```php
// Text — gibberish handled form-level, so nearly empty
class FormField_Text extends FormField {
    protected function inputType(): string { return 'text'; }
    public function wantsGibberish(): bool { return true; }
}

// Email — input type + its rule/message
class FormField_Email extends FormField {
    protected function inputType(): string { return 'email'; }
    public function rules(): array { return ['email']; }
    public function messages(): array { return ['email' => 'The '.$this->field->name.' must be a valid email address.']; }
}

// Number — input type + inputmode attr (was switch case 7)
class FormField_Number extends FormField {
    protected function inputType(): string { return 'number'; }
    public function htmlAttributes(): array { return array_merge(parent::htmlAttributes(), ['inputmode' => 'decimal']); }
}

// Select — options source; base select render() does the rest
class FormField_Select extends FormField {
    protected function options(): array {
        return $this->selectFieldsOverride[$this->field->field_name] ?? $this->field->select_options;
    }
}
// YesNo overrides options() -> [1=>'Yes',0=>'No']; Country -> forms()->getCountries()

// Country — not0 rule moves here
class FormField_CountrySelect extends FormField {
    protected function options(): array { return forms()->getCountries(); }
    public function rules(): array { return [new Rules\Not0()]; }   // promoted from bare 'not0' string
    public function messages(): array { return ['not0' => 'The '.$this->field->name.' field is required.']; }
}

// Password (type 10) — min:5
class FormField_Password extends FormField {
    protected function render(): string { /* existing bespoke render kept */ }
    public function rules(): array { return ['min:5']; }
    public function messages(): array { return ['min' => 'The '.$this->field->name.' must be at least :min characters']; }
}

// Password w/ Confirmation (type 11) — cross-field case lives here, not a switch
class FormField_PasswordConfirmation extends FormField_Password {
    public function rules(): array { return ['confirmed', 'min:5']; }
    public function messages(): array {
        return ['confirmed' => 'The '.$this->field->name.' does not match.',
                'min' => 'The '.$this->field->name.' must be at least :min characters'];
    }
    public function extraRules(): array {
        $n = $this->field->field_name.'_confirmation';
        return [$n => ['rules' => ['required','min:5'],
                       'messages' => [$n.'.required' => 'The Confirm '.$this->field->name.' field is required.',
                                      $n.'.min' => 'The Confirm '.$this->field->name.' must be at least :min characters.']]];
    }
}

// Multiple Files (type 18) — array case as a method, not a switch
class FormField_MultipleFiles extends FormField {
    protected function inputType(): string { return 'file'; }
    public function htmlAttributes(): array {
        return array_merge(parent::htmlAttributes(), ['multiple' => 'multiple', 'class' => 'form__control form__control--multiple-files']);
    }
    public function isArrayField(): bool { return true; }
    public function rules(): array { return ['mimes:'.config('form-builder.accepted_mime_types')]; }
    public function messages(): array { return ['mimes' => 'The '.$this->field->name.' is an invalid file type.']; }
}

// Non-inputs (Group Start/End 22/23) — trivial classes so the registry is complete; markup is
// structural and stays in fields.blade.php. Static (19) keeps its nl2br render; already in skip_validation.
class FormField_GroupStart extends FormField {
    public function renderView(): string { return ''; }
}
```

### 3. Registry — ONE id→class map (`config/form-builder.php`)

Replaces the brittle name-derivation. Single source of truth, keyed by the load-bearing IDs:

```php
'field_classes' => [
    1 => Fields\FormField_Text::class,  2 => Fields\FormField_Textarea::class,
    3 => Fields\FormField_Select::class, /* ... */
    11 => Fields\FormField_PasswordConfirmation::class,   // kills the getViewAttribute type-11 special-case
    /* ... */ 22 => Fields\FormField_GroupStart::class, 23 => Fields\FormField_GroupEnd::class,
    // 20 (Custom) omitted — resolved via host-app lookup
],
```

`FormsRepository` gains `getFieldClassInstance($field, $defaultFields=[], $override=[])`:
- type 20 → existing host resolution (`getCustomFieldClassName`), with a **compat shim**: if the host
  class exposes the old `getValidationRules()` (object shape) but not `rules()`, translate it so the
  request loop stays clean and existing host apps don't break.
- otherwise → `config('form-builder.field_classes')[$type] ?? null`, instantiate if `class_exists`.

Keep `getFieldClass()` returning the class *name* as a thin back-compat wrapper routed through the map.

### 4. `FormSubmitRequest::rules()` — the loop that replaces the switch

```php
foreach ($form->fields as $field) {
    if (in_array($field->form_field_type_id, $skip)) continue;       // [19,12], unchanged
    if (ConditionEvaluator::isHidden($field, $data)) continue;        // unchanged

    $instance = forms()->getFieldClassInstance($field);
    if (!$instance) continue;
    $name = $field->field_name;
    $rules = [];

    if ($field->required) {
        $rules[] = 'required';
        $this->customMessages[$name.'.required'] = 'The '.$field->name.' field is required.';
        $rules = array_merge($rules, $instance->rules());
        foreach ($instance->messages() as $rule => $msg) $this->customMessages[$name.'.'.$rule] = $msg;
        foreach ($instance->extraRules() as $extra => $spec) {
            $args[$extra] = $spec['rules'];
            $this->customMessages += $spec['messages'];
        }
    }

    if ($instance->isArrayField() && $field->required) {              // Multiple Files
        $args[$name] = 'required';
        $args[$name.'.*'] = $rules;
    } elseif ($rules) {
        $args[$name] = $rules;
    }

    if ($instance->wantsGibberish()                                  // text/textarea, asks the class
        && (!isset($field->settings->gibberish_check) || $field->settings->gibberish_check !== false)) {
        $args[$name] = array_merge((array)($args[$name] ?? []), [new Gibberish($field->name)]);
    }

    if (!empty($field->error_message)) {                             // blanket override, unchanged
        foreach (array_keys($this->customMessages) as $k)
            if (str_starts_with($k, $name.'.')) $this->customMessages[$k] = $field->error_message;
    }
}

// form-level (NOT field) rules stay here:
$args['hname'] = 'honeypot';
$args['htime'] = 'required|honeytime:5';
if ($form->recaptcha) { $args['_captcha'] = ['required', new ReCaptcha('submit')]; $this->customMessages['_captcha.required'] = 'Robot Detected'; }
```

**Baked-in decisions:** honeypot + recaptcha are form-level (no field), stay in the request.
Gibberish is a form-level *policy applied to certain types* — exposed via `wantsGibberish()` so the
request asks the class instead of `in_array($type,[1,2])`. The settings opt-out / conditional-hidden
checks stay in the request (they read submitted `$data` + field `settings` = request context).
The old type-20 block is **deleted** — type 20 flows through the same `rules()/messages()` path.

### 5. What moves vs. what stays

- **Moves into classes:** `getAttributesAttribute` switch → per-class `htmlAttributes()`; all validation
  rules/messages → per-class `rules()/messages()/extraRules()`; the type-11 view special-case → the map.
- **Stays put (chrome, shared across all types):** `row.blade.php` (label/note/required class/`data-fb-*`),
  `label.blade.php`, `fields.blade.php` (group start/end, password double-render, hidden split),
  `getLabelPositionAttribute`/`getShowLabelAttribute`/forceToTop, `ConditionEvaluator`, the sha1 cache,
  email/export/`include_in_email`. The model's `renderView()` signature is unchanged, so the blades keep
  calling `$field->renderView(...)`.

---

## Critical files

- `src/Module/Contracts/FormFieldInterface.php` — rewrite to the new contract
- `src/Module/Fields/FormField.php` — base: render hooks (`inputType`/`options`/`render`), `htmlAttributes`, `rules`/`messages`/`extraRules`/`wantsGibberish`/`isArrayField`
- `src/Module/Fields/FormField_*.php` — collapse simple inputs to `inputType()`; selects to `options()`; add validation methods to Email/Password/PasswordConfirmation/Country/File/MultipleFiles; leave checkbox/radio/file/password bespoke `render()`
- `src/Module/Http/Requests/FormSubmitRequest.php` — replace the switch (lines ~53–88) + type-20 block (~90–104) with the loop
- `src/Module/Http/Repositories/FormsRepository.php` — add `getFieldClassInstance()` + map resolution + type-20 compat shim; route `getFieldClass`/`getFieldClassName` through the map
- `src/Module/Traits/FieldType.php` — `getAttributesAttribute` delegates to `htmlAttributes()`; drop the type-11 special-case in `getViewAttribute`
- `config/form-builder.php` — add the `field_classes` id→class map
- `src/Module/Rules/Not0.php` (new, if no `Validator::extend('not0')` exists — grep first) — promote the bare `'not0'` string to a Rule class for consistency with Gibberish/ReCaptcha

---

## Sequencing (form renders + validates at every step)

1. Add `field_classes` map + `getFieldClassInstance()` returning the **existing** classes; point
   `getViewAttribute`/`renderView` at the map. No behaviour change.
2. Add the new interface + base hooks with base impls that **reproduce current behaviour**; existing
   subclasses still work (they only override `render()`).
3. Move `getAttributesAttribute` switch → per-class `htmlAttributes()`; model accessor delegates.
   **Diff rendered HTML — must be byte-stable.**
4. Collapse simple inputs (Text/Email/Tel/Number/Date/DateTime/DOB) → `inputType()`; selects → `options()`.
5. Add `rules()/messages()/extraRules()` to Email/Password/PasswordConfirmation/Country/File/MultipleFiles;
   `wantsGibberish()` on Text/Textarea.
6. Rewrite `FormSubmitRequest::rules()` to the loop; delete the switch + type-20 block; add the type-20
   compat shim in `getFieldClassInstance`.
7. Promote `not0` to a Rule class (only if not already a registered extension — grep `not0` first).
8. Delete dead code: name-based `getFieldClassName` for built-ins (keep for custom), type-11 view
   special-case, the `getAttributesAttribute` switch.

---

## Verification (no test suite exists — manual, via DDEV sandbox `~/Dev/refined/sandbox/cms-vue3-test`)

1. **Byte-stable render:** before refactoring, capture rendered HTML of a form containing **every**
   field type (`forms()->load(id)->render()` via tinker). After each render-touching step, diff against
   the capture — must match (classes, ids, `data-fb-*`, multiple/accept/inputmode attrs, hidden's
   unset class+required, password `_confirmation` naming).
2. **Validation parity:** submit the form invalid then valid; confirm each behaves as before —
   email format, password `min:5`, password-confirmation `confirmed` + `field{id}_confirmation`
   required, country `not0`, file/multiple-files `mimes` (array `.*`), gibberish on text/textarea
   (+ per-field opt-out), honeypot, recaptcha (when `form->recaptcha`), `error_message` blanket
   override, conditional-hidden + skip_validation fields skipped.
3. **Type 20 custom field:** a host custom field using the old `getValidationRules()` shape still
   validates (compat shim) and renders.
4. **Front-end untouched:** load the public form, confirm `validation.js`/`conditions.js`/`gibberish.js`
   still gate the submit button and show errors — no JS rebuild needed if HTML is byte-stable.
5. **Add ONE backend smoke test** (the repo has none) asserting `FormSubmitRequest` produces the
   expected rule set for a form with email + required + password-confirmation + country fields — the
   minimal check that the loop replaces the switch faithfully.

---

## Suggestions / things to consider while tweaking

- **`extraRules()` is the one bit of "magic"** (synthetic sibling field for password-confirm). It's the
  cleanest way to keep cross-field validation on the owning class without a switch. If it ever grows
  beyond password-confirm, reconsider — but YAGNI for now.
- **`wantsGibberish()` vs a config list:** could instead keep a `config('form-builder.gibberish_types')`
  list. The hook is more "class owns its behaviour" (the user's stated goal); the config is one fewer
  method. Going with the hook to match intent — easy to swap.
- **Don't over-collapse.** Checkbox/radio/file/password keep their own `render()` — forcing them into
  the base would need conditional branching that re-creates the duplication elsewhere. Only the 14
  truly-identical simple inputs collapse.
- **The byte-stable diff is non-negotiable** — it's the only thing protecting the untouched front-end
  JS contract. Capture the baseline HTML *before* step 3 and diff after every render-touching step.

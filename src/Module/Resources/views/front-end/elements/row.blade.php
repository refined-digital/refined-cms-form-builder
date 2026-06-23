@php
    $fieldClasses = [
        'form__row',
        'form__row--'.$field->id,
    ];
    if ($field->required) {
        $fieldClasses[] = 'form__row--required';
    }
    if ($field->custom_class) {
        $fieldClasses[] = $field->custom_class;
    }
    if (isset($errors) && $errors->has($field->field_name)) {
        $fieldClasses[] = ' form__row--has-error';
    }
    if ($field->show_label && $field->label_position == 2) {
        $fieldClasses[] = 'form__row--floating-label';
    }
    if ($field->form_field_type_id == \RefinedDigital\FormBuilder\Module\Enums\FormFieldType::STATIC->value) {
        $fieldClasses[] = 'form__row--static';
    }

    // conditional logic rules for the front-end engine (Phase 4)
    $conditionAttr = '';
    if (!empty($field->visibility_rules)) {
        $rules = is_string($field->visibility_rules)
            ? $field->visibility_rules
            : json_encode($field->visibility_rules);
        $conditionAttr = ' data-fb-conditions="'.htmlspecialchars($rules, ENT_QUOTES).'"';
    }

    // front-end validation hints (Phase 5/9)
    $dataAttrs = ' data-fb-field="'.$field->field_name.'"';
    if ($field->required) {
        $dataAttrs .= ' data-fb-required="1"';
    }
    if (!empty($field->error_message)) {
        $dataAttrs .= ' data-fb-error="'.htmlspecialchars($field->error_message, ENT_QUOTES).'"';
    }
    // gibberish opt-out flag for Text/Textarea
    if (in_array($field->form_field_type_id, [\RefinedDigital\FormBuilder\Module\Enums\FormFieldType::TEXT->value, \RefinedDigital\FormBuilder\Module\Enums\FormFieldType::TEXTAREA->value])
        && isset($field->settings->gibberish_check) && $field->settings->gibberish_check === false) {
        $dataAttrs .= ' data-fb-gibberish="0"';
    }

    // strong-password rules (types 10/11 that opted in). Emit the active rules
    // so the front-end validates + ticks the live checklist; the same rule set
    // is enforced server-side via PasswordStrength.
    $passwordRules = [];
    if (in_array($field->form_field_type_id, [\RefinedDigital\FormBuilder\Module\Enums\FormFieldType::PASSWORD->value, \RefinedDigital\FormBuilder\Module\Enums\FormFieldType::PASSWORD_CONFIRM->value])
        && !empty($field->settings->strong_password)) {
        $passwordRules = \RefinedDigital\FormBuilder\Module\Support\PasswordRules::active();
        if ($passwordRules) {
            $dataAttrs .= ' data-fb-password-rules="'.htmlspecialchars(json_encode($passwordRules), ENT_QUOTES).'"';
        }
    }
    $showPasswordRules = $passwordRules && !empty($field->settings->show_password_rules);
@endphp
<div class="{{ implode(' ', $fieldClasses) }}"{!! $dataAttrs !!}{!! $conditionAttr !!}{!! $field->required ? ' data-required-label="'.$field->name.'"' : ' '!!}>
  @if ($field->show_label && $field->label_position == 1)
    @include('formBuilder::front-end.elements.label')
  @endif

  @if ($field->note && $field->note_position)
    <div class="form__note">{{ $field->note }}</div>
  @endif

  @if (is_string($field->view) && str_contains($field->view, '::') && view()->exists($field->view))
    @include($field->view)
  @else
    {!! $field->renderView($defaultFields, $selectFieldsOverride) !!}
  @endif

  @if ($showPasswordRules)
    <ul class="form__password-rules" data-fb-password-checklist>
      @foreach ($passwordRules as $rule)
        <li class="form__password-rule" data-fb-rule="{{ $rule['key'] }}">
          <span class="form__password-rule-icon" aria-hidden="true"></span>
          {{ $rule['label'] }}
        </li>
      @endforeach
    </ul>
  @endif

  @if ($field->show_label && ($field->label_position == 0 || $field->label_position == 2))
    @include('formBuilder::front-end.elements.label')
  @endif

  @if ($field->note && !$field->note_position)
    <div class="form__note">{{ $field->note }}</div>
  @endif
</div>


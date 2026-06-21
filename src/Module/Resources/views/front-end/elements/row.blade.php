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
    if ($field->form_field_type_id == 19) {
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
    if (in_array($field->form_field_type_id, [1, 2])
        && isset($field->settings->gibberish_check) && $field->settings->gibberish_check === false) {
        $dataAttrs .= ' data-fb-gibberish="0"';
    }
@endphp
<div class="{{ implode(' ', $fieldClasses) }}"{!! $dataAttrs !!}{!! $conditionAttr !!}{!! $field->required ? ' data-required-label="'.$field->name.'"' : ' '!!}>
  @if ($field->show_label && $field->label_position == 1)
    @include('formBuilder::front-end.elements.label')
  @endif

  @if ($field->note && $field->note_position)
    <div class="form__note">{{ $field->note }}</div>
  @endif

  @if (str_contains('formBuilder::', $field->value) && view()->exists($field->view))
    @include($field->view)
  @else
    {!! $field->renderView($defaultFields, $selectFieldsOverride) !!}
  @endif

  @if ($field->show_label && ($field->label_position == 0 || $field->label_position == 2))
    @include('formBuilder::front-end.elements.label')
  @endif

  @if ($field->note && !$field->note_position)
    <div class="form__note">{{ $field->note }}</div>
  @endif
</div>


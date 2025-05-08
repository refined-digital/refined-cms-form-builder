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
@endphp
<div class="{{ implode(' ', $fieldClasses) }}"{!! $field->required ? ' data-required-label="'.$field->name.'"' : ' '!!}>
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


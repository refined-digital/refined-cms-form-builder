@php
  // todo: move this and don't do this in here
  $value = $field->hidden_field_value ?: $field->data;
  if (isset($form->defaultFields, $form->defaultFields[$field->field_name])) {
      $value = $form->defaultFields[$field->field_name];
  }
  if (isset($form->defaultFields, $form->defaultFields[$field->custom_class])) {
      $value = $form->defaultFields[$field->custom_class];
  }
@endphp

{!!
    html()
        ->input('hidden', $field->field_name)
        ->attributes($field->attributes)
        ->value($value)
!!}

@php
  // todo: move this and don't do this in here
  $value = $field->value;
  if (isset($defaultFields, $defaultFields[$field->field_name])) {
      $value = $defaultFields[$field->field_name];
  }
  if (isset($defaultFields, $defaultFields[$field->custom_class])) {
      $value = $defaultFields[$field->custom_class];
  }
@endphp
{!!
    html()
        ->input('email', $field->field_name, $value)
        ->attributes($field->attributes)
!!}

@php
  // todo: move this and don't do this in here
  $value = null;
  if (isset($defaultFields, $defaultFields[$field->field_name])) {
      $value = $defaultFields[$field->field_name];
  }
@endphp
{!!
    html()
        ->input('number', $field->field_name, $value)
        ->attributes($field->attributes)
!!}

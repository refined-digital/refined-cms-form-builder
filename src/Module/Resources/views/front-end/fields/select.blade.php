@php
  // todo: move this and don't do this in here
  $value = $field->value;
  if (isset($defaultFields, $defaultFields[$field->field_name])) {
      $value = $defaultFields[$field->field_name];
  }
  if (isset($defaultFields, $defaultFields[$field->custom_class])) {
      $value = $defaultFields[$field->custom_class];
  }

  $options = $field->select_options;
  if (isset($selectFieldsOverride[$field->field_name])) {
      $options = $selectFieldsOverride[$field->field_name];
  }
@endphp
{!!
    html()
        ->select($field->field_name, $options, $value)
        ->attributes($field->attributes)
!!}

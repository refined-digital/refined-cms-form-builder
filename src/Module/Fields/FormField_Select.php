<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Select extends FormField {

    public function render()
    {
        return <<<'blade'
@php
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
blade;
    }

}

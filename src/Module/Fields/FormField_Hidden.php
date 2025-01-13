<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Hidden extends FormField {

    public function render()
    {
        return <<<'blade'
@php
  if ($value === '[page]') {
    $value = request()->url();
  }
@endphp
{!!
    html()
        ->input('hidden', $field->field_name, $value)
        ->attributes($field->attributes)
!!}
blade;
    }

}

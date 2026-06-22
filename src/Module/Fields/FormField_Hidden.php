<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Hidden extends FormField {

    public function htmlAttributes(): array
    {
        $args = parent::htmlAttributes();
        unset($args['class'], $args['required']);
        return $args;
    }

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

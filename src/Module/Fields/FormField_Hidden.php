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
  } elseif (is_string($value) && preg_match('/^\[query:([\w.-]+)\]$/', $value, $token)) {
    // filled from the page url, e.g. [query:lot] picks up ?lot=12 from a lot's enquire link
    $query = request()->query($token[1]);
    $value = is_string($query) ? $query : null;
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

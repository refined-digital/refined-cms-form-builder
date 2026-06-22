<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Password extends FormField {

    public function rules(): array
    {
        return ['min:5'];
    }

    public function messages(): array
    {
        return ['min' => 'The '.$this->field->name.' must be at least :min characters'];
    }

    public function render()
    {
        return <<<'blade'
@php
    $attributes = $field->attributes;
    $name = $field->field_name;
    if ($field->name == 'Confirmation') {
        $attributes['id'] .= '-confirmation';
        $name .= '_confirmation';
    }

@endphp
{!!
    html()
        ->password($name)
        ->attributes($attributes)
!!}
blade;
    }

}

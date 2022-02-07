<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Text extends FormField {

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->input('text', $field->field_name, $value)
        ->attributes($field->attributes)
!!}
blade;
    }

}

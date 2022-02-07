<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Textarea extends FormField {

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->textarea($field->field_name, $value)
        ->attributes($field->attributes)
!!}
blade;
    }

}

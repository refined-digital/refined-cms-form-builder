<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_DOB extends FormField {

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->input('date', $field->field_name, $value)
        ->attributes($field->attributes)        
!!}    
blade;
    }

}

<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Email extends FormField {

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->input('email', $field->field_name, $value)
        ->attributes($field->attributes)        
!!}    
blade;
    }

}

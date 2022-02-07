<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Tel extends FormField {

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->input('tel', $field->field_name, $value)
        ->attributes($field->attributes)        
!!}    
blade;
    }

}

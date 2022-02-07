<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_File extends FormField {

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->input('file', $field->field_name, $value)
        ->attributes($field->attributes)        
!!}    
blade;
    }

}

<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_DateTime extends FormField {

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->input('datetime-local', $field->field_name, $value)
        ->attributes($field->attributes)        
!!}    
blade;
    }

}

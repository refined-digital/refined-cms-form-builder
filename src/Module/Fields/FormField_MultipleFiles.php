<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_MultipleFiles extends FormField {

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->input('file', $field->field_name.'[]', $value)
        ->attributes($field->attributes)        
!!}    
blade;
    }

}

<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_YesNoSelect extends FormField {

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->select($field->field_name, [1 => 'Yes', 0 => 'No'], $value)
        ->attributes($field->attributes)               
!!}    
blade;
    }

}

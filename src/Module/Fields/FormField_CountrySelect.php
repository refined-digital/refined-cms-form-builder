<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_CountrySelect extends FormField {

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->select($field->field_name, forms()->getCountries(), $value)
        ->attributes($field->attributes)
!!}    
blade;
    }

}

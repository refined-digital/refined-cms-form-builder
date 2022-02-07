<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Static extends FormField {

    public function render()
    {
        return <<<'blade'
{!! nl2br($field->data) !!}  
blade;
    }

}

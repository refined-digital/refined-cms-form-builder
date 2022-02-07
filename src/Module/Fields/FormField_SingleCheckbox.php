<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_SingleCheckbox extends FormField {

    public function render()
    {
        return <<<'blade'
<div class="form__control--single-checkbox">
    {!!
        html()
            ->checkbox($field->field_name, false, 1)
            ->attribute('id', $field->attributes['id'])
            ->class('form__control--single-checkbox-input')
    !!}
    {!!
        html()
            ->label($field->name, $field->attributes['id'])
            ->class('form__control--single-checkbox-label')
    !!}
</div>   
blade;
    }

}

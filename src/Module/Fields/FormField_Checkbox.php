<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Checkbox extends FormField {

    public function render()
    {
        return <<<'blade'
<div class="form__control--checkboxes">
    @if ($field->select_options)
        @foreach ($field->select_options as $key => $value)
            <div class="form__control--checkbox">
                {!!
                    html()
                        ->checkbox($field->field_name.'[]', (is_array($field->value) && in_array($key, $field->value) ? true : false), $key)
                        ->attribute('id', $field->attributes['id'].'_'.$loop->index)
                        ->class('form__control--checkbox-input')
                !!}
                {!!
                    html()
                        ->label($value, $field->attributes['id'].'_'.$loop->index)
                        ->class('form__control--checkbox-label')
                !!}
            </div>
        @endforeach
    @endif
</div>
blade;
    }

}

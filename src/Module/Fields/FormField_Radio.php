<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Radio extends FormField {

    public function render()
    {
        return <<<'blade'
<div class="form__control--radios">
    @if ($field->select_options)
        @foreach ($field->select_options as $key => $value)
            <div class="form__control--radio">
                {!!
                    html()
                        ->radio($field->field_name, false, $key)
                        ->attribute('id', $field->attributes['id'].'_'.$loop->index)
                        ->class('form__control--radio-input')
                !!}
                {!!
                    html()
                        ->label($value, $field->attributes['id'].'_'.$loop->index)
                        ->class('form__control--radio-label')
                !!}
            </div>
        @endforeach
    @endif
</div>   
blade;
    }

}

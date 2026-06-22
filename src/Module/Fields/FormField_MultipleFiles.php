<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_MultipleFiles extends FormField {

    public function htmlAttributes(): array
    {
        $args = parent::htmlAttributes();
        $args['class'] .= ' form__control--multiple-files';
        $args['multiple'] = 'multiple';
        return $args;
    }

    public function isArrayField(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['mimes:'.config('form-builder.accepted_mime_types')];
    }

    public function messages(): array
    {
        return ['mimes' => 'The '.$this->field->name.' is an invalid file type.'];
    }

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

<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Email extends FormField {

    protected function inputType(): ?string
    {
        return 'email';
    }

    public function rules(): array
    {
        return ['email'];
    }

    public function messages(): array
    {
        return ['email' => 'The '.$this->field->name.' must be a valid email address.'];
    }

}

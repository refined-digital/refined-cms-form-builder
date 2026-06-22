<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

// type 11. renders the same password input as type 10 (the base Password
// render already special-cases the 'Confirmation' second pass), but carries
// the confirmed + sibling-field validation.
class FormField_PasswordConfirmation extends FormField_Password {

    public function rules(): array
    {
        return ['confirmed', 'min:5'];
    }

    public function messages(): array
    {
        return [
            'confirmed' => 'The '.$this->field->name.' fields does not match.',
            'min'       => 'The '.$this->field->name.' must be at least :min characters',
        ];
    }

    public function extraRules(): array
    {
        $name = $this->field->field_name.'_confirmation';
        return [
            $name => [
                'rules' => ['required', 'min:5'],
                'messages' => [
                    $name.'.required' => 'The Confirm '.$this->field->name.' field is required.',
                    $name.'.min'      => 'The Confirm '.$this->field->name.' must be at least :min characters.',
                ],
            ],
        ];
    }

}

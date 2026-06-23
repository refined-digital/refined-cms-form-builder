<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

use RefinedDigital\FormBuilder\Module\Rules\PasswordStrength;

// type 11. renders the same password input as type 10 (the base Password
// render already special-cases the 'Confirmation' second pass), but carries
// the confirmed + sibling-field validation.
class FormField_PasswordConfirmation extends FormField_Password {

    public function rules(): array
    {
        // strong mode swaps min:5 for the config rule set, but keeps 'confirmed'
        if ($this->usesStrongPassword()) {
            return ['confirmed', new PasswordStrength($this->field->name)];
        }

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

        // the confirmation input enforces the same strength (so a weak value
        // typed only in the confirm box is rejected too)
        $confirmRules = $this->usesStrongPassword()
            ? ['required', new PasswordStrength($this->field->name)]
            : ['required', 'min:5'];

        return [
            $name => [
                'rules' => $confirmRules,
                'messages' => [
                    $name.'.required' => 'The Confirm '.$this->field->name.' field is required.',
                    $name.'.min'      => 'The Confirm '.$this->field->name.' must be at least :min characters.',
                ],
            ],
        ];
    }

}

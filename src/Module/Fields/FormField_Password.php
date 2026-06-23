<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

use RefinedDigital\FormBuilder\Module\Rules\PasswordStrength;
use RefinedDigital\FormBuilder\Module\Support\PasswordRules;

class FormField_Password extends FormField {

    /** Whether this field has opted into config-driven strong-password rules. */
    protected function usesStrongPassword(): bool
    {
        return !empty($this->field->settings->strong_password) && PasswordRules::enabled();
    }

    public function rules(): array
    {
        // strong mode replaces the default min:5 with the config rule set
        if ($this->usesStrongPassword()) {
            return [new PasswordStrength($this->field->name)];
        }

        return ['min:5'];
    }

    public function messages(): array
    {
        return ['min' => 'The '.$this->field->name.' must be at least :min characters'];
    }

    public function render()
    {
        return <<<'blade'
@php
    $attributes = $field->attributes;
    $name = $field->field_name;
    if ($field->name == 'Confirmation') {
        $attributes['id'] .= '-confirmation';
        $name .= '_confirmation';
    }

@endphp
{!!
    html()
        ->password($name)
        ->attributes($attributes)
!!}
blade;
    }

}

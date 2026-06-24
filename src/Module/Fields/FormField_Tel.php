<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Tel extends FormField {

    // loose phone format shared by server (regex rule) and browser (pattern attr).
    // digits plus + ( ) - space and optional ext; international numbers vary too
    // much for anything stricter.
    const PATTERN = '[\d\s()+\-]+(?:\s*(?:ext|x|#)\.?\s*\d+)?';

    protected function inputType(): ?string
    {
        return 'tel';
    }

    public function htmlAttributes(): array
    {
        return array_merge(parent::htmlAttributes(), ['pattern' => self::PATTERN]);
    }

    public function rules(): array
    {
        return ['regex:/^'.self::PATTERN.'$/i'];
    }

    public function messages(): array
    {
        return ['regex' => 'The :attribute must be a valid phone number.'];
    }

}

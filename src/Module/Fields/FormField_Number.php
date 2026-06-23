<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Number extends FormField {

    public function htmlAttributes(): array
    {
        $args = parent::htmlAttributes();
        $args['inputmode'] = 'decimal';
        return $args;
    }

    protected function inputType(): ?string
    {
        return 'number';
    }

}

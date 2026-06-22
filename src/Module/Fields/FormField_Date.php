<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Date extends FormField {

    public function htmlAttributes(): array
    {
        $args = parent::htmlAttributes();
        $args['class'] .= ' form__control--date-picker';
        return $args;
    }

    protected function inputType(): ?string
    {
        return 'date';
    }

}

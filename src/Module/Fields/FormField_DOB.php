<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_DOB extends FormField {

    protected function inputType(): ?string
    {
        return 'date';
    }

}

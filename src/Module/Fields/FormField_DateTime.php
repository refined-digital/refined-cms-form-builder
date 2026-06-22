<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_DateTime extends FormField {

    protected function inputType(): ?string
    {
        return 'datetime-local';
    }

}

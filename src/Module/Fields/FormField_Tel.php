<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Tel extends FormField {

    protected function inputType(): ?string
    {
        return 'tel';
    }

}

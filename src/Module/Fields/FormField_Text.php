<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Text extends FormField {

    protected function inputType(): ?string
    {
        return 'text';
    }

    public function wantsGibberish(): bool
    {
        return true;
    }

}

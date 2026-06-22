<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_YesNoSelect extends FormField {

    protected function options(): ?array
    {
        return [1 => 'Yes', 0 => 'No'];
    }

}

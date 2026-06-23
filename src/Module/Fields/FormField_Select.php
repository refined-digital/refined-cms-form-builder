<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_Select extends FormField {

    protected function options(): ?array
    {
        $field = $this->field;
        if (isset($this->selectFieldsOverride[$field->field_name])) {
            return $this->selectFieldsOverride[$field->field_name];
        }
        return $field->select_options;
    }

}

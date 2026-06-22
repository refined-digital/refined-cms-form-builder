<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_CountrySelect extends FormField {

    protected function options(): ?array
    {
        return forms()->getCountries();
    }

    public function rules(): array
    {
        // 'not0' is a validator extension registered by the core CMS provider
        return ['not0'];
    }

    public function messages(): array
    {
        return ['not0' => 'The '.$this->field->name.' field is required.'];
    }

}

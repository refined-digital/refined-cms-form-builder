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

    // not0 is a presence check ("you picked a country") — meaningless on an
    // optional country left at the default, so it doesn't apply when optional.
    public function optionalRules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return ['not0' => 'The '.$this->field->name.' field is required.'];
    }

}

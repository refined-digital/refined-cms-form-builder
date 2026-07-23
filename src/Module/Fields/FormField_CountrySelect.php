<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_CountrySelect extends FormField {

    protected function options(): ?array
    {
        return forms()->getCountries();
    }

    // getCountries() already unshifts its own 'Please Select' (at key 0, hence the
    // not0 rule below), so don't prepend the field's placeholder on top of it
    protected function optionsWithPlaceholder(): ?array
    {
        return $this->options();
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

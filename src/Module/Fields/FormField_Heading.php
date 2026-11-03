<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

// type 24 — a section heading. structural (no input, no value), so it renders
// directly rather than through row.blade.
class FormField_Heading extends FormField {

    public function isStructural(): bool
    {
        return true;
    }

    public function render()
    {
        return <<<'blade'
<h3 class="heading">{{ $field->name }}</h3>
blade;
    }

}

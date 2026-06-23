<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

// type 22 — opens a field group <section> (with an optional heading). it's a
// structural tag, not an input, so it renders directly (not through row.blade).
// the matching </section> comes from FormField_GroupEnd.
class FormField_GroupStart extends FormField {

    public function isStructural(): bool
    {
        return true;
    }

    public function render()
    {
        return <<<'blade'
<section class="form__field-group form__field-group--{{ $field->id }}">
@if ($field->show_label)
    <h4 class="form__field-group-heading">{{ $field->name }}</h4>
@endif
blade;
    }

}

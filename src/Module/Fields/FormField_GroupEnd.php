<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

// type 23 — closes the field group <section> opened by FormField_GroupStart.
class FormField_GroupEnd extends FormField {

    public function isStructural(): bool
    {
        return true;
    }

    public function render()
    {
        return <<<'blade'
</section>
blade;
    }

}

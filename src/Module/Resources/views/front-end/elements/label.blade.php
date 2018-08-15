<?php
    $attributes = $field->attributes;
    if ($field->form_field_type_id == 11 && $field->name == 'Confirmation') {
        $attributes['id'] .= '-confirmation';
    }
    $class = 'form__label';
    $class .= ' form__label--'.($field->label_position ? 'top' : 'bottom');
?>
{{
    html()
        ->label($field->name, $attributes['id'])
        ->class($class)
}}
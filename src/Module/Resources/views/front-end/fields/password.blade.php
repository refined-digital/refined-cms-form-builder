<?php
    $attributes = $field->attributes;
    $name = $field->field_name;
    if ($field->name == 'Confirmation') {
        $attributes['id'] .= '-confirmation';
        $name .= '_confirmation';
    }

?>
{!!
    html()
        ->password($name)
        ->attributes($attributes)
!!}

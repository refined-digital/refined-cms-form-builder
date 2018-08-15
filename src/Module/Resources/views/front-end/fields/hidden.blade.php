{!!
    html()
        ->input('hidden', $field->field_name)
        ->attributes($field->attributes)
        ->value($field->data)
!!}

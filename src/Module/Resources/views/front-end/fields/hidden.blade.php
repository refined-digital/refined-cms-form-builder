{!!
    html()
        ->input('hidden', $field->field_name)
        ->attributes($field->attributes)
        ->value($field->hidden_field_value ?: $field->data)
!!}

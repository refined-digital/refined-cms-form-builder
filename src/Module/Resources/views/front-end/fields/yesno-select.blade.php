{!!
    html()
        ->select($field->field_name, [1 => 'Yes', 0 => 'No'])
        ->attributes($field->attributes)
!!}

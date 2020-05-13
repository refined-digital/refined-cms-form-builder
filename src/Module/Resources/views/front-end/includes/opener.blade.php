{!!
    html()
        ->form('POST', $args->route)
        ->attributes($args->attributes)
        ->attribute('target', '_blank')
        ->open()
!!}

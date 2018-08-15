<?php

use \RefinedDigital\FormBuilder\Module\Http\Repositories\FormsRepository;

if (! function_exists('forms')) {
    function forms()
    {
        return app(FormsRepository::class);
    }
}

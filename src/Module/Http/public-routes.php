<?php

Route::namespace('FormBuilder\Module\Http\Controllers')
    ->group(function() {

        Route::post('forms/{form}/submit', [
            'as' => 'form-builder.submit',
            'uses' => 'FormBuilderController@submit'
        ]);

    })
;
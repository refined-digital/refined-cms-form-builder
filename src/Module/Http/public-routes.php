<?php

Route::namespace('FormBuilder\Module\Http\Controllers')
    ->group(function() {

        Route::get('forms/token', [
            'as' => 'form-builder.token',
            'uses' => 'FormBuilderController@token'
        ]);

        Route::post('forms/{form}/submit', [
            'as' => 'form-builder.submit',
            'uses' => 'FormBuilderController@submit'
        ]);

    })
;
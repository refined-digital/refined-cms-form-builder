<?php

Route::namespace('FormBuilder\Module\Http\Controllers')
    ->group(function() {

        Route::prefix('form-builder')
            ->group(function(){
                Route::get('{form}/duplicate', [
                    'as'    => 'form-builder.duplicate',
                    'uses'  => 'FormBuilderController@duplicate'
                ]);
                Route::get('{form}/export', [
                    'as'    => 'form-builder.export',
                    'uses'  => 'FormBuilderController@export'
                ]);
            })
        ;

        Route::resource('form-builder.fields', 'FormFieldsController');
        Route::resource('form-builder', 'FormBuilderController');
    })
;
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

                // visual submissions browser (list + grouped detail)
                Route::get('{form}/submissions', [
                    'as'    => 'form-builder.submissions',
                    'uses'  => 'FormBuilderController@submissions'
                ]);
                Route::get('{form}/submissions/{token}', [
                    'as'    => 'form-builder.submissions.show',
                    'uses'  => 'FormBuilderController@submissionShow'
                ]);

                // JSON API backing the visual editor. {form} is route-model bound
                // to the Form model. Inherits the admin middleware group (web/auth).
                Route::prefix('{form}/api')
                    ->as('form-builder.api.')
                    ->group(function () {
                        Route::get('field-types', 'FormBuilderApiController@fieldTypes')->name('field-types');
                        Route::get('fields', 'FormBuilderApiController@fields')->name('fields');
                        Route::post('fields', 'FormBuilderApiController@storeField')->name('fields.store');
                        Route::post('fields/reorder', 'FormBuilderApiController@reorder')->name('fields.reorder');
                        Route::put('fields/{field}', 'FormBuilderApiController@updateField')->name('fields.update');
                        Route::delete('fields/{field}', 'FormBuilderApiController@destroyField')->name('fields.destroy');
                        Route::put('/', 'FormBuilderApiController@updateForm')->name('update');

                        // email notifications (Phase 6)
                        Route::get('notifications', 'FormBuilderApiController@notifications')->name('notifications');
                        Route::post('notifications', 'FormBuilderApiController@storeNotification')->name('notifications.store');
                        Route::post('notifications/reorder', 'FormBuilderApiController@reorderNotifications')->name('notifications.reorder');
                        Route::put('notifications/{notification}', 'FormBuilderApiController@updateNotification')->name('notifications.update');
                        Route::delete('notifications/{notification}', 'FormBuilderApiController@destroyNotification')->name('notifications.destroy');

                        // integrations (Phase 7)
                        Route::get('integrations', 'FormBuilderApiController@integrations')->name('integrations');
                        Route::put('integrations/{key}', 'FormBuilderApiController@updateIntegration')->name('integrations.update');
                    });
            })
        ;

        Route::resource('form-builder', 'FormBuilderController');
    })
;

<?php

namespace RefinedDigital\FormBuilder\Module\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use RefinedDigital\CMS\Modules\Core\Models\CoreModel;
use RefinedDigital\FormBuilder\Module\Traits\FieldType;
use Spatie\EloquentSortable\Sortable;

class FormField extends CoreModel implements Sortable
{
    use SoftDeletes, FieldType;

    protected $fillable = [
        'form_id', 'form_field_type_id', 'active', 'show_label', 'position',
        'name', 'required', 'placeholder', 'data', 'custom_field_class', 'store_in',
        'note', 'label_position', 'autocomplete', 'custom_class',
        'note_position', 'merge_field', 'hidden_field_value'
    ];

    /**
     * The fields to be displayed for creating / editing
     *
     * @var array
     */
    public $formFields = [
        [
            'name' => 'Content',
            'blocks' => [
                [
                    'name' => 'Config Details',
                    'fields' => [
                        [
                            [ 'count' => 3],
                            [ 'label' => 'Active', 'name' => 'active', 'required' => true, 'type' => 'select', 'options' => [1 => 'Yes', 0 => 'No'] ],
                            [ 'label' => 'Required', 'name' => 'required', 'required' => true, 'type' => 'select', 'options' => [0 => 'No', 1 => 'Yes'] ],
                        ],
                        [
                            [ 'count' => 3],
                            [ 'label' => 'Show Label', 'name' => 'show_label', 'required' => true, 'type' => 'select', 'options' => [1 => 'Yes', 0 => 'No'] ],
                            [ 'label' => 'Label Position', 'name' => 'label_position', 'required' => true, 'type' => 'select', 'options' => [1 => 'Top', 0 => 'Bottom'] ],
                            [ 'label' => 'Auto Complete', 'name' => 'autocomplete', 'required' => true, 'type' => 'select', 'options' => [1 => 'On', 0 => 'Off'] ],
                        ],
                        [
                            [ 'label' => 'Field Type', 'name' => 'form_field_type_id', 'required' => true, 'type' => 'fieldTypes', 'v-model' => 'form.field.type' ],
                            [ 'label' => 'Name', 'name' => 'name', 'required' => true],
                            [ 'label' => 'Placeholder', 'name' => 'placeholder'],
                        ],
                        [
                          [ 'label' => 'Field Note', 'name' => 'note'],
                          [ 'label' => 'Note Position', 'name' => 'note_position', 'required' => true, 'type' => 'select', 'options' => [0 => 'Bottom', 1 => 'Top' ] ],
                        ],
                        [
                          [ 'count' => 2],
                          [ 'label' => 'Hidden Field Value', 'name' => 'hidden_field_value', 'row' => [ 'attrs' => ['v-if' => 'form.field.type === \'12\'']]],
                          [ 'label' => 'Custom CSS Class', 'name' => 'custom_class', ],
                          [ 'label' => 'Custom Field Class', 'name' => 'custom_field_class', 'row' => [ 'attrs' => ['v-if' => 'form.field.type === \'20\'']]],
                        ],
                        [
                            [ 'count' => 2],
                            [ 'label' => 'Merge Field', 'name' => 'merge_field', ],
                        ],
                        [
                            [ 'label' => 'Data', 'name' => 'data', 'type' => 'textarea', 'row' => [ 'attrs' => ['v-if' => 'form.field.showDataFor.indexOf(form.field.type) > -1']]],
                        ],
                    ]
                ],
                [
                    'name' => 'Options',
                    'attrs' => ['v-show' => 'form.field.showOptionsFor.indexOf(form.field.type) > -1'],
                    'fields' => [
                        [
                            [ 'label' => 'Options', 'name' => 'options', 'type' => 'options', 'hideLabel' => true],
                        ],
                    ]
                ],
            ]
        ]
    ];
}

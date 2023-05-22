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

    protected $casts = [
      'id' => 'integer',
      'active' => 'integer',
      'position' => 'integer',
      'form_id' => 'integer',
      'form_field_type_id' => 'integer',
      'show_label' => 'integer',
      'required' => 'integer',
      'note_position' => 'integer',
      'label_position' => 'integer',
      'autocomplete' => 'integer',
    ];

    protected $appends = [
        'view',
    ];

    /**
     * The fields to be displayed for creating / editing
     *
     * @var array
     */
    public $formFields = [
        [
            'name' => 'Content',
            'sections' => [
                'left' => [
                    'blocks' => [
                        [
                            'name' => 'Content',
                            'fields' => [
                                [
                                    [ 'label' => 'Field Type', 'name' => 'form_field_type_id', 'required' => true, 'type' => 'fieldTypes', 'v-model' => 'form.field.type' ],
                                    [ 'label' => 'Name', 'name' => 'name', 'required' => true],
                                ],
                                [
                                    [ 'label' => 'Placeholder', 'name' => 'placeholder', 'row' => [ 'attrs' => ['v-if' => 'form.labelPosition !== \'2\'']]],
                                    [ 'label' => 'Hidden Field Value', 'name' => 'hidden_field_value', 'row' => [ 'attrs' => ['v-if' => 'form.field.type === \'12\'']]],
                                ],
                                [
                                    [ 'label' => 'Field Note', 'name' => 'note'],
                                    [ 'label' => 'Note Position', 'name' => 'note_position', 'required' => true, 'type' => 'select', 'options' => [0 => 'Bottom', 1 => 'Top' ] ],
                                ],
                                [
                                    [ 'label' => 'Custom CSS Class', 'name' => 'custom_class', ],
                                    [ 'label' => 'Custom Field Class', 'name' => 'custom_field_class', 'row' => [ 'attrs' => ['v-if' => 'form.field.type === \'20\'']]],
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
                ],
                'right' => [
                    'blocks' => [
                        [
                            'name' => 'Settings',
                            'fields' => [
                                [
                                    [ 'label' => 'Active', 'name' => 'active', 'required' => true, 'type' => 'select', 'options' => [1 => 'Yes', 0 => 'No'] ],
                                    [ 'label' => 'Required', 'name' => 'required', 'required' => true, 'type' => 'select', 'options' => [0 => 'No', 1 => 'Yes'] ],
                                ],
                                [
                                    [ 'label' => 'Show Label', 'name' => 'show_label', 'required' => true, 'type' => 'select', 'options' => [1 => 'Yes', 0 => 'No'] ],
                                    [ 'label' => 'Label Position', 'name' => 'label_position', 'required' => true, 'type' => 'select', 'options' => [1 => 'Top', 0 => 'Bottom', 2 => 'Floating'], 'attrs' => ['v-model' => "form.labelPosition"] ],
                                ],
                                [
                                    [ 'label' => 'Auto Complete', 'name' => 'autocomplete', 'required' => true, 'type' => 'select', 'options' => [1 => 'On', 0 => 'Off'] ],
                                ],
                                [
                                    [ 'label' => 'Merge Field', 'name' => 'merge_field', ],
                                ],
                            ]
                        ]
                    ]
                ],
            ]
        ],
    ];
}

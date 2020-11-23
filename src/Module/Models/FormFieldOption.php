<?php

namespace RefinedDigital\FormBuilder\Module\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use RefinedDigital\CMS\Modules\Core\Models\CoreModel;
use Spatie\EloquentSortable\Sortable;

class FormFieldOption extends CoreModel implements Sortable
{
    use SoftDeletes;

    protected $fillable = [
        'form_field_id', 'position', 'value', 'label'
    ];

    protected $casts = [
      'id' => 'integer',
      'position' => 'integer',
    ];
}

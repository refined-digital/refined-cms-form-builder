<?php

namespace RefinedDigital\FormBuilder\Module\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use RefinedDigital\CMS\Modules\Core\Models\CoreModel;
use Spatie\EloquentSortable\Sortable;

class FormFieldType extends CoreModel implements Sortable
{
    use SoftDeletes;

    protected $fillable = [
        'active', 'position', 'name',
    ];

    protected $casts = [
      'id' => 'integer',
      'active' => 'integer',
      'position' => 'integer',
    ];
}

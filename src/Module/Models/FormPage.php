<?php

namespace RefinedDigital\FormBuilder\Module\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use RefinedDigital\CMS\Modules\Core\Models\CoreModel;

class FormPage extends CoreModel
{
    use SoftDeletes;

    protected $fillable = ['form_id', 'name', 'position'];

    protected $casts = [
        'id' => 'integer',
        'form_id' => 'integer',
        'position' => 'integer',
    ];

    public function fields()
    {
        return $this->hasMany(FormField::class, 'form_page_id')->orderBy('position');
    }
}

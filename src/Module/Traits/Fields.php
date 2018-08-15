<?php

namespace RefinedDigital\FormBuilder\Module\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use RefinedDigital\FormBuilder\Module\Scopes\FieldsScope;

trait Fields
{
    /**
     * Boot the is page trait for a model.
     *
     * @return void
     */
    public static function bootFields()
    {
        static::addGlobalScope(new FieldsScope());
    }

    public function fields() : HasMany
    {
        return $this->hasMany('RefinedDigital\FormBuilder\Module\Models\FormField', 'form_id');
    }

}
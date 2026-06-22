<?php

namespace RefinedDigital\FormBuilder\Module\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RefinedDigital\FormBuilder\Module\Scopes\FieldTypeScope;
use Str;
use Blade;

trait FieldType
{
    /**
     * Boot the is page trait for a model.
     *
     * @return void
     */
    public static function bootFieldType()
    {
        static::addGlobalScope(new FieldTypeScope());
    }


    public function type() : BelongsTo
    {
        return $this->belongsTo('RefinedDigital\FormBuilder\Module\Models\FormFieldType', 'form_field_type_id');
    }

    public function fieldOptions() : HasMany
    {
        return $this->hasMany('RefinedDigital\FormBuilder\Module\Models\FormFieldOption', 'form_field_id');
    }

    protected function getArrayableItems(array $values)
    {
        $appends = ['options','select_options','view','attributes','field_name','value'];
        foreach ($appends as $field) {
            if (!in_array($field, $this->appends)){
                $this->appends[] = $field;
            }
        }

        $hidden = ['fieldOptions'];
        foreach ($hidden as $field) {
            if (!in_array($field, $this->hidden)){
                $this->hidden[] = $field;
            }
        }

        return parent::getArrayableItems($values);
    }

    public function getOptionsAttribute() {
        $options = $this->fieldOptions;

        if ($options && $options->count()) {
            $data = [];
            foreach ($options as $option) {
                $data[] = [
                    'value' => $option->value,
                    'label' => $option->label,
                ];
            }

            return $data;
        }

        return null;

    }

    public function getSelectOptionsAttribute() {
        $options = $this->fieldOptions;

        if ($options && $options->count()) {
            $data = [];
            foreach ($options as $option) {
                $data[$option->value] = $option->label;
            }

            return $data;
        }

        return [];

    }

    public function getAttributesAttribute()
    {
        // delegate per-type HTML attributes to the field's class (htmlAttributes()),
        // so there's no field-type switch here. custom fields with no host class
        // fall back to the base attribute set.
        $instance = forms()->getFieldClassInstance($this);
        if ($instance) {
            return $instance->htmlAttributes();
        }

        return (new \RefinedDigital\FormBuilder\Module\Fields\FormField($this))->htmlAttributes();
    }

    public function getIsStructuralAttribute()
    {
        $instance = forms()->getFieldClassInstance($this);
        return $instance ? $instance->isStructural() : false;
    }

    public function getViewAttribute()
    {
        // the registry resolves the class for every built-in type (incl. 11),
        // so there's no per-type special-casing here.
        $class = forms()->getFieldClass($this);
        if ($class) {
            return $class;
        }

        // no class resolved — fall back to a blade view by type name. for a
        // custom field (type 20) prefer the host class's own view when present,
        // otherwise never blow up rendering/the API.
        $view = 'formBuilder::front-end.fields.'.Str::slug($this->type->name);

        if ($this->form_field_type_id == 20 && $this->custom_field_class) {
            $customClass = forms()->getFieldClassByName($this->custom_field_class);
            if (is_string($customClass) && class_exists($customClass)) {
                $view = (new $customClass($this))->getView();
            }
        }

        // a custom field with no (resolvable) class would point at a view that
        // doesn't exist and fatal the whole form. render nothing instead.
        if (!view()->exists($view) && !class_exists($view)) {
            return \RefinedDigital\FormBuilder\Module\Fields\FormField::class;
        }

        return $view;
    }

    public function getFieldNameAttribute()
    {
        $name = 'field'.$this->id;
        return $name;
    }

    public function getShowLabelAttribute()
    {
        if ($this->form_field_type_id == 6) {
            return false;
        }

        if ($this->id) {
            return $this->attributes['show_label'];
        }

        return true;

    }

    public function getLabelPositionAttribute()
    {
        $label = $this->id ? $this->attributes['label_position'] : 1;

        $forceToTop = [3,4,5,13,14,15,16,17,18,19];
        if (in_array($this->form_field_type_id, $forceToTop) && $this->attributes['label_position'] != 2) {
            $label = 1;
        }

        return $label;
    }

    public function getValueAttribute()
    {
        if (old($this->field_name)) {
            return old($this->field_name);
        }

        // seed the default value when there's no posted/old value
        if (isset($this->attributes['default_value']) && $this->attributes['default_value'] !== '' && $this->attributes['default_value'] !== null) {
            return $this->attributes['default_value'];
        }

        return null;
    }

    public function renderView($defaultFields = [], $selectFieldsOverride = [])
    {
        $className = $this->view;
        return (new $className($this, $defaultFields, $selectFieldsOverride))->renderView();
    }

}

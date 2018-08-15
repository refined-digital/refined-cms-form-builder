<?php

namespace RefinedDigital\FormBuilder\Module\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RefinedDigital\FormBuilder\Module\Scopes\FieldTypeScope;

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
        $args = [
            'class'     => 'form__control',
            'id'        => 'form__field--'.$this->id,
            'required'  => 'required'
        ];

        if ($this->placeholder) {
            $args['placeholder'] = $this->placeholder;
        }

        if (!$this->autocomplete) {
            $args['autocomplete'] = 'nope'; // chrome ignores off, so set it to a random string, in this case nope
        }

        switch ($this->form_field_type_id) {
            case 4:
                $args['class'] .= ' form__control--radio';
                break;
            case 5:
            case 6:
                $args['class'] .= ' form__control--checkbox';
                break;
            case 12:
                unset($args['class']);
                unset($args['required']);
                break;
            case 15:
                $args['class'] .= ' form__control--date-picker';
                break;
            case 18:
                $args['class'] .= ' form__control--multiple-files';
                $args['multiple'] = 'multiple';
                break;
        }

        return $args;
    }

    public function getViewAttribute()
    {
        $name = $this->type->name;
        if ($this->form_field_type_id == 11) {
            $name = 'password';
        }
        return 'formBuilder::front-end.fields.'.str_slug($name);
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

        return $this->attributes['show_label'];
    }

    public function getLabelPositionAttribute()
    {
        $label = $this->attributes['label_position'];

        $forceToTop = [3,4,5,13,14,15,16,17,18,19];
        if (in_array($this->form_field_type_id, $forceToTop)) {
            $label = 1;
        }

        return $label;
    }

    public function getValueAttribute()
    {
        if (old($this->field_name)) {
            return old($this->field_name);
        }

        return null;
    }

}
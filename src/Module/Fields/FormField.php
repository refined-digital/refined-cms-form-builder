<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

use Illuminate\Container\Container;
use RefinedDigital\FormBuilder\Module\Contracts\FormFieldInterface;

class FormField {
    protected $field;
    protected $value;

    public function __construct($field, $defaultFields = [], $selectFieldsOverride = [])
    {
        $this->field = $field;
        $this->selectFieldsOverride = $selectFieldsOverride;

        $value = $field->value;

        if ($field->form_field_type_id == 12) {
            $value = $field->hidden_field_value ?? $field->data;
        }

        if (isset($defaultFields, $defaultFields[$field->field_name])) {
            $value = $defaultFields[$field->field_name];
        }
        if (isset($defaultFields, $field->custom_class, $defaultFields[$field->custom_class])) {
            $value = $defaultFields[$field->custom_class];
        }


        $this->value = $value;
    }

    public function formatData($request)
    {
        if (!is_array($request)) {
            $request = $request->all();
        }
    }

    public function renderView()
    {
        $view = $this->resolveView($this->render());

        $with = [
            'field' => $this->field,
            'value' => $this->value
        ];

        if ($this->field->form_field_type_id == 3 && $this->selectFieldsOverride) {
            $with['selectFieldsOverride'] = $this->selectFieldsOverride;
        }

        return view()
            ->make($view)
            ->with($with)
            ->render();
    }

    protected function resolveView($view)
    {
        $resolver = function ($view) {
            $factory = Container::getInstance()->make('view');

            return $this->createBladeViewFromString($factory, $view);
        };

        return $resolver($view);
    }

    protected function createBladeViewFromString($factory, $contents)
    {
        $directory = Container::getInstance()['config']->get('view.compiled');

        $factory->addNamespace(
            '__formFields',
            $directory
        );

        if (! is_file($viewFile = $directory.'/'.sha1($contents).'.blade.php')) {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($viewFile, $contents);
        }

        return '__formFields::'.basename($viewFile, '.blade.php');
    }
}

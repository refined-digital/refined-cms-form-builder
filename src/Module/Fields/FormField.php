<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField {
    protected $view = '';
    protected $name = '';
    protected $templatePath = '';

    public function __construct()
    {
        $this->templatePath = 'formBuilder::'.$this->name.'.resources.views.';
    }

    public function getView()
    {
        return $this->templatePath.$this->view;
    }

    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    public function formatData($field, $request)
    {
        if (!is_array($request)) {
            $request = $request->all();
        }
    }
}
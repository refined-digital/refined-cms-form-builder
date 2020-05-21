<?php

namespace RefinedDigital\FormBuilder\Module\Http\Repositories;

use RefinedDigital\FormBuilder\Module\Models\FormField;

class FormsRepository
{

    protected $form;
    protected $template = 'front-end.form';
    protected $formBuilderRepository;
    protected $attributes = [];
    protected $hasPayments = false;
    protected $templateNamespace = 'formBuilder';

    public function __construct(FormBuilderRepository $repo)
    {
        $this->formBuilderRepository = $repo;
    }

    public function getGroupCount($group)
    {
        $size = sizeof($group);

        if (sizeof($group)) {
            foreach ($group as $d) {
                if (isset($d->count)) {
                    $size = $d->count;
                    continue;
                }
            }
        }

        return $size;
    }

    public function getForSelect($type = false)
    {
        return $this->formBuilderRepository->getForSelect($type);
    }


    public function getReplyToOptions()
    {
        $fields = [
            0       => 'None',
            'text'  => 'Enter Email Address'
        ];

        // grab the fields
        $id = request()->route('form_builder');
        $formFields = FormField::whereFormFieldTypeId(8)
                        ->whereFormId($id)
                        ->orderby('name','asc')
                        ->get();

        if ($formFields && $formFields->count()) {
            foreach ($formFields as $field) {
                $fields[$field->id] = $field->name;
            }
        }

        return $fields;
    }



    public function form($requestedForm)
    {
        // find the form
        if (is_integer($requestedForm) || is_numeric($requestedForm)) {
            $form = $this->formBuilderRepository->getFormById($requestedForm);
            if (!isset($form->id)) {
                throw new \Error('Form does not exist.');
            }
        } else {
            $form = $this->formBuilderRepository->getFormByName($requestedForm);
            if (!isset($form->id)) {
                throw new \Error('Form "'.$requestedForm.'" does not exist.');
            }
        }

        // todo: add the model stuff

        // add in the settings
        $form->settings = settings()->get('form-builder');

        $this->form = $form;

        return $this;
    }

    public function setDefaultFields($fields)
    {
        $this->form->defaultFields = $fields;

        return $this;
    }

    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    public function setButtonText($text = false)
    {
        if ($text) {
            $this->form->submitText = $text;
        }

        return $this;
    }

    public function setHasPayments($value)
    {
        $this->hasPayments = $value;

        return $this;
    }

    public function setTemplateNamespace($value = 'formBuilder')
    {
        $this->templateNamespace = $value;

        return $this;
    }

    public function setAttributes(array $args)
    {
        $this->attributes = $args;

        return $this;
    }

    public function getCountries()
    {
        $countries = config('form-builder.countries');
        array_unshift($countries, 'Please Select');
        return $countries;
    }

    public function render()
    {

        $template = $this->templateNamespace.'::'.$this->template;

        $args = new \stdClass();
        $args->route = route('refined.form-builder.submit', $this->form->id);
        $args->attributes = [
            'class' => ['form--'.$this->form->id],
            'novalidate'
        ];

        if (sizeof($this->attributes)) {
            if (isset($this->attributes['class'])) {
                $args->attributes['class'] = array_merge($args->attributes['class'], $this->attributes['class']);
                unset($this->attributes['class']);
            }

            if (sizeof($this->attributes)) {
                $args->attributes = array_merge($args->attributes, $this->attributes);
            }
        }

        // set the default button text
        if (!isset($this->form->submitText) || (isset($this->form->submitText) && !$this->form->submitText)) {
            $this->form->submitText = 'Submit';
        }

        // stringify the classes
        $args->attributes['class'] = implode(' ', $args->attributes['class']);

        if ($this->formBuilderRepository->hasFilesField($this->form)) {
            $args->attributes['enctype'] = 'multipart/form-data';
        }

        $returnData = [
            'args' => $args,
            'form' => $this->form,
            'hasPayments' => $this->hasPayments
        ];

        return view($template, $returnData);
    }



    public function getFieldClass($field)
    {
        $class = $this->getFieldClassName($field->custom_field_class);
        return new $class;
    }

    public function getFieldClassByName($customClassName)
    {
        $class = $this->getFieldClassName($customClassName);
        return new $class;
    }

    public function getFieldClassName($customClassName)
    {
        $name = str_replace('FormField_', '', $customClassName);
        $class = 'App\\RefinedCMS\\Forms\\'.$name.'\\'.$customClassName;

        return $class;
    }

    public function formatFieldsByName($request, $form)
    {
        $data = [];
        $counts = [];
        if (isset($form->fields)) {
            foreach ($form->fields as $field) {
                $key = $field->name;
                if (isset($counts[$field->name])) {
                    $key .= '_'.$counts[$field->name];
                }
                $data[$key] = $request->get($field->field_name);

                if (!isset($counts[$field->name])) {
                    $counts[$field->name] = 0;
                }
                $counts[$field->name] ++;
            }
        }

        return $data;
    }


}

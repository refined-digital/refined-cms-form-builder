<?php

namespace RefinedDigital\FormBuilder\Module\Http\Repositories;

use RefinedDigital\FormBuilder\Module\Models\FormField;

class FormsRepository
{

    protected $form;
    protected $template = 'front-end.form';
    protected $formBuilderRepository;

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
                return 'Form does not exist.';
            }
        } else {
            $form = $this->formBuilderRepository->getFormByName($requestedForm);
            if (!isset($form->id)) {
                return 'Form "'.$requestedForm.'" does not exist.';
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
        $this->formTemplate = $template;

        return $this;
    }

    public function setButtonText($text = false)
    {
        if ($text) {
            $this->form->submitText = $text;
        }

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

        $template = 'formBuilder::'.$this->template;

        $args = new \stdClass();
        $args->route = route('refined.form-builder.submit', $this->form->id);
        $args->attributes = [
            'class' => 'form--'.$this->form->id,
            'novalidate'
        ];

        if ($this->formBuilderRepository->hasFilesField($this->form)) {
            $args->attributes['enctype'] = 'multipart/form-data';
        }

        $returnData = [
            'args' => $args,
            'form' => $this->form
        ];

        //return app()->make(Render::class)->form($template, $returnData);

        return view($template, $returnData);
    }



}
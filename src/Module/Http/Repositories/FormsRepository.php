<?php

namespace RefinedDigital\FormBuilder\Module\Http\Repositories;

use RefinedDigital\FormBuilder\Module\Models\FormField;
use Str;

class FormsRepository
{

    protected $form;
    protected $template = 'front-end.form';
    protected $formBuilderRepository;
    protected $attributes = [];
    protected $hasPayments = false;
    protected $templateNamespace = 'formBuilder';
    protected $selectFieldsOverride = [];
    protected $replacement;

    public function __construct(FormBuilderRepository $repo)
    {
        $this->formBuilderRepository = $repo;
    }

    public function load($requestedForm)
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

    public function render()
    {

        $template = $this->templateNamespace.'::'.$this->template;

        $args = new \stdClass();
        $args->route = route('refined.form-builder.submit', $this->form->id);
        $args->attributes = [
            'class' => ['form--'.$this->form->id],
            'novalidate'
        ];

        if ($this->form->recaptcha && env('RECAPTCHA_SITE_KEY')) {
            $this->attributes['data-red'] = env('RECAPTCHA_SITE_KEY');
        }

        if ($this->replacement) {
            $args->attributes['data-replacement'] = $this->replacement;
        }

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

        // set the default button loading text
        if (!isset($this->form->loadingText) || (isset($this->form->loadingText) && !$this->form->loadingText)) {
            $this->form->loadingText = 'Loading...';
        }

        // stringify the classes
        $args->attributes['class'] = implode(' ', $args->attributes['class']);

        if ($this->formBuilderRepository->hasFilesField($this->form)) {
            $args->attributes['enctype'] = 'multipart/form-data';
        }

        $fields = $this->setFields();

        $returnData = [
            'args' => $args,
            'form' => $this->form,
            'hasPayments' => $this->hasPayments,
            'fields' => $fields,
            'selectFieldsOverride' => $this->selectFieldsOverride
        ];

        return view($template, $returnData);
    }

    public function setReplacementElement($replacement)
    {
        $this->replacement = $replacement;

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

    public function setButtonLoadingText($text = false)
    {
        if ($text) {
            $this->form->loadingText = $text;
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

    public function setAdditionalHiddenFields($fields)
    {
      if (!isset($this->additionalFields)) {
        $this->additionalFields = new \stdClass();
      }
      $this->additionalFields->hidden = $this->formatAdditionalFields($fields);
      return $this;
    }

    public function setAdditionalFields($fields)
    {
      if (!isset($this->additionalFields)) {
        $this->additionalFields = new \stdClass();
      }
      $this->additionalFields->fields = $this->formatAdditionalFields($fields);
      return $this;
    }

    public function setSelectFieldsOverride($key, $values)
    {
        $this->selectFieldsOverride[$key] = $values;
        return $this;
    }

    private function setFields()
    {
      $fields = new \stdClass();
      $fields->fields = $this->form->fields->filter(function($field) { return $field->form_field_type_id != 12; });
      $fields->hidden = $this->form->fields->filter(function($field) { return $field->form_field_type_id == 12; });

      if (isset($this->additionalFields->fields) && $this->additionalFields->fields->count()) {
        foreach ($this->additionalFields->fields as $field) {
          $fields->fields->push($field);
        }
      }
      if (isset($this->additionalFields->hidden) && $this->additionalFields->hidden->count()) {
        foreach ($this->additionalFields->hidden as $field) {
          $fields->hidden->push($field);
        }
      }

      return $fields;
    }




    public function getGroupCount($group)
    {
        return core()->getGroupCount($group);
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

    public function getFieldClass($field)
    {
        if ($field->custom_field_class) {
            $class = $this->getCustomFieldClassName($field->custom_field_class);
        } else {
            $class = $this->getFieldClassName($field->type->name);
        }

        if (class_exists($class)) {
            return $class;
        }

        return false;
    }

    public function getFieldClassByName($customClassName)
    {
        return $this->getCustomFieldClassName($customClassName);
    }

    public function getCustomFieldClassName($customClassName)
    {
        $name = str_replace('FormField_', '', $customClassName);

        return 'App\\RefinedCMS\\Forms\\'.$name.'\\'.$customClassName;
    }

    public function getFieldClassName($className)
    {
        $name = 'FormField_'.ucfirst(Str::camel($className));

        return 'RefinedDigital\\FormBuilder\\Module\\Fields\\'.$name;
    }

    public function getCountries()
    {
        $countries = config('form-builder.countries');
        array_unshift($countries, 'Please Select');
        return $countries;
    }


    public function formatFieldsByName($request, $form, $keyType = false)
    {
        $data = [];
        $counts = [];
        if (isset($form->fields)) {
            foreach ($form->fields as $field) {
                $key = $field->name;
                if (isset($counts[$field->name])) {
                    $key .= '_'.$counts[$field->name];
                }

                if ($keyType) {
                  switch ($keyType) {
                    case 'snake':
                      $key = Str::snake($key);
                      break;
                    case 'camel':
                      $key = Str::camel($key);
                      break;
                    case 'kebab':
                      $key = Str::kebab($key);
                      break;
                    case 'slug':
                      $key = Str::slug($key);
                      break;
                  }
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

    private function formatAdditionalFields($fields)
    {
      $formattedFields = collect([]);

      $hiddenClassName = $this->getFieldClassName('hidden');

      if (is_array($fields) && sizeof($fields)) {
        foreach ($fields as $field) {
          $field = (object) $field;

          // todo: check if this needs the namespace replaced via $this->templateNamespace
          $view = 'formBuilder::front-end.fields.'.$field->view;

          if ($field->view === 'hidden') {
            $field->form_field_type_id = 12;
            if ($field->value) {
              $field->hidden_field_value = $field->value;
            }
            $field->attributes = [];
            if (class_exists($hiddenClassName)) {
              $class = new $hiddenClassName($field);
            }
            if ($class) {
              $view = $class->renderView();
            }
          }

          $field->view = $view;
          $field->name = Str::slug($field->field_name, '_');
          $field->attributes = [
            'id' => 'form__field-extra--'.Str::slug($field->field_name)
          ];

          if ($field->value || $field->value == '') {
            $field->data = $field->value;
            unset($field->value);
          }

          $formattedFields->push($field);
        }
      }

      return $formattedFields;
    }

    public function formatWithMergeFields($request, $form)
    {
      if(is_array($request)) {
        $data = $request;
      } else {
        $data = $request->all();
      }

      $fields = [];
      if ($form->fields && $form->fields->count()) {
        foreach ($form->fields as $field) {
          if ($field->merge_field) {
            $fields[$field->merge_field] = isset($data[$field->field_name]) ? $data[$field->field_name] : '';
          }
        }
      }

      return $fields;
    }

    public function getGoogleRecaptchaJS()
    {
        return '<script src="//www.google.com/recaptcha/api.js" async defer></script>';
    }

    public function googleRecaptchaEnabled()
    {
        return env('RECAPTCHA_SITE_KEY') && env('RECAPTCHA_SECRET_KEY');
    }
}

<?php

namespace RefinedDigital\FormBuilder\Module\Http\Repositories;

use RefinedDigital\FormBuilder\Module\Enums\FormFieldType;
use RefinedDigital\FormBuilder\Module\Models\FormField;
use Str;

class FormsRepository
{

    protected $form;
    protected string $template = 'front-end.form';
    protected array $attributes = [];
    protected string $templateNamespace = 'formBuilder';
    protected array $selectFieldsOverride = [];
    protected $replacement;

    public function __construct(private readonly FormBuilderRepository $formBuilderRepository)
    {
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
            'class' => ['form--builder', 'form--'.$this->form->id],
            'data-fb-form' => $this->form->id,
            'novalidate'
        ];

        if ($this->form->recaptcha && env('RECAPTCHA_SITE_KEY')) {
            $this->attributes['data-red'] = env('RECAPTCHA_SITE_KEY');
        }

        if ($this->replacement) {
            $args->attributes['data-replacement'] = $this->replacement;
        }

        if (count($this->attributes)) {
            if (isset($this->attributes['class'])) {
                $args->attributes['class'] = array_merge($args->attributes['class'], $this->attributes['class']);
                unset($this->attributes['class']);
            }

            if (count($this->attributes)) {
                $args->attributes = array_merge($args->attributes, $this->attributes);
            }
        }

        // set the default button text (DB column submit_text wins, then any
        // fluent override, then the default)
        if (!empty($this->form->submit_text)) {
            $this->form->submitText = $this->form->submit_text;
        }
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

        $integrationMarkup = $this->integrationMarkup();

        $returnData = [
            'args' => $args,
            'form' => $this->form,
            'integrationHidden' => $integrationMarkup['hidden'],
            'integrationVisible' => $integrationMarkup['visible'],
            'fields' => $fields,
            'selectFieldsOverride' => $this->selectFieldsOverride
        ];

        return view($template, $returnData);
    }

    /**
     * Collect front-end markup contributed by the form's enabled integrations
     * (Phase 7 generic injection hook). An integration's aggregate `view` is either
     * a closure or a class with render($form, $config); it returns either a string
     * (treated as visible markup) or ['hidden' => '…', 'visible' => '…'].
     */
    protected function integrationMarkup(): array
    {
        $hidden = '';
        $visible = '';

        $aggregate = app(\RefinedDigital\CMS\Modules\Core\Aggregates\FormBuilderIntegrationAggregate::class);

        foreach ($this->form->integrations()->where('enabled', true)->get() as $row) {
            $definition = $aggregate->get($row->integration_key);
            if (!$definition || empty($definition['view'])) {
                continue;
            }

            $view = $definition['view'];
            $config = $row->config ?? [];

            try {
                if (is_callable($view)) {
                    $markup = $view($this->form, $config);
                } elseif (is_string($view) && class_exists($view)) {
                    $markup = app($view)->render($this->form, $config);
                } else {
                    continue;
                }
            } catch (\Throwable $e) {
                continue;
            }

            if (is_array($markup)) {
                $hidden .= $markup['hidden'] ?? '';
                $visible .= $markup['visible'] ?? '';
            } elseif (is_string($markup)) {
                $visible .= $markup;
            }
        }

        return ['hidden' => $hidden, 'visible' => $visible];
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
      $fields->fields = $this->form->fields->filter(fn($field) => $field->form_field_type_id != FormFieldType::HIDDEN->value);
      $fields->hidden = $this->form->fields->filter(fn($field) => $field->form_field_type_id == FormFieldType::HIDDEN->value);

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
      $formFields = FormField::whereFormFieldTypeId(FormFieldType::EMAIL->value)
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

    /**
     * Resolve the FormField_* class NAME for a field, or false if none.
     * Custom (type 20) fields resolve to a host-app class; everything else
     * comes from the id->class registry (config('form-builder.field_classes')),
     * falling back to the legacy name-derivation if the config is unpublished.
     */
    public function getFieldClass($field)
    {
        if ($field->custom_field_class) {
            $class = $this->getCustomFieldClassName($field->custom_field_class);
        } else {
            $map = config('form-builder.field_classes', []);
            $class = $map[$field->form_field_type_id] ?? $this->getFieldClassName($field->type->name);
        }

        if (class_exists($class)) {
            return $class;
        }

        return false;
    }

    /**
     * Resolve and instantiate a field's class. Single entry point used by both
     * rendering and validation so neither needs a field-type switch. Returns
     * null when no class resolves (e.g. a custom field whose host class is
     * missing).
     */
    public function getFieldClassInstance($field, $defaultFields = [], $selectFieldsOverride = [])
    {
        $class = $this->getFieldClass($field);
        if (!$class) {
            return null;
        }

        return new $class($field, $defaultFields, $selectFieldsOverride);
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
                  $key = match ($keyType) {
                    'snake' => Str::snake($key),
                    'camel' => Str::camel($key),
                    'kebab' => Str::kebab($key),
                    'slug'  => Str::slug($key),
                    default => $key,
                  };
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

      if (is_array($fields) && count($fields)) {
        foreach ($fields as $field) {
          $field = (object) $field;

          // todo: check if this needs the namespace replaced via $this->templateNamespace
          $view = 'formBuilder::front-end.fields.'.$field->view;

          if ($field->view === 'hidden') {
            $field->form_field_type_id = FormFieldType::HIDDEN->value;
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

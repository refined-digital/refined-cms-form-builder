<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

use Illuminate\Container\Container;

class FormField {
    protected $field;
    protected $value;
    protected $selectFieldsOverride;
    protected $defaultFields;

    public function __construct($field, $defaultFields = [], $selectFieldsOverride = [])
    {
        $this->field = $field;
        $this->selectFieldsOverride = $selectFieldsOverride;
        $this->defaultFields = $defaultFields;

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

    public function formatData($field, $request)
    {
        if (!is_array($request)) {
            $request = $request->all();
        }
    }

    /**
     * The HTML input type for simple inputs (text/email/number/...). A concrete
     * field that just wraps a single <input> overrides this and lets the base
     * render() build the markup. Null means the field renders some other way.
     */
    protected function inputType(): ?string
    {
        return null;
    }

    /**
     * The option set for select-style fields ([value => label]). Overridden by
     * selects so the base render() can emit the <select>. Null = not a select.
     */
    protected function options(): ?array
    {
        return null;
    }

    /**
     * Default markup for fields that declare an inputType() or options().
     * Concrete classes with bespoke markup (checkbox/radio/file/password)
     * override render() directly instead.
     */
    public function render()
    {
        if ($this->options() !== null) {
            // $options is injected by renderView() so selectFieldsOverride and
            // per-type option sources are resolved in PHP, not blade.
            return <<<'blade'
{!!
    html()
        ->select($field->field_name, $options, $value)
        ->attributes($field->attributes)
!!}
blade;
        }

        if ($this->inputType() !== null) {
            $type = $this->inputType();
            return <<<blade
{!!
    html()
        ->input('{$type}', \$field->field_name, \$value)
        ->attributes(\$field->attributes)
!!}
blade;
        }

        return '';
    }

    /**
     * The HTML attributes for this field's input. Base provides the common set
     * (class, id, required, placeholder, autocomplete, visibility); type-specific
     * classes/attrs are added by concrete classes overriding this and merging.
     * Replaces the per-type switch that used to live on the model.
     */
    public function htmlAttributes(): array
    {
        $field = $this->field;

        $args = [
            'class'    => 'form__control',
            'id'       => 'form__field--'.$field->id,
        ];

        // only genuinely-required fields get the HTML required attribute, so the
        // front-end validator (and native browser validation) don't treat every
        // field as mandatory
        if ($field->required) {
            $args['required'] = 'required';
        }

        if ($field->placeholder) {
            $args['placeholder'] = $field->placeholder;
        }

        if (!$field->autocomplete) {
            $args['autocomplete'] = uniqid(); // chrome ignores off, so set a random string
        }

        // visibility: visible (default) | hidden | disabled | readonly
        $visibility = $field->getAttributes()['visibility'] ?? 'visible';
        if ($visibility === 'disabled') {
            $args['disabled'] = 'disabled';
        } elseif ($visibility === 'readonly') {
            $args['readonly'] = 'readonly';
        }

        return $args;
    }

    // ---- validation hooks (asked for by FormSubmitRequest, no type switch) ----

    /** Laravel rules for this field's input, beyond the caller-added 'required'. */
    public function rules(): array
    {
        return [];
    }

    /**
     * Rules that still apply when the field is OPTIONAL but filled in (format
     * checks like email/mimes). Defaults to rules(); a class overrides to []
     * for presence-style rules that only make sense when required (e.g. not0).
     */
    public function optionalRules(): array
    {
        return $this->rules();
    }

    /** ['rule' => 'message']; the caller keys them onto field{id}.rule. */
    public function messages(): array
    {
        return [];
    }

    /** Synthetic sibling fields, e.g. password's *_confirmation. */
    public function extraRules(): array
    {
        return [];
    }

    /** Whether the gibberish anti-spam rule applies (Text/Textarea only). */
    public function wantsGibberish(): bool
    {
        return false;
    }

    /** Whether the field validates as an array (Multiple Files -> field{id}.*). */
    public function isArrayField(): bool
    {
        return false;
    }

    /** Structural fields (group start/end) render raw markup, not through row.blade. */
    public function isStructural(): bool
    {
        return false;
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

        // selects built by the base render() read their options from scope
        if ($this->options() !== null) {
            $with['options'] = $this->options();
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

    public function getValidationRules()
    {
        if (isset($this->validationRules)) {
            return $this->validationRules;
        }

        return null;
    }
}

<?php

namespace RefinedDigital\FormBuilder\Module\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use RefinedDigital\FormBuilder\Module\Rules\ReCaptcha;
use RefinedDigital\FormBuilder\Module\Rules\Gibberish;
use RefinedDigital\FormBuilder\Module\Support\ConditionEvaluator;

class FormSubmitRequest extends FormRequest
{

    protected $customMessages = [];

    /**
     * Determine if the service is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $form = $this->route('form');
        $skip = config('form-builder.skip_validation');

        if (!isset($form->fields) || !$form->fields || !$form->fields->count()) {
            return null;
        }

        $args = [];
        $data = $this->all();

        foreach ($form->fields as $field) {
            // skip non-validated types (static/hidden) and conditionally-hidden fields
            if (in_array($field->form_field_type_id, $skip)) {
                continue;
            }
            if (ConditionEvaluator::isHidden($field, $data)) {
                continue;
            }

            // each field's class owns its rules/messages — no field-type switch here
            $instance = forms()->getFieldClassInstance($field);
            $name = $field->field_name;

            if ($field->required) {
                $rules = ['required'];
                $this->customMessages[$name.'.required'] = 'The '.$field->name.' field is required.';

                if ($instance) {
                    $rules = array_merge($rules, $instance->rules(), $this->customFieldRules($field, $instance));
                    foreach ($instance->messages() as $rule => $message) {
                        $key = $instance->isArrayField() ? $name.'.*.'.$rule : $name.'.'.$rule;
                        $this->customMessages[$key] = $message;
                    }
                    foreach ($instance->extraRules() as $extraName => $spec) {
                        $args[$extraName] = $spec['rules'];
                        $this->customMessages += $spec['messages'];
                    }
                }

                // array fields (Multiple Files) validate per-item under name.*
                if ($instance && $instance->isArrayField()) {
                    $args[$name] = 'required';
                    $args[$name.'.*'] = $rules;
                    $this->customMessages[$name.'.required'] = 'The '.$field->name.' is required.';
                } else {
                    $args[$name] = $rules;
                }

                // a field-level custom error message overrides every generated
                // message for that field (required/email/min/etc.)
                if (!empty($field->error_message)) {
                    foreach (array_keys($this->customMessages) as $key) {
                        if (str_starts_with($key, $name.'.')) {
                            $this->customMessages[$key] = $field->error_message;
                        }
                    }
                }
            }

            // gibberish anti-spam — the field class declares whether it applies
            // (Text/Textarea), still honouring the per-field settings opt-out
            if ($instance && $instance->wantsGibberish()
                && (!isset($field->settings->gibberish_check) || $field->settings->gibberish_check !== false)) {
                $args[$name] = array_merge(
                    (array) ($args[$name] ?? []),
                    [new Gibberish($field->name)]
                );
            }
        }

        // form-level rules (no field): honeypot + invisible reCAPTCHA v3
        $args['hname'] = 'honeypot';
        $args['htime'] = 'required|honeytime:5';

        if ($form->recaptcha) {
            $args['_captcha'] = ['required', new ReCaptcha('submit')];
            $this->customMessages['_captcha.required'] = 'Robot Detected';
        }

        return $args;
    }

    /**
     * Back-compat shim for custom (type 20) host classes that still expose the
     * old getValidationRules() contract instead of rules()/messages(). Pulls
     * their rules and registers their messages. Built-in classes return [].
     */
    protected function customFieldRules($field, $instance): array
    {
        if ($field->form_field_type_id != 20 || !method_exists($instance, 'getValidationRules')) {
            return [];
        }

        $validationRules = $instance->getValidationRules();
        $rules = [];

        if (isset($validationRules->required) && sizeof($validationRules->required)) {
            foreach ($validationRules->required as $rule) {
                $rules[] = $rule;
            }
        }

        if (isset($validationRules->messages) && sizeof($validationRules->messages)) {
            foreach ($validationRules->messages as $rule => $message) {
                $this->customMessages[$field->field_name.'.'.$rule] = $message;
            }
        }

        return $rules;
    }


    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return $this->customMessages;
    }
}

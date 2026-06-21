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
        $args = null;
        $form = $this->route('form');
        $skip = config('form-builder.skip_validation');

        if (isset($form->fields) && $form->fields && $form->fields->count()) {
            $args = [];
            $data = $this->all();
            foreach ($form->fields as $field) {
                if ($field->required) {
                    // skip fields that don't require validate, but may be marked as such
                    if (in_array($field->form_field_type_id, $skip)) {
                        continue;
                    }

                    // skip fields hidden by their conditional logic (Phase 4)
                    if (ConditionEvaluator::isHidden($field, $data)) {
                        continue;
                    }
                    $required = ['required'];
                    $this->customMessages[$field->field_name.'.required'] = 'The '.$field->name.' field is required.';

                    switch ($field->form_field_type_id) {
                        case 8:
                            $required[] = 'email';
                            $this->customMessages[$field->field_name.'.email'] = 'The '.$field->name.' must be a valid email address.';
                            break;
                        case 10:
                            $required[] = 'min:5';
                            $this->customMessages[$field->field_name.'.min'] = 'The '.$field->name.' must be at least :min characters';
                            break;
                        case 11:
                            $required[] = 'confirmed';
                            $required[] = 'min:5';
                            $this->customMessages[$field->field_name.'.confirmed'] = 'The '.$field->name.' fields does not match.';
                            $this->customMessages[$field->field_name.'.min'] = 'The '.$field->name.' must be at least :min characters';

                            $args[$field->field_name.'_confirmation'] = ['required','min:5'];
                            $this->customMessages[$field->field_name.'_confirmation.required'] = 'The Confirm '.$field->name.' field is required.';
                            $this->customMessages[$field->field_name.'_confirmation.min'] = 'The Confirm '.$field->name.' must be at least :min characters.';
                            break;
                        case 14:
                            $required[] = ['not0'];
                            $this->customMessages[$field->field_name.'.not0'] = 'The '.$field->name.' field is required.';
                            break;
                        case 17:
                            $required[] = 'mimes:'.config('form-builder.accepted_mime_types');
                            $this->customMessages[$field->field_name.'.mimes'] = 'The '.$field->name.' is an invalid file type.';
                            break;
                        case 18:
                            $required[] = 'mimes:'.config('form-builder.accepted_mime_types');
                            $this->customMessages[$field->field_name.'.*.mimes'] = 'The '.$field->name.' is an invalid file type.';
                            if(isset($this->customMessages[$field->field_name.'.*.required'])) {
                                unset($this->customMessages[$field->field_name.'.*.required']);
                                $this->customMessages[$field->field_name.'.*.required'] = 'The '.$field->name.' is required.';
                            }
                            break;
                    }

                    if ($field->form_field_type_id == 20 && $field->custom_field_class) {
                        $model = forms()->getFieldClass($field);
                        $validationRules = $model->getValidationRules();
                        if (isset($validationRules->required) && sizeof($validationRules->required)) {
                            foreach ($validationRules->required as $rule) {
                                $required[] = $rule;
                            }
                        }

                        if (isset($validationRules->messages) && sizeof($validationRules->messages)) {
                            foreach ($validationRules->messages as $rule => $message) {
                                $this->customMessages[$field->field_name.'.'.$rule] = $message;
                            }
                        }
                    }

                    // add the required states to the args
                    if ($field->form_field_type_id == 18) {
                        $args[$field->field_name] = 'required';
                        $args[$field->field_name.'.*'] = $required;
                        $this->customMessages[$field->field_name.'.required'] = 'The '.$field->name.' is required.';
                    } else {
                        $args[$field->field_name] = $required;
                    }

                    // a field-level custom error message overrides every generated
                    // message for that field (required/email/min/etc.)
                    if (!empty($field->error_message)) {
                        foreach (array_keys($this->customMessages) as $key) {
                            if (str_starts_with($key, $field->field_name.'.')) {
                                $this->customMessages[$key] = $field->error_message;
                            }
                        }
                    }

                }

                // gibberish anti-spam on Text (1) and Textarea (2), unless the
                // field opts out via settings.gibberish_check === false, and unless
                // the field is conditionally hidden
                if (in_array($field->form_field_type_id, [1, 2])
                    && (!isset($field->settings->gibberish_check) || $field->settings->gibberish_check !== false)
                    && !ConditionEvaluator::isHidden($field, $data ?? $this->all())) {
                    $args[$field->field_name] = array_merge(
                        (array) ($args[$field->field_name] ?? []),
                        [new Gibberish($field->name)]
                    );
                }
            }

            // add in the honeypot
            $args['hname']  = 'honeypot';
            $args['htime']  = 'required|honeytime:5';

            // invisible reCAPTCHA v3 (score-based)
            if($form->recaptcha) {
                $args['_captcha']  = ['required', new ReCaptcha('submit')];
                $this->customMessages['_captcha.required']  = 'Robot Detected';
            }
        }


        // return the results to set for validation
        return $args;
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

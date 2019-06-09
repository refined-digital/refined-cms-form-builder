<?php

namespace RefinedDigital\FormBuilder\Module\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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

        if (isset($form->fields) && $form->fields && $form->fields->count()) {
            $args = [];
            foreach ($form->fields as $field) {
                if ($field->required) {
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

                    // todo: add required check for custom fields

                    // add the required states to the args
                    if ($field->form_field_type_id == 18) {
                        $args[$field->field_name] = 'required';
                        $args[$field->field_name.'.*'] = $required;
                        $this->customMessages[$field->field_name.'.required'] = 'The '.$field->name.' is required.';
                    } else {
                        $args[$field->field_name] = $required;
                    }

                }
            }

            // add in the honeypot
            $args['hname']  = 'honeypot';
            $args['htime']  = 'required|honeytime:5';

            // check for captcha
            // todo: do the real captcha validation
            if($form->recaptcha) {
                $args['g-recaptcha-response']  = 'required';
                $this->customMessages['g-recaptcha-response.required']  = 'You must confirm you are not a Robot';
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

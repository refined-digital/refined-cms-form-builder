<?php

namespace RefinedDigital\FormBuilder\Module\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormBuilderRequest extends FormRequest
{
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

        $args = [
            'name'                  => ['required' => 'required', 'unique' => 'unique:forms,name'],
            'subject'               => ['required' => 'required'],
            'email_to'              => ['required' => 'required', 'email' => 'email'],
            'message'               => ['required' => 'required'],
            'confirmation'          => ['required' => 'required'],
            'cc'                    => ['nullable', 'email' => 'email'],
            'bcc'                   => ['nullable', 'email' => 'email'],
        ];

        // remove the fields that are not required to store into a model
        if (request()->has('form_action')) {
            if (request()->get('form_action') == 3) {
                unset($args['subject']);
                unset($args['email_to']);
                unset($args['message']);
                unset($args['reply_to']);
                unset($args['cc']);
                unset($args['bcc']);
                $args['model'] = ['required' => 'required'];
            }

            if (request()->get('form_action') == 2) {
                $args['callback'] = ['required' => 'required'];
            }
        }

        // add the id signinfier to stop the record from over riding the current record
        if ($this->method() == 'PUT' || $this->method() == 'PATCH') {
            $args['name']['unique'] .= ','.$this->route('form_builder');
        }

        // add the email check to the reply to, but only if we have text to validate
        if (request()->get('reply_to_type') == 'text') {
            $args['reply_to'] = ['email' => 'email'];
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
        return [
            'message.required'          => 'The email message field is required.',
            'confirmation.required'     => 'The on screen message field is required.',
            'reply_to.email'            => 'The reply to field must be a valid email address.',
            'model.required'            => 'The model to save to field is required.',
        ];
    }
}

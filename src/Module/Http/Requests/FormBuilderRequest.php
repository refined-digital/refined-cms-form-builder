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
        // Forms are created with just a name, then configured in the visual editor
        // (fields, behaviour, notifications, integrations, settings) which persists
        // everything via the JSON API. Recipients/messages now live on
        // notifications, so only the name is validated here.
        $args = [
            'name' => ['required' => 'required', 'unique' => 'unique:forms,name'],
        ];

        // don't let an update collide with the record's own name
        if ($this->method() == 'PUT' || $this->method() == 'PATCH') {
            $args['name']['unique'] .= ','.$this->route('form_builder');
        }

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
            'name.required' => 'The form name is required.',
            'name.unique'   => 'A form with this name already exists.',
        ];
    }
}

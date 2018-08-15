<?php

namespace RefinedDigital\FormBuilder\Module\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormFieldRequest extends FormRequest
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
            'name'                      => ['required' => 'required'],
            'form_field_type_id'        => ['required' => 'required', 'not0' => 'not0'],
        ];

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
            'form_field_type_id.required'       => 'The field type field is required.',
            'form_field_type_id.not0'           => 'The field type field is required.',
        ];
    }
}

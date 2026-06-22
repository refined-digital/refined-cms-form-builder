<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_File extends FormField {

    public function htmlAttributes(): array
    {
        $args = parent::htmlAttributes();

        $accept = $this->acceptedFileTypes($this->field->settings);
        if ($accept) {
            $args['accept'] = $accept;
        }

        // ponytail: legacy getAttributesAttribute had no break after case 17, so
        // single File also got the multiple-files class + multiple attr. preserved
        // for byte-stable output; drop both lines if that fall-through was a bug.
        $args['class'] .= ' form__control--multiple-files';
        $args['multiple'] = 'multiple';

        return $args;
    }

    public function rules(): array
    {
        return ['mimes:'.config('form-builder.accepted_mime_types')];
    }

    public function messages(): array
    {
        return ['mimes' => 'The '.$this->field->name.' is an invalid file type.'];
    }

    protected function acceptedFileTypes($settings): string
    {
        $images = 'image/*';
        $files = 'application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.zip,.7zip';

        if (isset($settings->file_types)) {
            if ($settings->file_types == 'image') {
                return $images;
            }
            if ($settings->file_types == 'document') {
                return $files;
            }
            if ($settings->file_types == 'image_document') {
                return $images.','.$files;
            }
        }

        return $images.','.$files;
    }

    public function render()
    {
        return <<<'blade'
{!!
    html()
        ->input('file', $field->field_name, $value)
        ->attributes($field->attributes)        
!!}    

@php
    $maxFileSize = isset($field->settings->max_file_size) ? $field->settings->max_file_size : 2;
@endphp

@if($maxFileSize)
    <p class="form__note">
        Max file size of: {{ $maxFileSize }}MB
    </p>
@endif
@section('scripts')
<script>
const field = document.querySelector('#{{$field->attributes['id']}}');
if (field) {
    field.addEventListener('change', function () {
        const maxFileSize = {{ $maxFileSize }};
        if (field.files.length > 0) {
            const fileSize = field.files.item(0).size;
            const fileMb = fileSize / 1024 ** 2;
            if (fileMb >= maxFileSize) {
                alert(`Please select a file less than ${maxFileSize}MB.`);
                field.value = '';
            } 
        }
    })
}
</script>
@append
blade;
    }

}

<?php

namespace RefinedDigital\FormBuilder\Module\Fields;

class FormField_File extends FormField {

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

@if(isset($field->settings->max_file_size) && $field->settings->max_file_size)
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

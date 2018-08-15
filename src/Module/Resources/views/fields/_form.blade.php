@include('core::pages._form')

@section('scripts')
<script>
    <?php
        $type = 0;
        if (isset($data->form_field_type_id)) {
            $type = $data->form_field_type_id;
        }
        if (old('form_field_type_id')) {
            $type = old('form_field_type_id');
        }
    ?>
    window.app.form.field.type = '{{ $type }}';
</script>
@append
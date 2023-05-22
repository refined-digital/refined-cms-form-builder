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

        $labelPosition = 1;
        if (isset($data->label_position)) {
            $labelPosition = $data->label_position;
        }
        if (old('label_position')) {
            $labelPosition = old('label_position');
        }
    ?>
    window.app.form.field.type = '{{ $type }}';
    window.app.form.labelPosition = '{{ $labelPosition }}'
</script>
@append
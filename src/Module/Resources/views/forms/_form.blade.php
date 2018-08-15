@include('core::pages._form')


@section('scripts')
<script>
    <?php
        $formAction = 1;
        if (isset($data->form_action)) {
            $formAction = $data->form_action;
        }
        if (old('form_action')) {
            $formAction = old('form_action');
        }

        $receipt = 0;
        if (isset($data->receipt)) {
            $receipt = $data->receipt;
        }
        if (old('receipt')) {
            $receipt = old('receipt');
        }

        $reply = 0;
        if (isset($data->reply_to)) {
            if (is_numeric($data->reply_to)) {
                $reply = $data->reply_to;
            } else {
                if ($data->reply_to) {
                    $reply = 'text';
                }
            }
        }
        if (old('reply_to_type')) {
            $reply = old('reply_to_type');
        }

    ?>
    window.app.form.action = '{{ $formAction }}';
    window.app.form.receipt = '{{ $receipt }}';
    window.app.form.reply = '{{ $reply }}';
</script>
@append
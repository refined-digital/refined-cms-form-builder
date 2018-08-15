@if (isset($errors) && count($errors) > 0)
    <div class="alert-holder">
        <div class="alert">
            <h4>You have some errors in your form.</h4>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if (session()->has('complete'))
    {!! $form->confirmation !!}
@else

    <div class="form">
        {!!
            html()
                ->form('POST', $args->route)
                ->attributes($args->attributes)
                ->open()
        !!}
            @if ($form->fields && $form->fields->count())
                <?php // do the hidden fields ?>
                @foreach ($form->fields as $field)
                    @if ($field->form_field_type_id == 12 && view()->exists($field->view))
                        @include($field->view)
                    @endif
                @endforeach

                <?php // do the standard fields ?>
                @foreach ($form->fields as $field)
                    @if ($field->form_field_type_id != 12)
                        @if ($field->form_field_type_id == 11)
                            {!! view('formBuilder::front-end.elements.row', ['field' => $field, 'errors' => $errors]) !!}
                            <?php $field->name = 'Confirmation'; ?>
                            {!! view('formBuilder::front-end.elements.row', ['field' => $field, 'errors' => $errors]) !!}
                        @else
                            {!! view('formBuilder::front-end.elements.row', ['field' => $field, 'errors' => $errors]) !!}
                        @endif
                    @endif
                @endforeach
            @endif

            @if($form->recaptcha)
                @if(env('RECAPTCHA_KEY') == '')
                    <div class="required">ReCaptcha needs to be configured</div>
                @endif
                <div class="form__row form__row--captcha">
                    <div
                        class="g-recaptcha"
                        data-sitekey="{{ env('RECAPTCHA_KEY') }}"
                        {!! $form->recaptcha == 2 ? 'data-size="invisible" data-callback="submitForm'.$form->id.'"' : '' !!}
                    ></div>
                </div>
            @endif

            <div class="form__row form__row--buttons">
                {!! Honeypot::generate('hname', 'htime') !!}
                <button class="button">{{ $form->submitText or 'Submit' }}</button>
            </div>

        {!! html()->form()->close() !!}
    </div><!-- / form -->

@endif


@section('scripts')
<script src="{{ mix('/js/FormBuilder.js', '/vendor/refinedcms') }}"></script>
        <script>
            let form = document.querySelector('.form--{{ $form->id }}');
            let validate = new window.FormValidate();
    @if($form->recaptcha == 2)
        let formSubmitted{{ $form->id }} = false;
            function submitForm{{ $form->id }}() {
                formSubmitted{{ $form->id }} = true;
                form.submit();
            }
    @endif
        form.addEventListener('submit', function submit(e) {
                let errors = validate.validate(this);
                if (errors.length) {
                    e.preventDefault();
                    validate.alert();
                }
            @if($form->recaptcha == 2)
    else {
                    if (!formSubmitted{{ $form->id }}) {
                        e.preventDefault();
                        grecaptcha.execute();
                    }
                }
            @endif
});
        </script>
@if($form->recaptcha)
        <script src="//www.google.com/recaptcha/api.js" async defer></script>
@endif
@append
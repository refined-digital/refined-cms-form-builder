@section('scripts')
<script src="{{ mix('/js/FormBuilder.js', '/vendor/refined/core') }}"></script>
<script>
  const form{{$form->id}} = document.querySelectorAll('.form--{{ $form->id }}');
  const validate{{$form->id}} = new window.FormValidate();
@if($form->recaptcha)
  let formSubmitted{{ $form->id }} = false;

  const submitForm{{$form->id}} = (form) => {
    formSubmitted{{ $form->id }} = true;
    form.submit();
  }

@endif
  form{{$form->id}}.forEach(form => {
    @if($form->recaptcha)
const tokenField = form.querySelector('input[name="_captcha"]');

    @endif
form.addEventListener('submit', function (e) {
      const submitButton = form.querySelector('.form__row--buttons .button');
      const errors{{$form->id}} = validate{{$form->id}}.validate(this);

      if (submitButton) {
        submitButton.classList.add('button--loading');
      }

      if (errors{{$form->id}}.length) {
        e.preventDefault();
        validate{{$form->id}}.alert();

        if (submitButton) {
          submitButton.classList.remove('button--loading');
        }
      }

      @yield('form-submit-injection')

      @if($form->recaptcha)
else {
        if (!formSubmitted{{ $form->id }} && tokenField) {
          e.preventDefault();

          grecaptcha.ready(function() {
            grecaptcha
                  .execute('{{ env('RECAPTCHA_SITE_KEY') }}', { action: 'submit' })
                  .then(function(token) {
                    tokenField.value = token;
                    submitForm{{ $form->id }}(form);
                  })
            ;
          });
        }
      }
      @endif
    });
  });
</script>

@if($form->recaptcha)
  <script src="//www.google.com/recaptcha/api.js?render={{ env('RECAPTCHA_SITE_KEY') }}" async defer></script>
@endif
@append

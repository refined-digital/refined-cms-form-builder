@if($form->recaptcha)
  <div class="form__row form__row--captcha">
    @if(env('RECAPTCHA_KEY') == '')
      <div class="required">ReCaptcha needs to be configured</div>
    @endif
    <div
      class="g-recaptcha"
      data-sitekey="{{ env('RECAPTCHA_KEY') }}"
      {!! $form->recaptcha == 2 ? 'data-size="invisible" data-callback="submitForm'.$form->id.'"' : '' !!}
    ></div>
  </div>
@endif

@if($form->recaptcha)
  <div class="form__row form__row--captcha">
    @if(env('RECAPTCHA_SITE_KEY') == '')
      <div class="required">ReCaptcha needs to be configured</div>
    @else
      <input type="hidden" name="_captcha" />
    @endif
  </div>
@endif

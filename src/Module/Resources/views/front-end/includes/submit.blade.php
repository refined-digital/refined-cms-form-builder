<div class="form__form-error" data-fb-form-error hidden role="alert" aria-live="assertive"></div>

<div class="form__row form__row--buttons">
  {!! Honeypot::generate('hname', 'htime') !!}
  {{-- aria-disabled, not disabled: the click must still reach the submit handler
       so invalid fields get their validation messages (and with JS off the form
       still posts to the server, which validates anyway) --}}
  <button type="submit" class="button button--disabled" data-fb-submit aria-disabled="true">
    <span class="form-button__text">
      {!! $form->submitText !!}
    </span>
    <span class="form-button__loading">
      <span class="form-button__loading-icon">
        <svg class="loader-spinner" viewBox="0 0 32 32">
          <circle
            class="loader-spinner__path"
            cx="16"
            cy="16"
            r="14"
            fill="none"
            stroke-width="4"
          ></circle>
        </svg>
      </span>
    </span>
  </button>
</div>

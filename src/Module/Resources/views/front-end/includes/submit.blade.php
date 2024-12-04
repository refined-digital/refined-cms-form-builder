<div class="form__row form__row--buttons">
  {!! Honeypot::generate('hname', 'htime') !!}
  <button class="button">
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
      <span class="form-button__loading-text">{{ $form->loadingText }}</span>
    </span>
  </button>
</div>

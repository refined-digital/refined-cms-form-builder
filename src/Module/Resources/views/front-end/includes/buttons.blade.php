<div class="form__fields form__fields--buttons">
  @include('formBuilder::front-end.includes.captcha')
  {!! $fields->pages ? '<button type="button" class="button form__page-back" data-fb-page-back>Back</button><button type="button" class="button form__page-next" data-fb-page-next>Next</button>' : '' !!}@include('formBuilder::front-end.includes.submit')
{!! $form->autosave ? '<p class="form__autosave-note">Your answers are saved on this device for '.config('form-builder.autosave_days', 30).' days, or until you submit. <a href="#" class="form__start-over" data-fb-start-over>Start over</a></p>' : '' !!}</div>

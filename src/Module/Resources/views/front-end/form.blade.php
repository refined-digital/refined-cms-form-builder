@include('formBuilder::front-end.includes.errors')
@include('formBuilder::front-end.includes.message')

@if (session()->has('complete') && session()->has('form') && session()->get('form')->id == $form->id)
  {!! $form->confirmation !!}
@else
  <div class="form">
    @include('formBuilder::front-end.includes.opener')

    {{-- hidden section: hidden fields + integration-injected hidden inputs --}}
    @include('formBuilder::front-end.includes.fields')
    @if (!empty($integrationHidden))
      <div class="form__fields--integration-hidden">{!! $integrationHidden !!}</div>
    @endif

    {{-- integration-injected visible UI (e.g. payment card) sits before submit --}}
    @if (!empty($integrationVisible))
      <div class="form__fields form__fields--integration">{!! $integrationVisible !!}</div>
    @endif

    @include('formBuilder::front-end.includes.buttons')
    @include('formBuilder::front-end.includes.closer')
  </div><!-- / form -->
@endif

@php
  if (!session()->has('loaded_forms')) {
    session()->put('loaded_forms', []);
  }
@endphp

@if (!in_array($form->id, session()->get('loaded_forms')))
  @php
    session()->push('loaded_forms', $form->id);
  @endphp

  @include('formBuilder::front-end.includes.scripts')
  @include('formBuilder::front-end.includes.styles')
@endif


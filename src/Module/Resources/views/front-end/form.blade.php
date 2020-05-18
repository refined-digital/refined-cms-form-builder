@include('formBuilder::front-end.includes.errors')

@if (session()->has('complete') && session()->has('form') && session()->get('form')->id == $form->id)
  {!! $form->confirmation !!}
@else
  <div class="form">
    @include('formBuilder::front-end.includes.opener')
    @include('formBuilder::front-end.includes.fields')
    @if ($hasPayments)
      @include('formBuilder::front-end.includes.payment-gateways')
    @endif
    @include('formBuilder::front-end.includes.captcha')
    @include('formBuilder::front-end.includes.submit')
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


@section('scripts')
<script src="{{ mix('/js/FormBuilder.js', '/vendor/refined/core') }}"></script>
@if($form->recaptcha)
  <script src="//www.google.com/recaptcha/api.js?render={{ env('RECAPTCHA_SITE_KEY') }}" async defer></script>
@endif
@append

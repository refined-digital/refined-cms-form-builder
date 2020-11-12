@if (session()->has('message'))
  <div class="alert-holder">
    <div class="alert alert--success">{!! session()->get('message') !!}</div>
  </div>
@endif

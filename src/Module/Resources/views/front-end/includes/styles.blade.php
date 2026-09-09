{{-- Pushed into the layout's yield('styles') (appended, so it adds to
     whatever the page already set) rather than echoed under the form. The
     container flag dedupes across multiple forms on the page (even separate
     forms()->render() calls). --}}
@section('styles')
@if (!app()->bound('__fb_front_css'))
    @php app()->instance('__fb_front_css', true); @endphp
    <link rel="stylesheet" href="{{ refined_asset('vendor/refined/form-builder/css/form.css') }}">
@endif
@append

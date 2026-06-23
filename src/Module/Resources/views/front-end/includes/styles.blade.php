{{-- Load the form's front-end CSS inline so styling (validation states, the
     password checklist, loading spinner, form-level error) is present however
     the form is rendered. The container flag dedupes across multiple forms on
     the page (even separate forms()->render() calls). --}}
@if (!app()->bound('__fb_front_css'))
    @php app()->instance('__fb_front_css', true); @endphp
    <link rel="stylesheet" href="{{ refined_asset('vendor/refined/form-builder/css/form.css') }}">
@endif

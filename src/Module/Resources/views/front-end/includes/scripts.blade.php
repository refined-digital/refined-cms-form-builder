{{-- Load the form's front-end JS inline so the form works regardless of how
     it's rendered (a CMS Page, a custom blade, etc.). The container flag dedupes
     across multiple forms on the page (even separate forms()->render() calls,
     which @once can't see across). PageRepository may also enqueue it on
     Pages-module pages; the data-fb-init guard in the script makes a
     double-load harmless. --}}
@if (!app()->bound('__fb_front_js'))
    @php app()->instance('__fb_front_js', true); @endphp
    <script src="{{ refined_asset('vendor/refined/form-builder/js/form-builder-front-end.js') }}" defer></script>
@endif

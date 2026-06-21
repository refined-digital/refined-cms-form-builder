@extends('core::layouts.master')

@section('title', $heading)

@php
    // base url for the editor JSON API, with the form id baked in
    $apiBase = route('refined.form-builder.api.fields', $data->id);
    $apiBase = preg_replace('#/fields$#', '', $apiBase);
@endphp

@section('template')

<div class="app__content">
    <rd-fb-editor
        api-base="{{ $apiBase }}"
        :initial-form='@json($data)'
    ></rd-fb-editor>
</div>

@stop

@section('scripts')
    <link rel="stylesheet" href="{{ refined_asset('vendor/refined/form-builder/css/form-builder-admin.css?v='.uniqid()) }}"/>
    <script src="{{ refined_asset('vendor/refined/form-builder/js/form-builder-admin.js?v='.uniqid()) }}"></script>
@stop

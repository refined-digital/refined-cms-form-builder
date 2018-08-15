@extends('core::layouts.master')

@section('title', $heading)

@section('template')

<div class="app__content">

    <div class="form">
        {!!
            html()
                ->modelForm($data, 'PUT', route($routes->update, [$parent->id, $data->id]))
                ->attributes([
                    'id' => 'model-form',
                    'novalidate'
                ])
                ->open()
        !!}

        @if(view()->exists($prefix.'_form'))
            @include($prefix.'_form')
        @else
            @include('core::pages._form')
        @endif

        {!! html()->closeModelForm() !!}
    </div>

</div>
@stop

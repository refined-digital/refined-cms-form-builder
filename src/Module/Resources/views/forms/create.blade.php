@extends('core::layouts.master')

@section('title', $heading)

@section('template')
<div class="app__content">

    <div class="form">
        {!!
            html()
                ->modelForm($data, 'POST', $routes->store)
                ->attributes([
                    'id' => 'model-form',
                    'novalidate'
                ])
                ->open()
        !!}

        {{-- minimal create: name only. Saving redirects into the visual editor
             where everything else is configured (and autosaved). --}}
        <input type="hidden" name="action" value="save" id="form--submit"/>

        <div class="form__row">
            {!! html()->label('Name', 'name')->class('form__label') !!}
            {!!
                html()
                    ->text('name', old('name'))
                    ->attributes(['class' => 'form__control', 'id' => 'name', 'required'])
            !!}
            <p class="form__note">Give your form a name. You'll configure fields and settings next.</p>
        </div>

        {!! html()->closeModelForm() !!}
    </div>

</div>
@stop

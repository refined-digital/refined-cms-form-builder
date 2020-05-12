<div class="form__row form__row--{{ $field->id }}{{ $field->required ? ' form__row--required' : '' }}{{ $field->custom_class ? ' '.$field->custom_class : '' }}{{ isset($errors) && $errors->has($field->field_name) ? ' form__row--has-error' : '' }}"{!! $field->required ? ' data-required-label="'.$field->name.'"' : ' '!!}>

    @if ($field->show_label && $field->label_position == 1)
        @include('formBuilder::front-end.elements.label')
    @endif

    @if (view()->exists($field->view))
        @include($field->view)
    @endif

    @if ($field->show_label && $field->label_position == 0)
        @include('formBuilder::front-end.elements.label')
    @endif

    @if ($field->note)
        <div class="form__note">{{ $field->note }}</div>
    @endif
</div>


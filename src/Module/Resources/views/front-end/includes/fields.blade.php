@if ($fields->hidden->count())
  <div class="form__fields--hidden">
    @foreach ($fields->hidden as $field)
      @include($field->view)
    @endforeach
  </div>
@endif

@if ($fields->fields->count())
  <div class="form__fields--fields">
    @foreach ($fields->fields as $field)
      @if ($field->form_field_type_id == 11)
        {!! view('formBuilder::front-end.elements.row', ['field' => $field, 'errors' => $errors, 'defaultFields' => $form->defaultFields ?: []]) !!}
        <?php $field->name = 'Confirmation'; ?>
        {!! view('formBuilder::front-end.elements.row', ['field' => $field, 'errors' => $errors, 'defaultFields' => $form->defaultFields ?: []]) !!}
      @else
        {!! view('formBuilder::front-end.elements.row', ['field' => $field, 'errors' => $errors, 'defaultFields' => $form->defaultFields ?: []]) !!}
      @endif
    @endforeach
  </div>
@endif

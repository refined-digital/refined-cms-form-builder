@if ($form->fields && $form->fields->count())
  <?php // do the hidden fields ?>
  <div class="form__fields--hidden">
    @foreach ($form->fields as $field)
      @if ($field->form_field_type_id == 12 && view()->exists($field->view))
        @include($field->view)
      @endif
    @endforeach
  </div>

  <?php // do the standard fields ?>
  <div class="form__fields">
    @foreach ($form->fields as $field)
      @if ($field->form_field_type_id != 12)
        @if ($field->form_field_type_id == 11)
          {!! view('formBuilder::front-end.elements.row', ['field' => $field, 'errors' => $errors, 'defaultFields' => $form->defaultFields ?: []]) !!}
          <?php $field->name = 'Confirmation'; ?>
          {!! view('formBuilder::front-end.elements.row', ['field' => $field, 'errors' => $errors, 'defaultFields' => $form->defaultFields ?: []]) !!}
        @else
          {!! view('formBuilder::front-end.elements.row', ['field' => $field, 'errors' => $errors, 'defaultFields' => $form->defaultFields ?: []]) !!}
        @endif
      @endif
    @endforeach
  </div>
@endif

@if ($fields->hidden->count())
  <div class="form__fields--hidden">
    @foreach ($fields->hidden as $field)
      @if (str_contains('formBuilder::', $field->view) && view()->exists($field->view))
        @include($field->view)
      @else
        {!! $field->renderView($form->defaultFields, $selectFieldsOverride) !!}
      @endif
    @endforeach
  </div>
@endif

@if ($fields->fields->count())
  <div class="form__fields--fields">
    @foreach ($fields->fields as $field)
      {{-- structural fields (group start/end) render their own markup --}}
      @if ($field->is_structural)
        {!! $field->renderView($form->defaultFields, $selectFieldsOverride) !!}
        @php continue; @endphp
      @endif

      @php
        $rowData = [
          'field' => $field,
          'errors' => $errors,
          'defaultFields' => $form->defaultFields ?: [],
          'selectFieldsOverride' => $selectFieldsOverride
        ]
      @endphp
      @if ($field->form_field_type_id == 11)
        {!! view('formBuilder::front-end.elements.row', $rowData) !!}
        @php
          $rowData['field']->name = 'Confirmation';
        @endphp
        {!! view('formBuilder::front-end.elements.row', $rowData) !!}
      @else
        {!! view('formBuilder::front-end.elements.row', $rowData) !!}
      @endif
    @endforeach
  </div>
@endif

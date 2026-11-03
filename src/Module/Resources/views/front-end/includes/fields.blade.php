{{-- unpaged forms are one group with no wrapper, so their markup is unchanged --}}
@php $groups = $fields->pages ?: [(object) ['name' => '', 'fields' => $fields->fields]]; @endphp
@if ($fields->hidden->count())
  <div class="form__fields--hidden">
    @foreach ($fields->hidden as $field)
      @if (isset($field->html))
        {!! $field->html !!}
      @else
        {!! $field->renderView($form->defaultFields, $selectFieldsOverride) !!}
      @endif
    @endforeach
  </div>
@endif

@if ($fields->fields->count())
  <div class="form__fields--fields">
{!! $fields->pages ? '<p class="form__page-indicator" data-fb-page-indicator></p>' : '' !!}    @foreach ($groups as $index => $page){!! $fields->pages ? '<div class="form__page" data-fb-page="'.e($index).'" data-fb-page-name="'.e($page->name).'">' : '' !!}@foreach ($page->fields as $field)
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
      @if ($field->form_field_type_id == \RefinedDigital\FormBuilder\Module\Enums\FormFieldType::PASSWORD_CONFIRM->value)
        {!! view('formBuilder::front-end.elements.row', $rowData) !!}
        @php
          $rowData['field']->name = 'Confirmation';
        @endphp
        {!! view('formBuilder::front-end.elements.row', $rowData) !!}
      @else
        {!! view('formBuilder::front-end.elements.row', $rowData) !!}
      @endif
    @endforeach{!! $fields->pages ? '</div>' : '' !!}@endforeach
  </div>
@endif

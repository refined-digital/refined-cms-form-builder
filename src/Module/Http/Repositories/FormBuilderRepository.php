<?php

namespace RefinedDigital\FormBuilder\Module\Http\Repositories;

use RefinedDigital\CMS\Modules\Core\Http\Repositories\CoreRepository;
use RefinedDigital\CMS\Modules\Core\Http\Repositories\EmailRepository;
use RefinedDigital\FormBuilder\Module\Models\Form;
use RefinedDigital\FormBuilder\Module\Models\FormField;
use RefinedDigital\FormBuilder\Module\Models\FormFieldOption;
use RefinedDigital\FormBuilder\Module\Models\FormFieldType;

class FormBuilderRepository extends CoreRepository
{


    public function __construct()
    {
        $this->setModel('RefinedDigital\FormBuilder\Module\Models\Form');
    }

    public function getForSelect($type = false)
    {
        switch ($type) {
            case 'field types':
                return $this->getFormFieldTypesForSelect();
                break;
        }
    }

    public function getFormFieldTypesForSelect()
    {
        $types = FormFieldType::whereActive(1)
                        ->orderBy('position')
                        ->get();

        $data = [0 => 'Please Select'];

        if ($types && $types->count()) {
            foreach ($types as $t) {
                $data[$t->id] = $t->name;
            }
        }

        return $data;
    }

    public function storeField($request, $extraFields = [])
    {
        $field = $this->store($request, $extraFields);

        if (isset($field->id)) {
            // check if the options need to be stored
            $this->syncOptions($field->id, $request->get('options'));
        }

        return $field;
    }

    public function updateField($id, $request)
    {
        $field = $this->update($id, $request);

        if (isset($field->id)) {
            // check if the options need to be stored
            $this->syncOptions($field->id, $request->get('options'));
        }

        return $field;
    }

    protected function syncOptions($fieldId, $options)
    {
        // delete the form options
        FormFieldOption::whereFormFieldId($fieldId)->forceDelete();

        // now add any, if there is any
        if (is_array($options) && sizeof($options)) {
            foreach ($options as $option) {
                FormFieldOption::create([
                    'form_field_id' => $fieldId,
                    'value'         => $option['value'],
                    'label'         => $option['label'],
                ]);
            }
        }
    }

    public function duplicate($originalForm)
    {
        if (!isset($originalForm->id)) {
            return false;
        }

        $originalForm->name .= ' - DUPLICATE';
        $newForm = $this->store($originalForm->toArray());

        $repo = new FormBuilderRepository();
        $repo->setModel('RefinedDigital\FormBuilder\Module\Models\FormField');

        // grab the fields and create those
        if ($originalForm->fields->count()) {
            foreach ($originalForm->fields as $field) {
                $newField = $repo->store($field->toArray(), ['form_id' => $newForm->id]);
                if (is_array($field->options) && sizeof($field->options) && isset($newField->id)) {
                    $this->syncOptions($newField->id, $field->options);
                }
            }
        }

        return true;
    }

    public function export($form)
    {
        if (!isset($form->id)) {
            return false;
        }


        // grab the form submissions
        $repo = new EmailRepository();
        $submissions = $repo->getFormSubmissions($form->id);

        if ($submissions && $submissions->count()) {
            // set the headers
            $headers = ['id' => 'id', 'created_at' => 'created_at', 'to' => 'to', 'from' => 'from', 'ip' => 'ip'];
            $hide = [10,11,19];

            // add in the field titles
            if ($form->fields && $form->fields->count()) {
                foreach ($form->fields as $field) {
                    if (!in_array($field->form_field_type_id, $hide)) {
                        $headers[$field->field_name] = $field->name;
                    }
                }
            }

            $body = [];

            // add in the body
            foreach ($submissions as $entry) {
                if (isset($entry->data->data)) {
                    $row = $headers;
                    // clear out the fields
                    foreach($row as $k => $v) {
                        $row[$k] = null;
                    }

                    // set the initial headers
                    $row['id'] = $entry->id;
                    $row['created_at'] = $entry->created_at->format('Y-m-d H:i:s');
                    $row['to'] = $entry->to;
                    $row['from'] = $entry->from;
                    $row['ip'] = $entry->ip;

                    // add in the data
                    $fields = $repo->formatFields((array) $entry->data->data, $form);
                    foreach ($row as $fieldName => $fieldData) {
                        $key = str_replace('field', '', $fieldName);
                        if (isset($fields[$key])) {
                            $row[$fieldName] = preg_replace('#<br\s*?/?>#i', '', $fields[$key]->value);
                            $row[$fieldName] = str_replace(', ', PHP_EOL, $row[$fieldName]);
                        }
                    }


                    $body[] = $row;
                }
            }

            if (sizeof($headers) && sizeof($body)) {

                return ['headers' => $headers, 'body' => $body];
            }

        }

        return false;
    }

    public function getAllFields()
    {
        $id = request()->route('form_builder');

        return $this->model::
            whereFormId($id)
            ->keywords()
            ->order()
            ->paging()
        ;
    }

    public function getForTree()
    {
        $items = $this->model::whereActive(1)
                        ->orderBy('position','asc')
                        ->get();

        $data = [['name' => 'None', 'id' => 0]];
        if ($items && $items->count()) {
            foreach ($items as $item) {
                $data[] = ['name' => $item->name, 'id' => $item->id];
            }
        }

        return $data;
    }

    public function getFormById($id)
    {
        return $this->model::find($id);
    }

    public function getFormByName($name)
    {
        return $this->model::whereName($name)->get();
    }

    public function hasFilesField($form)
    {
        if ($form->fields && $form->fields->count()) {
            $ids = $form->fields->pluck('form_field_type_id');

            // file fields
            if ($ids->contains(18) || $ids->contains(19)) {
                return true;
            }

        }

        return false;
    }

    public function compileAndSend($request, $form)
    {
        $repo = new EmailRepository();
        $html = $repo->makeHtml($request, $form);
        $settings = $repo->settingsFromForm($form, $request);
        $settings->body = $html;
        $settings->form_id = $form->id;
        $settings->data = $repo->setDataForDB($request);
        $repo->send($settings);
    }


    public function destroy($id)
    {
        $fields = FormField::whereFormId($id)->get();
        if ($fields && $fields->count()) {
            foreach ($fields as $field) {
                FormFieldOption::whereFormFieldId($field->id)->delete();
                $field->delete();
            }
        }
        return parent::destroy($id);
    }
}
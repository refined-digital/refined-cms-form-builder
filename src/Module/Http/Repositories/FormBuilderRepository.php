<?php

namespace RefinedDigital\FormBuilder\Module\Http\Repositories;

use RefinedDigital\CMS\Modules\Core\Http\Repositories\CoreRepository;
use RefinedDigital\CMS\Modules\Core\Http\Repositories\EmailRepository;
use RefinedDigital\FormBuilder\Module\Models\Form;
use RefinedDigital\FormBuilder\Module\Models\FormField;
use RefinedDigital\FormBuilder\Module\Models\FormFieldOption;
use RefinedDigital\FormBuilder\Module\Models\FormFieldType;
use RefinedDigital\FormBuilder\Module\Models\FormEmailNotification;
use RefinedDigital\FormBuilder\Module\Models\FormIntegration;

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
            case 'forms':
                return $this->getFormsForSelect();
            case 'content forms':
                return $this->getFormsForContentSelect();
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

    public function getFormsForSelect()
    {
        $types = Form::orderBy('position')->get();

        $data = [0 => 'Please Select'];

        if ($types && $types->count()) {
            foreach ($types as $t) {
                $data[$t->id] = $t->name;
            }
        }

        return $data;
    }

    public function getFormsForContentSelect()
    {
        $types = Form::orderBy('position')->get();

        $data = [['label' => 'Please Select', 'value' => 0]];

        if ($types && $types->count()) {
            foreach ($types as $t) {
                $data[] = [
                    'label' => $t->name,
                    'value' => $t->id
                ];
            }
        }

        return $data;
    }

    public function storeField($request, $extraFields = [])
    {
        if(is_array($request)) {
            $data = $request;
            $options = $request['options'] ?? null;
        } else {
            $data = $request->all();
            $options = $request->get('options');
        }

        // Floating label (position 2) clears the placeholder text on save.
        if (isset($data['label_position']) && $data['label_position'] == 2) {
            $data['placeholder'] = ' ';
        }

        unset($data['options']);

        $field = $this->store($data, $extraFields);

        if (isset($field->id)) {
            // check if the options need to be stored
            $this->syncOptions($field->id, $options);
        }

        return $field;
    }

    public function updateField($id, $request)
    {
        if(is_array($request)) {
            $data = $request;
            $options = $request['options'] ?? null;
        } else {
            $data = $request->all();
            $options = $request->get('options');
        }

        // Floating label (position 2) clears the placeholder text on save.
        if (isset($data['label_position']) && $data['label_position'] == 2) {
            $data['placeholder'] = ' ';
        }

        unset($data['options']);

        $field = $this->update($id, $data);

        if (isset($field->id)) {
            // check if the options need to be stored
            $this->syncOptions($field->id, $options);
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

        // copy the email notifications (Phase 6)
        if ($originalForm->notifications->count()) {
            foreach ($originalForm->notifications as $notification) {
                $data = $notification->toArray();
                unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
                $data['form_id'] = $newForm->id;
                FormEmailNotification::create($data);
            }
        }

        // copy the integrations (Phase 7)
        if ($originalForm->integrations->count()) {
            foreach ($originalForm->integrations as $integration) {
                $data = $integration->toArray();
                unset($data['id'], $data['created_at'], $data['updated_at']);
                $data['form_id'] = $newForm->id;
                FormIntegration::create($data);
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
            $headers = [
              'id' => 'id',
              'created_at' => 'created_at',
              'to' => 'to',
              'from' => 'from',
              'ip' => 'ip'
            ];
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
        return $this->model::whereName($name)->first();
    }

    public function hasFilesField($form)
    {
        if ($form->fields && $form->fields->count()) {
            $ids = $form->fields->pluck('form_field_type_id');

            // file fields
            if ($ids->contains(17) || $ids->contains(18)) {
                return true;
            }

        }

        return false;
    }

    /**
     * Run all enabled integrations for the form. Honours halt-on-failure: if an
     * integration's process() returns a failure (success === false) or throws, we
     * return that failure so the caller aborts the submission (422). A null/success
     * return continues.
     *
     * @return object|null  failure result to abort, or null to continue
     */
    public function runIntegrations($request, $form)
    {
        $aggregate = app(\RefinedDigital\CMS\Modules\Core\Aggregates\FormBuilderIntegrationAggregate::class);

        foreach ($form->integrations()->where('enabled', true)->get() as $row) {
            $definition = $aggregate->get($row->integration_key);
            if (!$definition || empty($definition['processor'])) {
                continue;
            }

            $settings = [
                'enabled'    => $row->enabled,
                'send_email' => $row->send_email,
                'config'     => $row->config ?? [],
            ];

            try {
                $processor = app($definition['processor']);
                $result = $processor->process($request, $form, $settings);
            } catch (\Throwable $e) {
                return (object) ['success' => false, 'message' => $e->getMessage()];
            }

            // a failure result aborts everything downstream
            if (is_object($result) && isset($result->success) && $result->success === false) {
                return $result;
            }
            if (is_array($result) && array_key_exists('success', $result) && $result['success'] === false) {
                return (object) $result;
            }
        }

        return null;
    }

    /**
     * Notifications send unless an enabled integration has send_email = false.
     */
    public function shouldSendNotifications($form): bool
    {
        return !$form->integrations()
            ->where('enabled', true)
            ->where('send_email', false)
            ->exists();
    }

    /**
     * Send all active email notifications for the form. One EmailSubmission row is
     * stored per notification (preserves CSV export). Uses the core EmailRepository
     * (no custom mailer). Delivery is queued when configured.
     */
    public function compileAndSend($request, $form)
    {
        $repo = new EmailRepository();
        $queue = config('form-builder.queue_emails', true) && config('queue.default') !== 'sync';

        $notifications = $form->notifications()->where('active', 1)->get();

        // no configured notifications -> nothing to send
        if (!$notifications->count()) {
            return;
        }

        // the submitted values, stored on every EmailSubmission for CSV export
        // (export reads $entry->data->data keyed by field<id>)
        $data = is_array($request) ? $request : $request->all();

        foreach ($notifications as $notification) {
            $settings = new \stdClass();
            $settings->to = $notification->to;
            $settings->subject = $repo->replaceTokens($notification->subject, $request, $form);

            if ($notification->cc) {
                $settings->cc = $notification->cc;
            }
            if ($notification->bcc) {
                $settings->bcc = $notification->bcc;
            }

            // reply_to may reference an email-type field (field<id>) or be a literal
            $replyTo = $this->resolveReplyTo($notification->reply_to, $request, $form);
            if ($replyTo) {
                $settings->reply_to = $replyTo;
            }

            // attach any uploaded files
            $fileSettings = $repo->settingsFromForm($form, $request);
            if (isset($fileSettings->files)) {
                $settings->files = $fileSettings->files;
            }

            // body: the notification content with [[fields]] / [[field:id]] tokens
            $body = $notification->content ?? '';
            $body = str_replace(['[[fields]]', '{{fields}}'], $repo->generateHtml($request, $form), $body);
            $settings->body = $repo->replaceTokens($body, $request, $form);

            $settings->form_id = $form->id;
            $settings->data = $data;
            $settings->send_as_plain_text = false;

            $repo->send($settings, $queue);
        }
    }

    /**
     * Resolve a notification's reply_to: a literal email, or a field<id> reference
     * pointing at an email-type field whose submitted value is used.
     */
    protected function resolveReplyTo($replyTo, $request, $form)
    {
        if (!$replyTo) {
            return null;
        }

        if (str_starts_with($replyTo, 'field')) {
            $data = is_array($request) ? $request : $request->all();
            return $data[$replyTo] ?? null;
        }

        return $replyTo;
    }

    public function destroyField($id)
    {
        FormFieldOption::whereFormFieldId($id)->delete();
        return parent::destroy($id);

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

        // cascade soft-delete the notifications (Phase 6)
        FormEmailNotification::where('form_id', $id)->delete();

        // remove integration rows (Phase 7)
        FormIntegration::where('form_id', $id)->delete();

        return parent::destroy($id);
    }
}

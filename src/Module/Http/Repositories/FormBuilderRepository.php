<?php

namespace RefinedDigital\FormBuilder\Module\Http\Repositories;

use RefinedDigital\CMS\Modules\Core\Http\Repositories\CoreRepository;
use RefinedDigital\CMS\Modules\Core\Http\Repositories\EmailRepository;
use RefinedDigital\FormBuilder\Module\Models\Form;
use RefinedDigital\FormBuilder\Module\Models\FormField;
use RefinedDigital\FormBuilder\Module\Models\FormFieldOption;
use RefinedDigital\FormBuilder\Module\Models\FormFieldType;
use RefinedDigital\FormBuilder\Module\Enums\FormFieldType as FieldType;
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
        return match ($type) {
            'field types'   => $this->getFormFieldTypesForSelect(),
            'forms'         => $this->getFormsForSelect(),
            'content forms' => $this->getFormsForContentSelect(),
            default         => null,
        };
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
        if (is_array($options) && count($options)) {
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
                if (is_array($field->options) && count($field->options) && isset($newField->id)) {
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
            // match EmailRepository::formatFields skip list (passwords, static,
            // hidden, group start/end) so export columns line up with the data
            $hide = [FieldType::PASSWORD->value, FieldType::PASSWORD_CONFIRM->value, FieldType::HIDDEN->value, FieldType::STATIC->value, FieldType::GROUP_START->value, FieldType::GROUP_END->value];

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

            if (count($headers) && count($body)) {
                return ['headers' => $headers, 'body' => $body];
            }

        }

        return false;
    }

    /**
     * Submissions for a form, grouped into one entry per form-fill. A fill
     * writes one EmailSubmission row per active notification, all sharing a
     * `submission_group` token (legacy rows with no token group by their own id).
     * Returns a Collection of objects: token, created_at, count, summary, rows.
     */
    public function groupedSubmissions($form)
    {
        if (!isset($form->id)) {
            return collect();
        }

        $repo = new EmailRepository();
        $submissions = $repo->getFormSubmissions($form->id)->sortByDesc('created_at');

        $groups = [];
        foreach ($submissions as $entry) {
            $token = $entry->data->submission_group ?? ('id-'.$entry->id);

            if (!isset($groups[$token])) {
                $groups[$token] = (object) [
                    // `id` mirrors the token so the standard index blade's
                    // route(..., $d->id) resolves the {token} detail route
                    'id'           => $token,
                    'token'        => $token,
                    'created_at'   => $entry->created_at,
                    'submitted_at' => '', // pre-formatted (timezone-aware) below
                    'count'        => 0,
                    'count_label'  => '',
                    'summary'      => $this->submissionSummary($entry, $form),
                    'rows'         => [],
                ];
            }

            $groups[$token]->count++;
            $groups[$token]->rows[] = $entry;
            // keep the earliest timestamp for the group
            if ($entry->created_at < $groups[$token]->created_at) {
                $groups[$token]->created_at = $entry->created_at;
            }
        }

        $tz = config('form-builder.timezone');
        $format = config('form-builder.datetime_format', 'd/m/Y g:ia');
        foreach ($groups as $g) {
            // a friendly "N sent" label for the Notifications column
            $g->count_label = $g->count.' sent';
            // timezone-aware display string (the list column renders it as text)
            $g->submitted_at = $g->created_at->copy()->timezone($tz)->format($format);
        }

        return collect(array_values($groups))->sortByDesc('created_at')->values();
    }

    /**
     * A single submission group (one form-fill) with its formatted field
     * label/value pairs and the per-notification delivery details. Returns null
     * if the token isn't found for this form.
     */
    public function submissionGroup($form, $token)
    {
        $group = $this->groupedSubmissions($form)->firstWhere('token', $token);
        if (!$group) {
            return null;
        }

        $repo = new EmailRepository();
        $first = $group->rows[0];

        // label => value pairs (reuses the same formatter as the CSV export)
        $fields = isset($first->data->data)
            ? $repo->formatFields((array) $first->data->data, $form)
            : [];

        // per-notification delivery meta (one entry per row in the group)
        $notifications = [];
        foreach ($group->rows as $row) {
            $notifications[] = (object) [
                'name'       => $row->data->notification_name ?? null,
                'subject'    => $row->data->subject ?? null,
                'to'         => $row->to,
                'cc'         => $row->data->cc ?? null,
                'bcc'        => $row->data->bcc ?? null,
                'reply_to'   => $row->data->reply_to ?? null,
                'from'       => $row->from,
                'ip'         => $row->ip,
                'created_at' => $row->created_at,
            ];
        }

        return (object) [
            'token'         => $token,
            'created_at'    => $group->created_at,
            'fields'        => $fields,
            'notifications' => $notifications,
        ];
    }

    /**
     * Resolve the Form a submission group belongs to, from its token alone.
     * Tokens are globally-unique uuids (legacy rows use id-{n}); we find the
     * owning EmailSubmission and load its form. Returns null if not found.
     */
    public function findFormByToken($token)
    {
        $submission = \RefinedDigital\CMS\Modules\Core\Models\EmailSubmission::query()
            ->when(str_starts_with($token, 'id-'), function ($q) use ($token) {
                $q->where('id', (int) substr($token, 3));
            }, function ($q) use ($token) {
                $q->where('data', 'like', '%"submission_group":"'.$token.'"%');
            })
            ->first();

        if (!$submission || !$submission->form_id) {
            return null;
        }

        return \RefinedDigital\FormBuilder\Module\Models\Form::find($submission->form_id);
    }

    /** A short one-line summary of a submission (first couple of field values). */
    protected function submissionSummary($entry, $form)
    {
        if (!isset($entry->data->data)) {
            return '';
        }

        $repo = new EmailRepository();
        $fields = $repo->formatFields((array) $entry->data->data, $form);

        $parts = [];
        foreach ($fields as $field) {
            $value = trim(strip_tags((string) $field->value));
            if ($value !== '') {
                $parts[] = $value;
            }
            if (count($parts) >= 2) {
                break;
            }
        }

        return implode(' — ', $parts);
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
            if ($ids->contains(FieldType::FILE->value) || $ids->contains(FieldType::MULTIPLE_FILES->value)) {
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

        // one token per form-fill so the admin can group the per-notification
        // rows back into a single submission (no schema change — rides in `data`)
        $submissionGroup = (string) \Illuminate\Support\Str::uuid();

        foreach ($notifications as $notification) {
            // recipients may contain field<id> tokens — swap each for the
            // submitted value, dropping any that resolve empty
            $to = $this->resolveRecipients($notification->to, $request);

            // nothing to send to (e.g. the only recipient was an empty field)
            if ($to === '') {
                continue;
            }

            $settings = new \stdClass();
            $settings->submission_group = $submissionGroup;
            $settings->notification_name = $notification->name;
            $settings->to = $to;
            $settings->subject = $repo->replaceTokens($notification->subject, $request, $form);

            if ($notification->cc) {
                $cc = $this->resolveRecipients($notification->cc, $request);
                if ($cc !== '') {
                    $settings->cc = $cc;
                }
            }
            if ($notification->bcc) {
                $bcc = $this->resolveRecipients($notification->bcc, $request);
                if ($bcc !== '') {
                    $settings->bcc = $bcc;
                }
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

            // branding for the email template (keeps core decoupled from this config)
            $settings->email_accent = config('form-builder.email.accent_colour');
            $settings->email_logo = config('form-builder.email.logo_url');

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

    /**
     * Resolve a comma-delimited recipient list (to/cc/bcc) that may mix literal
     * email addresses with field<id> tokens. Each token is swapped for the
     * submitted value; empty/unresolved entries are dropped. Returns a clean
     * comma-delimited string (empty string if nothing resolves).
     */
    protected function resolveRecipients($list, $request)
    {
        if (!$list) {
            return '';
        }

        $data = is_array($request) ? $request : $request->all();
        $out = [];

        foreach (explode(',', $list) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }

            // field<id> token -> the submitted value (skip if blank/missing)
            if (preg_match('/^field\d+$/', $entry)) {
                $value = trim((string) ($data[$entry] ?? ''));
                if ($value !== '') {
                    $out[] = $value;
                }
                continue;
            }

            $out[] = $entry;
        }

        return implode(',', $out);
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

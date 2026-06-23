<?php

namespace RefinedDigital\FormBuilder\Module\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RefinedDigital\FormBuilder\Module\Http\Repositories\FormBuilderRepository;
use RefinedDigital\FormBuilder\Module\Models\Form;
use RefinedDigital\FormBuilder\Module\Models\FormField;
use RefinedDigital\FormBuilder\Module\Models\FormFieldType;
use RefinedDigital\FormBuilder\Module\Models\FormEmailNotification;
use RefinedDigital\FormBuilder\Module\Models\FormIntegration;
use RefinedDigital\CMS\Modules\Core\Aggregates\FormBuilderIntegrationAggregate;

/**
 * JSON API backing the visual editor (Phase 1+). All routes are registered under
 * the admin middleware group (web/auth/userLevel/admin) via routes.php, so these
 * are session-authenticated and CSRF-protected.
 */
class FormBuilderApiController extends Controller
{
    protected readonly FormBuilderRepository $repository;

    /**
     * Palette grouping + icons for each field-type id. Drives FieldPalette.vue.
     */
    protected array $palette = [
        // Basic
        1  => ['group' => 'Basic',    'icon' => 'fa-font'],
        2  => ['group' => 'Basic',    'icon' => 'fa-align-left'],
        8  => ['group' => 'Basic',    'icon' => 'fa-envelope'],
        9  => ['group' => 'Basic',    'icon' => 'fa-phone'],
        7  => ['group' => 'Basic',    'icon' => 'fa-hashtag'],
        // Option
        4  => ['group' => 'Option',   'icon' => 'fa-dot-circle'],
        5  => ['group' => 'Option',   'icon' => 'fa-check-square'],
        3  => ['group' => 'Option',   'icon' => 'fa-caret-square-down'],
        6  => ['group' => 'Option',   'icon' => 'fa-check'],
        13 => ['group' => 'Option',   'icon' => 'fa-toggle-on'],
        14 => ['group' => 'Option',   'icon' => 'fa-globe'],
        // Advanced
        15 => ['group' => 'Advanced', 'icon' => 'fa-calendar'],
        16 => ['group' => 'Advanced', 'icon' => 'fa-calendar-alt'],
        17 => ['group' => 'Advanced', 'icon' => 'fa-upload'],
        18 => ['group' => 'Advanced', 'icon' => 'fa-upload'],
        10 => ['group' => 'Advanced', 'icon' => 'fa-key'],
        11 => ['group' => 'Advanced', 'icon' => 'fa-key'],
        12 => ['group' => 'Advanced', 'icon' => 'fa-eye-slash'],
        21 => ['group' => 'Advanced', 'icon' => 'fa-birthday-cake'],
        19 => ['group' => 'Advanced', 'icon' => 'fa-align-justify'],
        20 => ['group' => 'Advanced', 'icon' => 'fa-puzzle-piece'],
        22 => ['group' => 'Advanced', 'icon' => 'fa-object-group'],
        23 => ['group' => 'Advanced', 'icon' => 'fa-object-ungroup'],
    ];

    public function __construct()
    {
        $this->repository = new FormBuilderRepository();
        $this->repository->setModel(FormField::class);
    }

    /**
     * The field-type palette: id, name, group, icon. Ordered by palette group.
     */
    public function fieldTypes()
    {
        $types = FormFieldType::whereActive(1)->orderBy('position')->get();

        $groupOrder = ['Basic' => 0, 'Option' => 1, 'Advanced' => 2];

        $data = $types->map(function ($type) {
            $meta = $this->palette[$type->id] ?? ['group' => 'Advanced', 'icon' => 'fa-square'];

            return [
                'id'    => $type->id,
                'name'  => $type->name,
                'group' => $meta['group'],
                'icon'  => $meta['icon'],
            ];
        })->sortBy([
            fn ($a, $b) => ($groupOrder[$a['group']] ?? 99) <=> ($groupOrder[$b['group']] ?? 99),
        ])->values();

        return response()->json(['data' => $data]);
    }

    /**
     * All fields for a form, ordered by position, with options + computed attrs.
     */
    public function fields(Form $form)
    {
        $fields = FormField::whereFormId($form->id)
            ->orderBy('position')
            ->get();

        return response()->json(['data' => $fields]);
    }

    public function storeField(Request $request, Form $form)
    {
        $data = $this->fieldData($request);
        $data['position'] = (int) FormField::whereFormId($form->id)->max('position') + 1;

        $field = $this->repository->storeField($data + ['options' => $request->input('options', [])], ['form_id' => $form->id]);

        return response()->json(['data' => FormField::find($field->id)], 201);
    }

    public function updateField(Request $request, Form $form, $fieldId)
    {
        $field = FormField::whereFormId($form->id)->findOrFail($fieldId);

        $data = $this->fieldData($request);
        $data['options'] = $request->input('options', []);

        $this->repository->updateField($field->id, $data);

        return response()->json(['data' => FormField::find($field->id)]);
    }

    public function destroyField(Form $form, $fieldId)
    {
        $field = FormField::whereFormId($form->id)->findOrFail($fieldId);
        $this->repository->destroyField($field->id);

        return response()->json(['data' => ['id' => (int) $fieldId]]);
    }

    /**
     * Persist a new field order. Body: { order: [fieldId, fieldId, ...] }.
     */
    public function reorder(Request $request, Form $form)
    {
        $order = $request->input('order', []);
        $position = 0;
        foreach ($order as $fieldId) {
            FormField::whereFormId($form->id)->where('id', $fieldId)
                ->update(['position' => $position++]);
        }

        return response()->json(['data' => ['order' => $order]]);
    }

    /**
     * Form-level settings save (name, submit_text, recaptcha, behaviour, etc.).
     */
    public function updateForm(Request $request, Form $form)
    {
        $data = $request->only($form->getFillable());
        $form->update($data);

        return response()->json(['data' => $form->fresh()]);
    }

    // ---- Email notifications (Phase 6) ----

    public function notifications(Form $form)
    {
        return response()->json(['data' => $form->notifications()->get()]);
    }

    public function storeNotification(Request $request, Form $form)
    {
        $data = $this->notificationData($request);
        $data['form_id'] = $form->id;
        $data['position'] = (int) FormEmailNotification::where('form_id', $form->id)->max('position') + 1;

        $notification = FormEmailNotification::create($data);

        return response()->json(['data' => $notification], 201);
    }

    public function updateNotification(Request $request, Form $form, $id)
    {
        $notification = FormEmailNotification::where('form_id', $form->id)->findOrFail($id);
        $notification->update($this->notificationData($request));

        return response()->json(['data' => $notification->fresh()]);
    }

    public function destroyNotification(Form $form, $id)
    {
        $notification = FormEmailNotification::where('form_id', $form->id)->findOrFail($id);
        $notification->delete();

        return response()->json(['data' => ['id' => (int) $id]]);
    }

    public function reorderNotifications(Request $request, Form $form)
    {
        $order = $request->input('order', []);
        $position = 0;
        foreach ($order as $id) {
            FormEmailNotification::where('form_id', $form->id)->where('id', $id)
                ->update(['position' => $position++]);
        }

        return response()->json(['data' => ['order' => $order]]);
    }

    protected function notificationData(Request $request): array
    {
        return $request->only([
            'active', 'name', 'to', 'cc', 'bcc', 'reply_to', 'subject', 'content',
        ]);
    }

    // ---- Integrations (Phase 7) ----

    /**
     * Registered integrations (from the aggregate) merged with this form's saved rows.
     */
    public function integrations(Form $form)
    {
        $registered = app(FormBuilderIntegrationAggregate::class)->all();
        $saved = $form->integrations()->get()->keyBy('integration_key');

        $data = collect($registered)->map(function ($integration, $key) use ($saved) {
            $row = $saved->get($key);
            return [
                'key'         => $key,
                'name'        => $integration['name'],
                'icon'        => $integration['icon'],
                'description' => $integration['description'],
                'settings'    => $integration['settings'],   // declared custom fields
                'enabled'     => $row ? (bool) $row->enabled : false,
                'send_email'  => $row ? (bool) $row->send_email : true,
                'config'      => $row->config ?? new \stdClass(),
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function updateIntegration(Request $request, Form $form, $key)
    {
        if (!app(FormBuilderIntegrationAggregate::class)->has($key)) {
            return response()->json(['message' => 'Unknown integration'], 404);
        }

        $integration = FormIntegration::updateOrCreate(
            ['form_id' => $form->id, 'integration_key' => $key],
            [
                'enabled'    => (bool) $request->input('enabled', false),
                'send_email' => (bool) $request->input('send_email', true),
                'config'     => $request->input('config', []),
            ]
        );

        return response()->json(['data' => $integration]);
    }

    /**
     * Whitelist + normalise the field payload from the editor.
     */
    protected function fieldData(Request $request): array
    {
        $data = $request->only([
            'form_field_type_id', 'active', 'show_label', 'name', 'required',
            'placeholder', 'default_value', 'error_message', 'include_in_email',
            'visibility', 'visibility_rules', 'data', 'custom_field_class',
            'store_in', 'note', 'note_position', 'label_position', 'autocomplete',
            'custom_class', 'merge_field', 'hidden_field_value', 'settings',
        ]);

        // visibility_rules is cast to array on the model; accept null/array/json
        if (array_key_exists('visibility_rules', $data) && is_string($data['visibility_rules'])) {
            $data['visibility_rules'] = json_decode($data['visibility_rules'], true);
        }

        return $data;
    }
}

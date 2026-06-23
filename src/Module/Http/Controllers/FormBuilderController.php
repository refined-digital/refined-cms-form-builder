<?php

namespace RefinedDigital\FormBuilder\Module\Http\Controllers;

use Illuminate\Http\Request;
use RefinedDigital\CMS\Modules\Core\Http\Controllers\CoreController;
use RefinedDigital\FormBuilder\Module\Http\Requests\FormBuilderRequest;
use RefinedDigital\FormBuilder\Module\Http\Repositories\FormBuilderRepository;
use RefinedDigital\CMS\Modules\Core\Http\Repositories\CoreRepository;
use RefinedDigital\FormBuilder\Module\Http\Requests\FormSubmitRequest;
use RefinedDigital\FormBuilder\Module\Models\Form;

class FormBuilderController extends CoreController
{
    protected $model = 'RefinedDigital\FormBuilder\Module\Models\Form';
    protected $prefix = 'formBuilder::forms.';
    protected $route = 'form-builder';
    protected $heading = 'Form Builder';
    protected $button = 'a Form';

    protected readonly FormBuilderRepository $formBuilderRepository;

    public function __construct(CoreRepository $coreRepository)
    {
        $this->formBuilderRepository = new FormBuilderRepository();
        $this->formBuilderRepository->setModel($this->model);

        parent::__construct($coreRepository);
    }

    public function setup() {

        $table = new \stdClass();
        $table->fields = [
            (object) [ 'name' => 'Name', 'field' => 'name', 'sortable' => true, 'route' => 'refined.form-builder.edit'],
            (object) [ 'name' => 'Subject', 'field' => 'subject', 'sortable' => true, 'route' => 'refined.form-builder.edit'],
        ];
        $table->routes = (object) [
            'edit'      => 'refined.form-builder.edit',
            'destroy'   => 'refined.form-builder.destroy'
        ];
        $table->sortable = false;

        $table->extraActions = [
            (object) [ 'route' => 'refined.form-builder.submissions', 'name' => 'Submissions', 'icon' => 'far fa-list-alt'],
            (object) [ 'route' => 'refined.form-builder.duplicate', 'name' => 'Duplicate', 'icon' => 'far fa-clone'],
            // Export moved off the listing; route + controller@export kept for relocation
        ];

        $this->table = $table;

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // minimal create: just a name; saving drops the user into the editor
        // (the editor autosaves everything else via the API). Buttons are
        // rendered as objects (matches CoreController's normalisation).
        $this->buttons = [
            (object) ['class' => 'button button--blue', 'name' => 'Create Form', 'href' => '#'],
        ];

        return parent::create();
    }

    public function edit($item)
    {
        // the visual editor autosaves every change via the JSON API, so the
        // legacy Save / Save & Return / Save & New header buttons aren't needed.
        // Keep a single link back to the forms list.
        $this->buttons = [
            (object) ['class' => 'button button--grey', 'name' => 'Back to Forms', 'href' => route('refined.form-builder.index')],
            (object) ['class' => 'button button--blue', 'name' => 'View Submissions', 'href' => route('refined.form-builder.submissions', $item)],
        ];

        // get the instance
        $data = $this->model::findOrFail($item);

        return parent::edit($data);
    }

    /**
     * Store the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(FormBuilderRequest $request)
    {
        return parent::storeRecord($request);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(FormBuilderRequest $request, $id)
    {
        return parent::updateRecord($request, $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if ($this->formBuilderRepository->destroy($id)) {
            return back()->with('status', 'Item deleted successfully');
        }

        return back()->with('status', 'Failed to delete item')->with('fail', 1);
    }

    /**
     * Export the form submissions.
     *
     * @param  \RefinedDigital\FormBuilder\Module\Models\Form  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function export(Form $form)
    {
        $data = $this->formBuilderRepository->export($form);

        if (is_array($data)) {
            $filename = \Str::slug('form '.$form->name.'-export-'.date('Y-m-d')).'.csv';

            return response()->streamDownload(function() use($data) {
                // create a file pointer connected to the output stream
                $output = fopen('php://output', 'w');

                // output the column headings
                fputcsv($output, $data['headers']);

                // format the body
                if(count($data['body'])) {
                    foreach($data['body'] as $b) {
                        fputcsv($output, $b);
                    }
                }
            }, $filename);
        }

        return redirect()->back()->with('status', 'Failed to generate export')->with('fail', 1);
    }

    /**
     * Visual list of a form's submissions, grouped one entry per form-fill.
     * Renders through the standard core index blade so it matches every other
     * admin listing (header, data-table, action icons).
     */
    public function submissions(Form $form)
    {
        $groups = $this->formBuilderRepository->groupedSubmissions($form);

        // wrap the grouped collection in a paginator so the standard blade's
        // pagination works (and ->count() etc. behave like other listings)
        $perPage = 30;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $data = new \Illuminate\Pagination\LengthAwarePaginator(
            $groups->forPage($page, $perPage)->values(),
            $groups->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $table = new \stdClass();
        $table->fields = [
            (object) ['name' => 'Submitted',     'field' => 'submitted_at'],
            (object) ['name' => 'Notifications', 'field' => 'count_label'],
            (object) ['name' => 'Summary',       'field' => 'summary'],
        ];
        // green pencil opens the detail; no destroy route -> no delete icon
        $table->routes = (object) ['edit' => 'refined.form-builder.submissions.show'];
        $table->noDelete = [];

        return view('core::pages.index', [
            'heading'           => $form->name.' — Submissions',
            'button'            => false,
            'routes'            => (object) ['index' => route('refined.form-builder.submissions', $form)],
            'routeEnd'          => 'index',
            'tableSettings'     => $table,
            'data'              => $data,
            'canCreate'         => false,
            'canDelete'         => false,
            'canUpdate'         => true,
            'sort'              => false,
            'sortable'          => false,
            'showEnableSorting' => false,
            'prefix'            => $this->prefix,
            // breadcrumb: Form Builder / {form} — Submissions
            'parent'            => (object) ['name' => $this->heading, 'index' => route('refined.form-builder.index')],
            // right-side buttons
            'indexButtons'      => [
                (object) ['name' => 'Back to Forms', 'href' => route('refined.form-builder.index'), 'class' => 'button button--grey'],
                (object) ['name' => 'Export CSV', 'href' => route('refined.form-builder.export', $form), 'class' => 'button button--blue'],
                (object) ['name' => 'Edit Form', 'href' => route('refined.form-builder.edit', $form), 'class' => 'button button--blue'],
            ],
        ]);
    }

    /**
     * Detail of a single grouped submission: the form's field values plus the
     * per-notification delivery details. Resolves the owning form from the token.
     */
    public function submissionShow($token)
    {
        $form = $this->formBuilderRepository->findFormByToken($token);
        $submission = $form ? $this->formBuilderRepository->submissionGroup($form, $token) : null;

        if (!$form || !$submission) {
            return redirect()
                ->route('refined.form-builder.index')
                ->with('status', 'Submission not found')
                ->with('fail', 1);
        }

        return view('formBuilder::forms.submissions.show', [
            'heading'      => $this->heading,
            'form'         => $form,
            'submission'   => $submission,
            'backRoute'    => route('refined.form-builder.submissions', $form),
        ]);
    }

    /**
     * Duplicates the form.
     *
     * @param  \RefinedDigital\FormBuilder\Module\Models\Form  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate($originalId)
    {
        $form = Form::findOrFail($originalId);

        $type = $this->formBuilderRepository->duplicate($form);

        if ($type) {
            return redirect()->back()->with('status', 'Form has been successfully duplicated');
        }

        return back()->with('status', 'Failed to duplicate form')->with('fail', 1);
    }


    /**
     * handles the form submits
     *
     * @param  Request
     * @param  RefinedDigital\FormBuilder\Module\Models\Form
     * @return \Illuminate\Http\RedirectResponse
     */
     public function submit(FormSubmitRequest $request, Form $form)
     {
        // run enabled integrations first; a failure aborts the whole submission
        // (no notifications, no redirect) — used by Payments for declined charges
        $failure = $this->formBuilderRepository->runIntegrations($request, $form);
        if ($failure) {
            $message = $failure->message ?? 'We could not process your submission.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => $failure->errors ?? []], 422);
            }
            return redirect()->back()->withInput()->withErrors(['form' => $message]);
        }

        // send the active email notifications unless an enabled integration opted
        // out via Send Email = No
        if ($this->formBuilderRepository->shouldSendNotifications($form)) {
            $this->formBuilderRepository->compileAndSend($request, $form);
        }

        if (session()->has('form_data')) {
            session()->forget('form_data');
        }

        // behaviour outcome (Phase 6): message | redirect_page | redirect_url
        $action = $form->submit_action ?: 'message';

        if ($action === 'redirect_page' && $form->redirect_page) {
            $settings = json_decode($form->redirect_page);
            if (isset($settings->url) && $settings->url) {
                $url = help()->checkLink($settings->url);
                if ($request->expectsJson()) {
                    return response()->json([...$form->only(['id']), 'url' => $url]);
                }
                return redirect($url)->with('complete', 1)->with('form', $form);
            }
        }

        if ($action === 'redirect_url' && $form->redirect_url) {
            if ($request->expectsJson()) {
                return response()->json([...$form->only(['id']), 'url' => $form->redirect_url]);
            }
            return redirect($form->redirect_url)->with('complete', 1)->with('form', $form);
        }

        // default: show the on-screen confirmation message
        if ($request->expectsJson()) {
            return response()->json($form->only(['confirmation', 'id']));
        }

        return redirect()->back()->with('complete', 1)->with('form', $form);
     }

}

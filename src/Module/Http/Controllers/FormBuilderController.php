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

    protected $formBuilderRepository;

    public function __construct(CoreRepository $coreRepository)
    {
        $this->formBuilderRepository = new FormBuilderRepository();
        $this->formBuilderRepository->setModel($this->model);
        $this->buttons[] = ['class' => 'button button--blue', 'name' => 'Save & Edit Fields', 'href' => '#'];

        parent::__construct($coreRepository);
    }

    public function setup() {

        $table = new \stdClass();
        $table->fields = [
            (object) [ 'name' => 'Name', 'field' => 'name', 'sortable' => true, 'route' => 'refined.form-builder.fields.index'],
            (object) [ 'name' => 'Subject', 'field' => 'subject', 'sortable' => true, 'route' => 'refined.form-builder.fields.index'],
            (object) [ 'name' => 'Email To', 'field' => 'email_to', 'sortable' => true, 'route' => 'refined.form-builder.fields.index'],
        ];
        $table->routes = (object) [
            'edit'      => 'refined.form-builder.edit',
            'destroy'   => 'refined.form-builder.destroy'
        ];
        $table->sortable = false;

        $table->extraActions = [
            (object) [ 'route' => 'refined.form-builder.duplicate', 'name' => 'Duplicate', 'icon' => 'far fa-clone'],
            (object) [ 'route' => 'refined.form-builder.export', 'name' => 'Export', 'icon' => 'far fa-file-excel'],
        ];

        $this->table = $table;

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($item)
    {
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
            $filename = str_slug('form '.$form->name.'-export-'.date('Y-m-d')).'.csv';

            return response()->streamDownload(function() use($data) {
                // create a file pointer connected to the output stream
                $output = fopen('php://output', 'w');

                // output the column headings
                fputcsv($output, $data['headers']);

                // format the body
                if(sizeof($data['body'])) {
                    foreach($data['body'] as $b) {
                        fputcsv($output, $b);
                    }
                }
            }, $filename);
        }

        return redirect()->back()->with('status', 'Failed to generate export')->with('fail', 1);
    }

    /**
     * Duplicates the form.
     *
     * @param  \RefinedDigital\FormBuilder\Module\Models\Form  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate(Form $form)
    {
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
         // todo: add events to hook into after email has been sent
         // todo: maybe make the actual sending of the email as an event also
        switch($form->form_action) {
            case 2: // email in callback
                $hasReturn = $this->formBuilderRepository->emailInCallback($request, $form);
                if ($hasReturn) {
                    return $hasReturn;
                }
                break;
            case 3: // save to model
                $hasReturn = $this->formBuilderRepository->saveToModel($request, $form);
                if ($hasReturn) {
                    return $hasReturn;
                }
                break;
            default:
                $this->formBuilderRepository->compileAndSend($request, $form);
            break;
        }

        if (session()->has('form_data')) {
            session()->forget('form_data');
        }

        if ($request->ajax()) {
            return response()->json($form);
        }

        if ($form->redirect_page) {
            return redirect($form->redirect_page)->with('complete', 1)->with('form', $form);
        }

        return redirect()->back()->with('complete', 1)->with('form', $form);
     }

}

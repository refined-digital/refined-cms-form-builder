<?php

namespace RefinedDigital\FormBuilder\Module\Http\Controllers;

use RefinedDigital\CMS\Modules\Core\Http\Controllers\CoreController;
use RefinedDigital\FormBuilder\Module\Http\Requests\FormFieldRequest;
use RefinedDigital\FormBuilder\Module\Http\Repositories\FormBuilderRepository;
use RefinedDigital\CMS\Modules\Core\Http\Repositories\CoreRepository;

class FormFieldsController extends CoreController
{
    protected $model = 'RefinedDigital\FormBuilder\Module\Models\FormField';
    protected $prefix = 'formBuilder::fields.';
    protected $route = 'form-builder.fields';
    protected $heading = 'Form Fields';
    protected $button = 'a Field';

    protected $parentRoute = 'form_builder';
    protected $parentModel = 'RefinedDigital\FormBuilder\Module\Models\Form';
    protected $parentIndex = 'form-builder.index';

    protected $formBuilderRepository;

    public function __construct(CoreRepository $coreRepository)
    {
        $this->formBuilderRepository = new FormBuilderRepository();
        $this->formBuilderRepository->setModel($this->model);

        if (!app()->runningInConsole()) {
            $this->indexButtons[] = ['class' => 'button button--green', 'name' => 'Edit Form', 'href' => route('refined.form-builder.edit', request()->route($this->parentRoute))];
            $this->indexButtons[] = ['class' => 'button button--red', 'name' => 'Return to Forms', 'href' => route('refined.form-builder.index')];
        }

        parent::__construct($coreRepository);
    }

    public function setup() {

        $table = new \stdClass();
        $table->fields = [
            (object) [ 'name' => 'Name', 'field' => 'name', 'sortable' => true],
            (object) [ 'name' => 'Field Type', 'type' => 'field', 'sortable' => false],
            (object) [ 'name' => 'Required', 'field' => 'required', 'sortable' => true, 'type'=> 'select', 'options' => [1 => 'Yes', 0 => 'No'], 'classes' => ['data-table__cell--active']],
            (object) [ 'name' => 'Active', 'field' => 'active', 'sortable' => true, 'type'=> 'select', 'options' => [1 => 'Yes', 0 => 'No'], 'classes' => ['data-table__cell--active']],
        ];
        $table->routes = (object) [
            'edit'      => 'refined.form-builder.fields.edit',
            'destroy'   => 'refined.form-builder.fields.destroy'
        ];
        $table->sortable = true;

        $this->table = $table;

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // do the initial setting of vars on the child class
        $data = $this->formBuilderRepository->getAllFields();
        return $this->indexSetup($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($item)
    {
        $id = request()->route('field');
        // get the instance
        $data = $this->model::findOrFail($id);

        return parent::edit($data);
    }

    /**
     * Store the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(FormFieldRequest $request, $formId)
    {
        $item = $this->formBuilderRepository->storeField($request, ['form_id' => $formId]);

        $route = $this->getReturnRoute($item->id, $request->get('action'), $formId);

        return redirect($route)->with('status', 'Successfully created');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(FormFieldRequest $request, $formId, $id)
    {
        $this->formBuilderRepository->updateField($id, $request);

        $route = $this->getReturnRoute($id, $request->get('action'), $formId);

        return redirect($route)->with('status', 'Successfully updated');
    }

    public function destroy($id)
    {
        $id = request()->route('field');

        if ($this->formBuilderRepository->destroy($id)) {
            return back()->with('status', 'Item deleted successfully');
        }

        return back()->with('status', 'Failed to delete item')->with('fail', 1);
    }

    protected function getReturnRoute($id, $type = false, $parentId = false)
    {
        $route = route('refined.'.$this->route.'.edit', [$parentId, $id]);

        if ($type == 'save & return') {
            $route = route('refined.'.$this->route.'.index', $parentId);
        }

        if ($type == 'save & new') {
            $route = route('refined.'.$this->route.'.create', $parentId);
        }

        return $route;
    }

}

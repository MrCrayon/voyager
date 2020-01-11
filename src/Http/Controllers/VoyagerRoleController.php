<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Facades\Voyager;

class VoyagerRoleController extends VoyagerBaseController
{
    // POST BR(E)AD
    public function update(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->getCached()->where('slug', $slug)->first();

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        $dataTypeRows = $dataType->editRows;

        //Validate fields
        $val = $this->validateBread($request->all(), $dataTypeRows, $dataType->name, $id)->validate();

        $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);
        $this->insertUpdateData($request, $slug, $dataTypeRows, $data);

        $data->permissions()->sync($request->input('permissions', []));

        return redirect()
            ->route("voyager.{$dataType->slug}.index")
            ->with([
                'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
    }

    // POST BRE(A)D
    public function store(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->getCached()->where('slug', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        $dataTypeRows = $dataType->addRows;

        //Validate fields
        $val = $this->validateBread($request->all(), $dataTypeRows)->validate();

        $data = new $dataType->model_name();
        $this->insertUpdateData($request, $slug, $dataTypeRows, $data);

        $data->permissions()->sync($request->input('permissions', []));

        return redirect()
            ->route("voyager.{$dataType->slug}.index")
            ->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
    }
}

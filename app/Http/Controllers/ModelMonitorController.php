<?php

namespace App\Http\Controllers;

//use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Repositories\ModelMonitorRepositoryEloquent;
use App\Entities\ModelMonitor;
use Log;
use Input;
use Lang;
use Session;
use Redirect;
use Response;
use Illuminate\Support\Facades\View;
use Prettus\Validator\Exceptions\ValidatorException;

class ModelMonitorController extends Controller
{

     protected $repository;
     public function __construct(ModelMonitorRepositoryEloquent $repository) 
{
    $this->middleware('auth');
    //$this->middleware('acl');
    $this->repository = $repository;
}

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $modelmonitors = $this->repository->all();
        if(Request::isJson()) {
            return $modelmonitors;
        }

        return View::make("modelmonitor.index", compact('modelmonitors'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $modelmonitor = new ModelMonitor();
        return view("modelmonitor.edit", compact('modelmonitor'));
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        try {
            $this->repository->validator();
            $this->repository->create( Input::all() );
            Session::flash('message', Lang::get('general.succefullcreate', 
                  ['table'=> Lang::get('general.ModelMonitor')]));
            return Redirect::to('modelmonitor');
        } catch (ValidatorException $e) {
            return Redirect::back()->withInput()
                   ->with('errors',  $e->getMessageBag());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $modelmonitor= $this->repository->find($id);
        return View::make("modelmonitor.show", compact('modelmonitor'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $modelmonitor = $this->repository->find($id);
        return View::make("modelmonitor.edit", compact('modelmonitor'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        try {
            $this->repository->validator();
            $this->repository->update(Input::all(), $id);
            Session::flash('message', Lang::get('general.succefullupdate', 
                       ['table'=> Lang::get('general.ModelMonitor')]));
            return Redirect::to('modelmonitor');
         }
         catch (ValidatorException $e) {
            return Redirect::back()->withInput()
                    ->with('errors',  $e->getMessageBag());
         }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        Log::info('Delete field: '.$id);
        if($this->repository->find($id)) {
            $this->repository->delete($id);
            Session::flash('message', Lang::get("general.deletedregister"));
         }

        return Redirect::to('modelmonitor');
    }
}

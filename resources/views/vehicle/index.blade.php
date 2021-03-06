@extends('layouts.default')

@section('header')
      
      <span class="mdl-layout-title">{{Lang::get("general.Vehicle")}}</span>

@stop

@include('vehicle.filter')

@section('content')

<div class="mdl-grid demo-content">

	@include('includes.gridview', [
    	'registers' => $vehicles,
    	'gridview' => [
    		'pageActive' => 'vehicle',
         	'sortFilters' => [
                ["class" => "mdl-cell--hide-phone mdl-cell--3-col", "name" => "model-vehicle", "lang" => "general.model_vehicle"], 
                ["class" => "mdl-cell--2-col", "name" => "fleet", "lang" => "general.fleet_number"], 
                ["class" => "mdl-cell--2-col", "name" => "number", "lang" => "general.number"], 
                ["class" => "mdl-cell--hide-phone mdl-cell--hide-tablet mdl-cell--2-col", "name" => "cost", "lang" => "general.cost", "mask" => "money"], 
    		] 
    	]
    ])
	
</div>

@stop   
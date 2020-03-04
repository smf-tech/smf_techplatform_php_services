<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Machine extends \Jenssegers\Mongodb\Eloquent\Model
{
     protected $table = 'machine'; 
	 
	public function State()
	{
		return $this->hasOne('App\State','_id','state_id')->select('name');
	}

	public function District()
	{
		return $this->hasOne('App\District','_id','district_id')->select('name');
	}

	public function Taluka()
	{
		return $this->hasOne('App\Taluka','_id','taluka_id')->select('name');
	}

	public function masterData()
	{
		return $this->hasOne('App\MasterData','_id','owned_by');
	}

	public function machine_make_master()
	{
		return $this->hasOne('App\MachineMakeMaster','_id','make_model');
	}

	public function MasterDatatype()
	{
		return $this->hasOne('App\MasterData','_id','type_id');
	}
	
	public function MasterManufactureYr()
	{
		return $this->hasOne('App\MachineManufactureYear','_id','manufactured_year');
	}

	public function ownership()
	{
		return $this->hasOne('App\MasterData','_id','ownership_type_id');
	}	
	
	public function machineMou()
	{
		return $this->hasOne('App\MachineMou','provider_information.machine_id','_id')->select('provider_information','operator_details','mou_details','is_MOU_cancelled','status');
	}
	
	public function machineDeployed() {

		return $this->hasOne('App\StructureMachineMapping','machine_id','_id')->where('status', 'deployed')->select('machine_id','structure_id');

	}
	
	public function ownedBy()
	{
		return $this->hasOne('App\MasterData','_id','owned_by');
	}
	
	public function signOff() {
		
		return $this->hasOne('App\MachineSignOff','machine_id','_id');
		
	}

	public function operatorData() {
		
		return $this->hasOne('App\OperatorMachineMapping','machine_id','_id');
		
	}	
}

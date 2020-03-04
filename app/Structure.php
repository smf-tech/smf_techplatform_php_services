<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Structure extends \Jenssegers\Mongodb\Eloquent\Model
{
	// protected $connection  = 'mongo';
	protected $table = 'structure';
	protected $dates = ['closed_date'];
	
	public function departmentName() 
	{
		return $this->hasOne('App\StructureDepartment','_id','department_id')->select('_id','value');	
	}
	
	public function subDepartmentName() 
	{
		return $this->hasOne('App\StructureSubDepartment','_id','sub_department_id');	
	}
	
	public function workType() 
	{
		return $this->hasOne('App\MasterData','_id','work_type');	
	}	
	
	public function structureType() 
	{
		return $this->hasOne('App\StructureType','_id','type_id');	
	}

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
	
	public function Village()
	{
		return $this->hasOne('App\Village','_id','village_id')->select('name');
	}
	
	public function structureMachine() {

		return $this->hasMany('App\StructureMachineMapping','structure_id','_id')->where('status', 'deployed')->select('machine_id','structure_id','status','created_at');

	}

	public function structurePrepared() {

		return $this->hasMany('App\StructurePreparation','structure_id','_id')->select('structure_id','created_by');

	}
	
	
	
}
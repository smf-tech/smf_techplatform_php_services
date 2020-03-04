<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
class OperatorMachineMapping extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'operator_machine_mapping';
	
	public function machineData()
    {
        return $this->hasMany('App\Machine', '_id', 'machine_id')->select('_id','status_code','machine_code');
    }
	
	public function userData()
    {
        return $this->belongsTo('App\User', '_id', 'operator_id')->select('_id','name','phone');
    }
	
	
	public function operatorData()
    {
        return $this->hasOne('App\User', '_id', 'operator_id')->select('_id','name','phone');
    }
}
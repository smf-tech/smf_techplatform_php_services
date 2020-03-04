<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
class StructureMachineMapping extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'structure_machine_mapping';
	
	public function structureDetails() {

		return $this->hasOne('App\Structure','_id','structure_id')->select('code');

	}

}
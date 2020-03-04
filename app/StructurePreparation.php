<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
class StructurePreparation extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'structure_preparation';
		
	public function userDetails() {

		return $this->hasMany('App\User','_id','created_by')->select('_id','name');

	}


}
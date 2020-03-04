<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmartGirlBatch extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'smartGirl_trainer_batch';
    // protected $fillable=['name'];  


    public function State()
	{
		return $this->hasOne('App\State','_id','state_id')->select('name');
	}

	public function District()
	{
		return $this->hasOne('App\District','_id','district_id')->select('name');
	}
     
}

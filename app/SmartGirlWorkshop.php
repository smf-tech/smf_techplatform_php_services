<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SmartGirlWorkshop extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'smartGirl_girls_workshop';
    // protected $fillable=['name'];  


    public function State()
	{
		return $this->hasOne('App\State','_id','state_id')->select('name');
	}

	public function District()
	{
		return $this->hasOne('App\District','_id','district_id')->select('name');
	}

	public function Category()
	{
		return $this->hasOne('App\Category','_id','batch_category_id')->select('name.default','_id');
	}
     
}

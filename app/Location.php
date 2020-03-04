<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\State;
use App\District;
use App\Taluka;
use App\Village;
use App\City;
use App\Country;

class Location extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable = [
        'name','jurisdictionId','level','jurisdiction_type_id'
    ];

    public function state()
    {
        return $this->belongsTo(State::class)->where('is_active',1);
    }

    public function district()
    {
        return $this->belongsTo(District::class)->where('is_active',1);
    }

    public function taluka()
    {
        return $this->belongsTo(Taluka::class)->where('is_active',1);
    }

    public function village()
    {
        return $this->belongsTo(Village::class)->where('is_active',1);
    }
	
    public function city()
    {
        return $this->belongsTo(City::class)->where('is_active',1);;
    }
	
    public function country()
    {
        return $this->belongsTo(Country::class)->where('is_active',1);
    }
}

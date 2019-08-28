<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\State;
use App\District;
use App\Taluka;
use App\Village;

class Location extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable = [
        'name','jurisdictionId','level','jurisdiction_type_id'
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function taluka()
    {
        return $this->belongsTo(Taluka::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }
	
    public function city()
    {
        return $this->belongsTo(City::class);
    }
	
    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}

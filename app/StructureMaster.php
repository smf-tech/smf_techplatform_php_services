<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Model;
use App\State;
use App\District;
use App\Taluka;
use App\Village;

class StructureMaster extends Model
{
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
}

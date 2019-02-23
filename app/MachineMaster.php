<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\State;
use App\District;
use App\Taluka;

class MachineMaster extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'machine_masters';

    protected $fillable = [
        'pin','name','type','machine_code','district','taluka',
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

}

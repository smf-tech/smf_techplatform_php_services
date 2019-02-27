<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\State;
use App\District;

class MachineMou extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'machine_mou';

    protected $fillable = [
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}

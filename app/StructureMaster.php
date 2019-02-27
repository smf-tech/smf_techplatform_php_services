<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Model;
use App\State;
use App\District;
use App\Taluka;
use App\Village;

class StructureMaster extends Model
{
    protected $table = 'structure_masters';
    protected $fillable = [  
        'structure_code','taluka_id','village_id','structure_owner_department','type',
        'district_id','created_by','created_at','updated_at'
        // 'pin','name','type','machine_code','district','taluka',
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
}

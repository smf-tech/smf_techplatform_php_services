<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Model;
use App\StructureTracking;

class Volunteer extends Model
{
    protected $fillable = [
        'name',
        'mobileNumber'
    ];

    public function structureTracking()
    {
        return $this->belongsTo(StructureTracking::class);
    }
}

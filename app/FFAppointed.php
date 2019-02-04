<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Model;
use App\StructureTracking;

class FFAppointed extends Model
{
    protected $fillable = [
        'name',
        'mobile_number',
        'training_completed'
    ];

    public function structureTracking()
    {
        return $this->belongsTo(StructureTracking::class);
    }
}

<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Model;
use App\Volunteer;
use App\FFAppointed;

class StructureTracking extends Model
{
    protected $fillable = [
        'village',
        'structure_code',
        'work_type',
        'start_date',
        'operator_training_done',
        'work_start_date',
        'work_end_date',
        'status',
        'created_by',
        'updated_by'
    ];

    public function volunteers()
    {
        return $this->hasMany(Volunteer::class);
    }

    public function ffs()
    {
        return $this->hasMany(FFAppointed::class);
    }
}

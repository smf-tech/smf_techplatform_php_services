<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Model;
use App\Volunteer;
use App\FFAppointed;
use App\Village;

class StructureTracking extends Model
{
    protected $fillable = [
        'structure_code',
        'work_type',
        'reporting_date',
        'operator_training_done',
        'work_start_date',
        'work_end_date',
        'status',
        'created_by',
        'updated_by',
        'structure_images_1',
        'structure_images_2',
        'structure_images_3',
        'structure_images_4'
    ];

    public function volunteers()
    {
        return $this->hasMany(Volunteer::class);
    }

    public function ffs()
    {
        return $this->hasMany(FFAppointed::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }
}

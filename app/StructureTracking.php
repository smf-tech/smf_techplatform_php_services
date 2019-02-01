<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Model;

class StructureTracking extends Model
{
    protected $fillable = [
        'village',
        'structure_code',
        'work_type',
        'start_date',
        'operator_training_done',
        'ff_appointed',
        'work_start_date',
        'work_end_date',
        'volunteers',
        'status',
        'created_by',
        'updated_by'
    ];
}

<?php

namespace App;

use App\Volunteer;
use App\FFAppointed;
use App\Village;

class StructureTracking extends BaseModel
{
	use AuditFields;

    protected $fillable = [
        'structure_code',
        'work_type',
        'reporting_date',
        'operator_training_done',
        'work_start_date',
        'work_end_date',
        'status',
        'userName',
        'updated_by',
        'structure_images_1',
        'structure_images_2',
        'structure_images_3',
        'structure_images_4',
		'form_id'
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

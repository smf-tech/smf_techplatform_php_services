<?php

namespace App;

use App\MachineTracking;
use App\Village;

class ShiftingRecord extends BaseModel
{
	use AuditFields;

    protected $table = 'shifting_records';

    protected $fillable = [
        'old_structure_code',
        'machine_code',
        'new_structure_code',
        'structure_status',
        'demobilisation_date',
        'demobilisation_time',
        'remobilisation_date',
        'remobilisation_time',
        'start_meter',
        'end_meter',
        'distance_travelled',
        'shifting_mode',
        'operator_availability',
        'delay_in_days',
        'delay_reason',
        'issue_faced',
        'diesel_availability_photo',
        'userName',
        'form_id'
    ];

    public function machineTracking()
    {
        return $this->belongsTo(MachineTracking::class);
    }

    public function movedFromVillage()
    {
        return $this->belongsTo(Village::class);
    }

    public function movedToVillage()
    {
        return $this->belongsTo(Village::class);
    }
}

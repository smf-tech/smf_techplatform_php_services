<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\MachineTracking;

class ShiftingRecord extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'shifting_records';

    protected $fillable = [

        'moved_from_village',
        'old_structure_code',
        'machine_code',
        'moved_to_village',
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
        'diesel_available_at_destination'
        
    ];
    public function machineTrackings()
    {
        return $this->belongsTo(MachineTracking::class);
    }
}
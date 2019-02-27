<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ShiftingRecord;
use App\MachineMou;
use App\Village;

class MachineTracking extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'machine_tracking';

    protected $fillable = [
        'shifting_record',
    ];

    public function shiftingRecords()
    {
        return $this->hasMany(ShiftingRecord::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\ShiftingRecord;
use App\MachineMou;
use App\Village;
use App\Taluka;

class MachineTracking extends BaseModel
{
	use AuditFields;

    protected $table = 'machine_tracking';

    protected $hidden = ['created_at','updated_at'];
    
    protected $fillable = [
        'shifting_record',
        'structure_code',
        'machine_code',
        'date_deployed',
        'status',
        'last_deployed',
        'userName',
        'form_id',
        'isDeleted',
        'deployed',
        'taluka_id',
        'village_id'
    ];

    public function shiftingRecords()
    {
        return $this->belongsToMany(ShiftingRecord::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function taluka()
    {
        return $this->belongsTo(Taluka::class);
    }
}

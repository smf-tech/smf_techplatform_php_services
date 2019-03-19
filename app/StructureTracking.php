<?php

namespace App;

use App\Volunteer;
use App\FFAppointed;
use App\Village;
use App\Taluka;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StructureTracking extends BaseModel
{
    use AuditFields;
    
    protected $hidden = ['created_at','updated_at'];

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
        'form_id',
        'isDeleted'
    ];

    // public function scopeExclude($query) {
    //     $columnNames = $this->fillable;
    //     array_push($columnNames,'created_at','updated_at');
    //     return $query->select(array_diff($columnNames, $this->hidden));
    // }

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
    public function taluka()
    {
        return $this->belongsTo(Taluka::class);
    }
}

<?php
namespace App;

use App\Traits\CreatorDetails;
use App\Event;
use App\Survey;

class EventType extends \Jenssegers\Mongodb\Eloquent\Model
{
    use AuditFields;

    
    protected $fillable = [];
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function surveys(){
        return $this->belongsToMany(Survey::class);
    }
}
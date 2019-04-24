<?php

namespace App;

use App\Event;

class EventParticipant extends BaseModel
{
    use AuditFields;

    protected $hidden = ['created_at','updated_at'];

	public function event()
	{
		return $this->belongsTo(Event::class);
	}
}

<?php

namespace App;

use App\EventParticipant;

class Event extends BaseModel
{
    use AuditFields;

    protected $hidden = ['created_at','updated_at'];

	public function participants()
	{
		return $this->hasMany(EventParticipant::class);
	}
}

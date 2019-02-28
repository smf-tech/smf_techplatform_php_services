<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Model;
use App\NotificationSchema;

class NotificationLog extends Model
{
    protected $fillable = [
		'firebase_id',
		'firebase_response'
	];

    public function notificationSchema()
    {
        return $this->belongsTo(NotificationSchema::class);
    }
}

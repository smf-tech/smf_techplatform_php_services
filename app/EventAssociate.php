<?php

namespace App;

class EventAssociate extends BaseModel
{
    use AuditFields;

    protected $hidden = ['created_at','updated_at'];

	protected $fillable=['eventName', 'userName', 'isDeleted'];
}

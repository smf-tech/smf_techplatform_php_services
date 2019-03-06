<?php

namespace App;

class ApprovalLog extends BaseModel
{
	protected $fillable = [
		'entity_id',
		'entity_type',
		'approver_id',
		'status',
		'reason',
		'userName',
		'createdDateTime',
		'updatedDateTime'
	];
}

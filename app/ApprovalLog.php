<?php

namespace App;

class ApprovalLog extends BaseModel
{
 	use AuditFields;

  	protected $fillable = [
		'entity_id',
		'entity_type',
		'approver_ids',
		'status',
		'reason',
		'userName',
	];   
}

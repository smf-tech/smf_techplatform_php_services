<?php 
namespace App;


use Illuminate\Database\Eloquent\Model;


class ApprovalsPending extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'approvals_pending';
	protected $fillable = [
		'entity_id',
		'entity_type',
		'approver_ids',
		'status',
		'reason',
		'userName',
	]; 
    
}
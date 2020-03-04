<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class FeedComment extends \Jenssegers\Mongodb\Eloquent\Model
{
	protected $connection = 'bjsCommunity';
    protected $fillable = [
        'comment',
		'content_type',
		'post_id',
		'is_active',
		'is_deleted',
		'like_count',		
		'user_id',
    ];
	
	public function userDetails() 
	{
		return $this->hasOne('App\CommunityUser','_id','user_id')->select('_id','name','phone','email','profile_image');	
	}
}

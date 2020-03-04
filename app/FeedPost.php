<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class FeedPost extends \Jenssegers\Mongodb\Eloquent\Model
{
	protected $connection = 'bjsCommunity';
    protected $fillable = [
        'title',
		'description',
		'content_type',
		'is_exlusive',
		'is_active',
		'is_published',
		'publish_time',
		'content_detail',
		'external_url',
		'like_count',
		'comment_count',
		'share_count',
		'user_id',
    ];
	
	public function userDetails() 
	{
		return $this->hasOne('App\User','_id','user_id')->select('_id','name','phone','email','profile_pic');	
	}
	
	public function feedLike() 
	{
		return $this->hasMany('App\FeedLike','feed_id','_id');	
	}
	
	public function feedComment() 
	{
		return $this->hasOne('App\FeedComment','post_id','_id');	
	}
	
}

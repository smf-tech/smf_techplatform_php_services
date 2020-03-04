<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class FeedLike extends \Jenssegers\Mongodb\Eloquent\Model
{
	protected $connection = 'bjsCommunity';
	protected $table = 'feed_like';

    protected $fillable = [
        'feed_id',
		'user_id'
		];
	
	public function feedDetails() 
	{
		return $this->hasOne('App\FeedPost','_id','feed_id')->where('is_deleted',0);	
	}
}
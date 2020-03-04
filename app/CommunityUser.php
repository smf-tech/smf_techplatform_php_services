<?php

namespace App;


class CommunityUser extends \Jenssegers\Mongodb\Eloquent\Model
{
   
    protected $connection = "bjsCommunity";

	protected $table = 'users';

	
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];
	
	
}
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Location extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable = [
        'name','jurisdictionId','level',
    ];
}

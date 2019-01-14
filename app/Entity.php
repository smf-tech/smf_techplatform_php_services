<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Entity extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable = [
        'Name','display_name'
    ];
}

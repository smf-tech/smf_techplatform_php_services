<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable = [
        'name'
    ];
}

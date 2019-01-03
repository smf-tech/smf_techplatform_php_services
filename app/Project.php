<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable = [
        'name'
    ];
}

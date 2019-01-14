<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Microservice extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable=['name','description','base_url','route','is_active'];
}

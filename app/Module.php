<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Module  extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $collection = 'modules';
    protected $fillable=['name'];       
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleJurisdiction  extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable=['role_id','jurisdiction_id'];       
}

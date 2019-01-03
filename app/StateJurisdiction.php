<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StateJurisdiction  extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable=['state_id','jurisdiction_id','level'];    
}

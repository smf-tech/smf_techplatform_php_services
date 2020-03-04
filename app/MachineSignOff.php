<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\State;
use App\District;
use App\Taluka;

class MachineSignOff extends \Jenssegers\Mongodb\Eloquent\Model
{
	use AuditFields;

    protected $table = 'machine_sign_off';
	 protected $dates = ['created_at'];


     
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MachineDailyWorkRecord extends \Jenssegers\Mongodb\Eloquent\Model {
	
    protected $table = 'machine_daily_work_record';
	
	    protected $dates = ['workDate'];

    
}

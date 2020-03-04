<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MachineSynch extends \Jenssegers\Mongodb\Eloquent\Model
{
	use AuditFields;

    protected $table = 'machine_synch';

     protected $dates = ['created_at'];
 
}

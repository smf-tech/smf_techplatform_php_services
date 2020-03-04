<?php

namespace App;

#use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Model;

class StructureDepartment extends Model
{
	//protected $connection  = 'bjsmongo';
  	protected $table = 'department_master';

}
<?php

namespace App;

#use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Model;

class StructureType  extends Model
{
		//protected $connection  = 'bjsmongo';
    	protected $table = 'structure_type_master';

}
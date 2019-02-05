<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MachineMaster extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'machine_masters';

    protected $fillable = [
        'pin','name','type','machine_code','district','taluka',
    ];

}

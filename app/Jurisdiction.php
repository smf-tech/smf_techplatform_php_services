<?php 

namespace App;

use Illuminate\Database\Eloquent\Model;

class Jurisdiction  extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable=['levelName'];
}
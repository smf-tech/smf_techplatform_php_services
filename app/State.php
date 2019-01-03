<?php 

namespace App;

use Illuminate\Database\Eloquent\Model;

class State  extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable=['Name'];

    public function jurisdictions(){
        return $this->hasMany('App\StateJurisdiction');
    }
}
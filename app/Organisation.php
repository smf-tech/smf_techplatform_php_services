<?php

namespace App;

#use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Model;

class Organisation extends Model
{
    protected $fillable=['name','service'];
    protected $hidden=['orgshow'];
}

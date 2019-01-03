<?php

namespace App;

#use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Model;

class Task extends Model
{
    protected $fillable = [
        'user_id','name', 'status',
    ];
}

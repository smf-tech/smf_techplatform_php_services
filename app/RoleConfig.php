<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleConfig  extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable=['role_id','default_modules','on_approve_modules','projects','approver_role'];       
}

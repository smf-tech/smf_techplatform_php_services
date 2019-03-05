<?php 

namespace App;

use Illuminate\Database\Eloquent\Model;

class BaseModel  extends \Jenssegers\Mongodb\Eloquent\Model
{
    const CREATED_AT = 'createdDateTime';
    const UPDATED_AT = 'updatedDateTime';
}
<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Model;
use App\Category;

class Report extends Model
{

	public function category()
	{
		return $this->belongsTo(Category::class);
	}
}

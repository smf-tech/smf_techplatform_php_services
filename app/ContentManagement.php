<?php 
namespace App;


use Illuminate\Database\Eloquent\Model;


class ContentManagement extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'content_management';


     public function ContentCategories()
    {
        return $this->belongsTo(ContentCategories::class);
    }
    
    
}
<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
// use Cviebrock\EloquentSluggable\Sluggable;
// use Illuminate\Database\Eloquent\SoftDeletes;
class Survey  extends \Jenssegers\Mongodb\Eloquent\Model
{
    // use SoftDeletes; //Sluggable, 
    // protected $table = 'surveys';
    // protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'json', 'project_id','category_id','creator_id','microservice_id','entity_id',//slug',
    ];
    // protected $casts = [
    //     'json'  =>  'array',
    // ];
    // public function sluggable(): array
    // {
    //     return [
    //         'slug' => [
    //             'source' => 'slug_or_name',
    //         ],
    //     ];
    // }
    // public function getSlugOrNameAttribute()
    // {
    //     if ($this->slug != '') {
    //         return $this->slug;
    //     }
    //     return $this->name;
    // }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function results()
    {
        return $this->hasMany('App\SurveyResult', 'survey_id');
    }
    public function project()
    {
        return $this->belongsTo('App\Project');
    }
    public function category()
    {
        return $this->belongsTo('App\Category');
    }
    public function microservice()
    {
        return $this->belongsTo('App\Microservice');
    }
    public function entity()
    {
        return $this->belongsTo('App\Entity');
    }
}
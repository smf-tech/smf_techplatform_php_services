<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class Survey  extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $fillable = [
        'name', 'json', 'project_id','category_id','creator_id','microservice_id','entity_id',//slug',
    ];
    
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
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
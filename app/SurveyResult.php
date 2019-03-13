<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use App\User;

class SurveyResult  extends \Jenssegers\Mongodb\Eloquent\Model
{
    protected $table = 'survey_results';
    protected $fillable = [
        'survey_id', 'user_id', 'json', 'isDeleted',//'ip_address',
    ];
    // protected $casts = [
    //     'json'  =>  'array',
    // ];
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function survey()
    {
        return $this->belongsTo('App\Survey', 'survey_id');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
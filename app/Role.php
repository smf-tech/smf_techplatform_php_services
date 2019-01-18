<?php 
namespace App;

use Maklad\Permission\Models\Role as Roles;

class Role extends Roles
{
    protected $table = 'survey_roles';

    protected $fillable=['name','display_name','description','org_id','jurisdiction_id','user_ids','guard_name'];    

    public function jurisdiction()
    {
        return $this->belongsTo('App\Jurisdiction','jurisdiction_id');
    }
}
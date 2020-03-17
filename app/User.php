<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Jenssegers\Mongodb\Eloquent\Model as Model;
#use Illuminate\Database\Eloquent\Model;
#use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
#use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
#use DesignMyNight\Mongodb\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Maklad\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;

class User extends Model implements AuthenticatableContract,AuthorizableContract
{
    use HasApiTokens,Authenticatable, Authorizable, HasRoles, AuditFields;

	
	 protected $connection = "mongodb";
     // protected $table = 'users';
	// protected $connection = 'mongodb';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name','email','password','phone','dob','org_id','role_id','approve_status','project_id','profile_pic','type','associate_id','location','firebase_id','gender','device_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];
	
	/**
     * Find the user identified by the given $identifier.
     *
     * @param $identifier email|phone
     * @return mixed
     */
    public function findForPassport($identifier) {
        return User::orWhere('email', $identifier)->orWhere('phone', $identifier)->first();
    }
	
/*	public function operatorMapping() {

		return $this->hasOne('App\OperatorMachineMapping','operator_id','_id');

	}*/
	
	public function operatorMappingList() {
		
		return $this->hasOne('App\OperatorMachineMapping','operator_id','_id');
		
	}


	}

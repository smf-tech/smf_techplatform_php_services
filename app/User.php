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

class User extends Model implements AuthenticatableContract,AuthorizableContract
{
    use HasApiTokens,Authenticatable, Authorizable, HasRoles;


    /**
     * Bucket name
     */
    const BUCKET_NAME = 'bucketName';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name','email','password','phone','dob','org_id','role_id','approve_status','project_id','profile_pic'
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

    public function uploadProfilePicture(\Illuminate\Http\UploadedFile $file)
    {
        $key = time() . '.' . $file->getExtension();
        $s3Client = \App::make('aws')->createClient('s3');
        $s3Client->putObject([
            'Bucket' => self::BUCKET_NAME,
            'Key' => $key,
            'SourceFile' => $file->getRealPath()
        ]);
    }

}
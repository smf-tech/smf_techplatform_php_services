<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Maklad\Permission\Models\Role;
use Maklad\Permission\Models\Permission;
use Dingo\Api\Routing\Helpers;
use App\Organisation;
use App\RoleConfig;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use Helpers;

    /**
     *
     * @var Request
     */
    protected $request;

    public function __construct(Request $request) 
    {
        $this->request = $request;
    }

    public function getUserDetails() {
        $user = $this->request->user();
        return $user;
    }

    public function show()
    {
        $users = User::all();
        return $users;
    }

    public function update($phone)
    {
        $user = User::where('phone', $phone)->first();
        if($user){
//            if ($this->request->hasFile('profile_picture')) {
//                $this->validate($this->request, [
//                    'profile_picture' => 'image'
//                ]);
//            }
            $update_data = $this->request->all();
//            $user->uploadProfilePicture($update_data['profile_picture']);
            //return $update_data;
            $update_data['dob'] = strtotime($update_data['dob']);
            $user->update($update_data);
            /*if(array_key_exists('role_id',$update_data)){
                $role = Role::find($update_data['role_id']);
                if($role){
                    $user->assignRole($role->name);
                }
            }*/
            $user->dob = date('Y-m-d',$user->dob);
            return response()->json(['status'=>'success', 'data'=>$user, 'message'=>''],200);
        }else{
            return response()->json(['status'=>'error', 'data'=>$user, 'message'=>'Invalid Mobile Number'],404);
        }
        
    }

    public function approveuser($phone){

        $userForApproval = User::where('phone', $phone)->first();
        $loggedInUser = $this->request->user();

        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 

        $userRole = RoleConfig::where('role_id',$userForApproval->role_id)->first();

        if(!isset($userRole->approver_role)) {

            $userForApproval->update(['approve_status'=>'approved']);
            return response()->json(['status'=>'success', 'data'=>$userForApproval, 'message'=>''],200);
        }
        if($userRole->approver_role == $loggedInUser->role_id){

            $put_params = $this->request->all();
            $update_data = ['approve_status'=>$put_params['update_status']];
            $userForApproval->update($update_data);
            return response()->json(['status'=>'success', 'data'=>$userForApproval, 'message'=>''],200);

        }else{

            return response()->json(['status'=>'error', 'data'=>'', 'message'=>'You do not have approver role for the given user'],403);

        }
    }

    public function upload()
    {
        if ($this->request->file('profilePhoto')->isValid()) {
            $fileInstance = $this->request->file('profilePhoto');
            $name = $fileInstance->getClientOriginalName();
            //https://mybucket.s3.amazonaws.com/myfolder/afile.jpg
            var_dump($this->request->file('profilePhoto')->storeAs('profile-photoes', $name, 's3'));
        }
    }

    public function approvalList()
    {
        $loggedInUser = $this->request->user();

        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 

        $roles = RoleConfig::where('approver_role', $loggedInUser->role_id)->get(['role_id']);

        if($roles != '[]') {

            DB::setDefaultConnection('mongodb');

            foreach($roles as $role)
            {
                $user = User::where('role_id', '=', $role->role_id)
                        ->where('approve_status','pending')->get();
            
                // if($user != '[]')
                //     $users[] = $user;
            }

            return response()->json(['status'=>'success','data'=>$user,'message'=>''], 200);
        
        } else {

            return response()->json([
                        "status"=>"error",
                        "data"=>"",
                        "message"=>"You do not have approver role for any User"
                    ],
                    403);
        }
    }
}

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
        if($user) {
            $update_data = $this->request->all();

            if (isset($update_data['type']) && !empty($update_data['type'])) {
                if ($update_data['type'] !== 'organisation') {
                    $update_data['associate_id'] = $update_data['org_id'];
                    $orgId = \App\Role::find($update_data['role_id'])->org_id;
                    $update_data['org_id'] = $orgId;
                }
            }

            $user->update($update_data);

            return response()->json(['status'=>'success', 'data'=>$user, 'message'=>''],200);
        }else{
            return response()->json(['status'=>'error', 'data'=>$user, 'message'=>'Invalid Mobile Number'],404);
        }
        
    }

    public function approveuser($phone){

        $userForApproval = User::where('phone', $phone)->first();
        $loggedInUser = $this->request->user();

        $database = $this->connectTenantDatabase($this->request);

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
        if (!$this->request->filled('type')) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'Please specify type field and values must be either form, profile or story'], 400);
        }
        $types = ['profile' => 'BJS/Images/profile', 'form' => 'BJS/Images/forms', 'story' => 'BJS/Images/stories'];
        if (!isset($types[$this->request->type])) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'Invalid type value'], 400);
        }
        if ($this->request->file('image')->isValid()) {
            $fileInstance = $this->request->file('image');
            $name = $fileInstance->getClientOriginalName();
            //https://mybucket.s3.amazonaws.com/myfolder/afile.jpg
            $s3Path = $this->request->file('image')->storePubliclyAs($types[$this->request->type], $name, 's3');
            if ($s3Path == null || !$s3Path) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
            }
            $result = 'https://' . env('AWS_BUCKET') . '.' . env('AWS_URL') . '/' . $s3Path;
            return response()->json(['status' => 'success', 'data' => ['url' => $result], 'message' => 'Image successfully uploaded in S3']);
        }
    }

    public function approvalList()
    {
        $loggedInUser = $this->request->user();

        $database = $this->connectTenantDatabase($this->request);

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

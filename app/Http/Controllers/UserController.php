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
use App\ApprovalLog;

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
        if($user) {
            return response()->json(['status'=>'success', 'data'=>$user, 'message'=>''],200);
        }else{
            return response()->json(['status'=>'error', 'data'=>$user, 'message'=>'User Not Found'],404);
        }
    }

    public function show($phone)
    {
        $user = User::where('phone', $phone)->first();
        if($user) {
            return response()->json(['status'=>'success', 'data'=>$user, 'message'=>''],200);
        }else{
            return response()->json(['status'=>'error', 'data'=>$user, 'message'=>'User Not Found'],404);
        }
        
        return $user;
    }

    public function update($phone)
    {
        $user = User::where('phone', $phone)->first();
		$userId = $user->id;
		$userLocation = $user->location;
        if($user) {
            $update_data = $this->request->all();

            if (isset($update_data['type']) && !empty($update_data['type'])) {
                if ($update_data['type'] !== 'organisation') {
                    $update_data['associate_id'] = $update_data['org_id'];
                    $orgId = \App\Role::find($update_data['role_id'])->org_id;
                    $update_data['org_id'] = $orgId;
                }
            }

            if (
                (isset($update_data['org_id']) && $update_data['org_id'] != $user->org_id)
                ||
                (isset($update_data['project_id']) && !is_array($user->project_id))
                ||
                ($update_data['project_id'] != $user->project_id[0])
                ||
                (isset($update_data['role_id']) && $update_data['role_id'] != $user->role_id)
				||
				(isset($update_data['location']) && $user->location == null)
				||
				(isset($update_data['location']) && $user->location != null && $this->compareLocation($update_data['location'], $user->location))
                ) {
                    $update_data['approve_status'] = 'pending';
            }
            if (isset($update_data['phone'])) {
                unset($update_data['phone']);
            }
            if (isset($update_data['password'])) {
                unset($update_data['password']);
            }
            if(isset($update_data['role_id']) && $update_data['role_id'] != $user->role_id){
                 $user->location = [];  
                 $user->save(); 
            }

            $user->update($update_data);
			$approverList = [];
			$approverIds = [];
			$firebaseIds = [];
			$approvalLogId = '';
			if (isset($update_data['role_id'])) {
                $approverList = $this->getApprovers($this->request, $update_data['role_id'], $userLocation, $update_data['org_id']);

                $this->connectTenantDatabase($this->request);
				foreach($approverList as $approver) {
					$approverIds[] = $approver['id'];
					if (isset($approver['firebase_id']) && !empty($approver['firebase_id'])) {
						$firebaseIds[] = $approver['firebase_id'];
					}
				}
            }
			if (isset($update_data['approve_status']) && $update_data['approve_status'] === self::STATUS_PENDING) {
				$approvalLogId = $this->addApprovalLog($this->request, $userId, self::ENTITY_USER, $approverIds, self::STATUS_PENDING, $userId,null,$update_data['org_id']);
			}
			foreach ($firebaseIds as $firebaseId) {
				$this->sendPushNotification(
                    $this->request,
					self::NOTIFICATION_TYPE_APPROVAL,
					$firebaseId,
					[
						'phone' => $phone,
						'update_status' => self::STATUS_APPROVED,
						'approval_log_id' => $approvalLogId
                    ],
                    $update_data['org_id']
				);
			}
            $user['approvers'] = $approverList;
            return response()->json(['status'=>'success', 'data'=>$user, 'message'=>''],200);
        }else{
            return response()->json(['status'=>'error', 'data'=>$user, 'message'=>'Invalid Mobile Number'],404);
        }
        
    }

    public function approveuser($approvalLogId)
	{
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
		$approvalLog = ApprovalLog::find($approvalLogId);
		if ($approvalLog === null) {
			return response()->json(['status' => 'error', 'data' => '', 'message' => 'No approval record found.'], 403);
		}
		if (!$this->request->filled('update_status')) {
			return response()->json(['status' => 'error', 'data' => '', 'message' => 'Status is missing.'], 403);
		}
		$status = $this->getStatus($this->request->update_status);
		if ($status === false) {
			return response()->json(['status' => 'error', 'data' => '', 'message' => 'Invalid value passed.'], 403);
		}
		switch($approvalLog->entity_type) {
			case self::ENTITY_USER:
				DB::setDefaultConnection('mongodb');
				$user = User::find($approvalLog->entity_id);
				$user->update([
					'approve_status' => $status
				]);
				break;
		}
        $this->connectTenantDatabase($this->request);

        $approvalLog->update([
            'status' => $status, 
            'reason' => ($this->request->filled('reason'))?$this->request->input('reason'):''
            ]);
        
		return response()->json(['status'=>'success', 'data'=>'Status changed successfully', 'message'=>'']);
    }

    public function upload()
    {
        if (!$this->request->filled('type')) {
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => 'Please specify type field and values must be either form, profile or story'
                    ],
                    400
                );
        }
        $types = [
            'profile' => 'BJS/Images/profile',
            'form' => 'BJS/Images/forms',
            'story' => 'BJS/Images/stories'
        ];
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
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

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

	public function getApprovalLog()
	{
		try {
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			if (!$this->request->filled('status')) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'Status is missing.'], 403);
			}
			$status = $this->getStatus($this->request->status);
			if ($status === false) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'Invalid value passed.'], 403);
			}
			$approvalLogs = ApprovalLog::where(['status' => $status, 'approver_ids' => $this->request->user()->id])->get()->all();
			foreach ($approvalLogs as &$approvalLog) {
				switch($approvalLog->entity_type) {
					case self::ENTITY_USER:
						DB::setDefaultConnection('mongodb');
                        $user = User::find($approvalLog->entity_id);
                        if($user == null){
                            $approvalLog['entity'] = [
                                'user' => []
                            ];
                            break;
                        }
						$organisation = Organisation::find($user->org_id);
						$role = \App\Role::find($user->role_id);
						$this->connectTenantDatabase($this->request);
						$project = \App\Project::find($user->project_id);
						$approvalLog['entity'] = [
							'user' => [
								'name' => $user->name,
								'role' => $role,
								'location' => $user->location,
								'project' => $project,
								'organisation' => $organisation
							]
						];
						break;
				}
			};
			return response()->json(['status' => 'success', 'data' => $approvalLogs, 'message' => 'Fetched approval logs']);
		} catch(\Exception $exception) {
			return response()->json(
                    [
                        'status' => 'error',
                        'data' => null,
                        'message' => $exception->getMessage()
                    ],
                    404
                );
		}
	}
}

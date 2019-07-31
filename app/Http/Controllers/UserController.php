<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Maklad\Permission\Models\Role;
use Maklad\Permission\Models\Permission;
use Dingo\Api\Routing\Helpers;
use App\Organisation;
use App\Project;
use App\RoleConfig;
use Illuminate\Support\Facades\DB;
use App\ApprovalLog;
use Carbon\Carbon;
use Illuminate\Support\Arr;
 
class UserController extends Controller
{
    use Helpers;

	protected $types = [
            'profile' => 'BJS/Images/profile',
            'form' => 'BJS/Images/forms',
            'story' => 'BJS/Images/stories'
        ];

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
            $this->getUserAssociatedData($user);
            return response()->json(['status'=>'success', 'data'=>$user, 'message'=>''],200);
        }else{
            return response()->json(['status'=>'error', 'data'=>$user, 'message'=>'User Not Found'],404);
        }
    }
public function test($id)
{
	$module = Permission::where('id',$id)->first();
	return $module;
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
		$userId = $user['id'];
		
		$userLocation = $user['location'];
        if($user) {
            $update_data = json_decode(file_get_contents('php://input'), true);
			 
            if (isset($update_data['type']) && !empty($update_data['type'])) {
                if ($update_data['type'] !== 'organisation') {
                    $update_data['associate_id'] = $update_data['org_id'];
                    $orgId = \App\Role::find($update_data['role_id'])->org_id;
                    $update_data['org_id'] = $orgId;
                }
            }
			  // return response()->json(['status'=>'success', 'data'=>$update_data, 'message'=>''],200);
            $update_project_id = (isset($update_data['project_id']) && is_array($update_data['project_id']))?$update_data['project_id'][0]:$update_data['project_id'];
            if (
                (isset($update_data['org_id']) && $update_data['org_id'] != $user['org_id'])
                ||
                (isset($update_data['project_id']) && !is_array($user['project_id']))
                ||
                (!in_array($update_project_id,$user['project_id']))
                ||
                (isset($update_data['role_id']) && $update_data['role_id'] != $user->role_id)
				||
				(isset($update_data['location']) && $user['location'] == null)
				||
				(isset($update_data['location']) && $user['location'] != null && $this->compareLocation($update_data['location'], $user['location']))
                ) {
                    $update_data['approve_status'] = 'pending';
            }
            if (isset($update_data['phone'])) {
                unset($update_data['phone']);
            }
            if (isset($update_data['password'])) {
                unset($update_data['password']);
            }
            if(isset($update_data['role_id']) && $update_data['role_id'] != $user['role_id']){
                 $user['location'] = [];


                 $user->save(); 
            }
				//var_dump($user);
                // exit;  
            $user->update($update_data);

			$approverList = [];
			$approverIds = [];
            $firebaseIds = [];
            $approverUsers=[];
			$approvalLogId = '';
			if (isset($update_data['role_id'])) {
                $approverList = $this->getApprovers($this->request, $update_data['role_id'], $userLocation, $update_data['org_id']);
               
				if(empty($approverList))
				{
				  $approverList = User::where('is_admin',true)->where('approved',true)->where('org_id',$update_data['org_id']);
				}
				
				
				foreach($approverList as $approver) {
					$approverIds[] = $approver['id'];
					if (isset($approver['firebase_id']) && !empty($approver['firebase_id'])) {
						$firebaseIds[] = $approver['firebase_id'];
                    }
                    $approver = $this->getUserAssociatedData($approver);
                    array_push($approverUsers,$approver);
				}
            }

            $this->connectTenantDatabase($this->request);
			
				$approvalLogId = $this->addApprovalLog($this->request, $userId, self::ENTITY_USER, $approverIds, self::STATUS_PENDING, $userId," ",$update_data['org_id']);
              
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
            $user['approvers'] = $approverUsers;
            $user = $this->getUserAssociatedData($user);

            return response()->json(['status'=>'success', 'data'=>$user, 'message'=>''],200);
        }else{
            return response()->json(['status'=>'error', 'data'=>$user, 'message'=>'Invalid Mobile Number'],404);
        }
        
    }

    public function getUserAssociatedData($user){
        DB::setDefaultConnection('mongodb');
        if(isset($user->org_id)){
            $organisation = Organisation::find($user->org_id);
            $org_object = new \stdClass;
            $org_object->_id = $organisation->id;
            $org_object->name = $organisation->name;
            $user->org_id = $org_object;
        }
        if(isset($user->role_id)){
            $role = \App\Role::find($user->role_id);
            $role_object = new \stdClass;
            $role_object->_id = $role->id;
            $role_object->name = $role->display_name;
            $user->role_id = $role_object;
        }
        
        if(isset($user->location) && isset($user->org_id)){
            $database = $this->connectTenantDatabase($this->request,$organisation->id);
            if ($database !== null) {
                $location =  new \stdClass;
                foreach($user->location as $level => $location_level){
                    $level_data = array();
                    foreach ($location_level as $location_id){
                    if ($level == 'state'){
                        $location_obj = \App\State::find($location_id);
                    }
                    if ($level == 'district'){
                        $location_obj = \App\District::find($location_id);
                    }
                    if ($level == 'taluka'){
                        $location_obj = \App\Taluka::find($location_id);
                    }
                    if ($level == 'village'){
                        $location_obj = \App\Village::find($location_id);
                    }
                    $location_std_obj =  new \stdClass;
                    $location_std_obj->_id = $location_obj->id; 
                    $location_std_obj->name = $location_obj->name; 
                    array_push($level_data,$location_std_obj);
                    }
                    $location->{$level} = $level_data;
                }
                $user->location = $location;
            }
        }

        if(isset($user->project_id)){
            $database = $this->connectTenantDatabase($this->request,$organisation->id);
            $projects = array();
            if ($database !== null) {
           
            foreach($user->project_id as $project_id){
                
                $project = Project::find($project_id); 
                //var_dump($database); exit;
                $project_object = new \stdClass;
                $project_object->_id = $project->id;
                $project_object->name = $project->name;
                array_push($projects,$project_object);
            }
            
            $user->project_id = $projects;
            }
        }

        return $user;
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
            'event' => 'BJS/Images/events',
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

	public function uploadImages()
	{
		if (!$this->request->filled('type')) {
			return response()->json(
				[
					'status' => 'error',
					'data' => '',
					'message' => 'Please specify type field and value must be either form, profile or story'
				],
				400
			);
        }

        if (!isset($this->types[$this->request->type])) {
			return response()->json(['status' => 'error', 'data' => '', 'message' => 'Invalid type value'], 400);
        }

		$urls = [];

		if ($this->request->file('images') === null) {
			return response()->json(['status' => 'error', 'data' => '', 'message' => 'Images not found'], 400);
		}

		foreach ($this->request->file('images') as $image) {
			if ($image->isValid()) {
				$name = $image->getClientOriginalName();
				$s3Path = $image->storePubliclyAs($this->types[$this->request->type], $name, 's3');

				if ($s3Path == null || !$s3Path) {
					continue;
				}
				$urls[] = 'https://' . env('AWS_BUCKET') . '.' . env('AWS_URL') . '/' . $s3Path;
			}
		}
		if (count($urls) === 0) {
			return response()->json(['status' => 'error', 'data' => ['urls' => $urls], 'message' => 'Error while uploading images in S3'], 403);
		}
		return response()->json(['status' => 'success', 'data' => ['urls' => $urls], 'message' => 'Images uploaded successfully in S3']);
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
    
    public function getUsers() {
        $user = $this->request->user();
        //set pagination variables
        $limit = (int)$this->request->filled('limit') ?(int)$this->request->input('limit'):50;
        $order = $this->request->filled('order') ? $this->request->input('order'):'desc';
        $field = $this->request->filled('field') ? $this->request->input('field'):'createdDateTime';
        
        //check for query params
        $role = $this->request->filled('role')?$this->request->input('role'):null;
        $project = $this->request->filled('project')?$this->request->input('project'):null;
        $organization = $this->request->filled('organization')?$this->request->input('organization'):null;
        #$location = $this->request->filled('location')?$this->request->input('location'):null;
        $location = null;
        foreach($this->request->all() as $key=>$value)
        {
            if (strpos($key, 'location') !== false) {
                $location_level = explode('_',$key);
                $location[$location_level[1]]= explode(',',$value);
            }
        }   
        //var_dump($location);exit;
        if(is_null($role) && is_null($project) && is_null($organization) && is_null($location)){
            return response()->json(['status' => 'error',
            'data' => [],
            'message '=> 'Please set query params'],
            400); 
        }

        $roles = isset($role)?explode(',',$role): [];
        $projects =isset($project)?explode(',',$project): [];
        $organizations =isset($organization)?explode(',',$organization): [];
        $location =isset($location)?$location:[];
        $users = User::select(['name','gender','email','role_id','project_id','org_id','location'])
                ->where('isDeleted','!=',true)
                ->where('is_admin','!=',true)
                ->where(function($q) use ($roles) {
                    if(!empty($roles)){
                        $q->whereIn('role_id',$roles);
                    }
                })   
                ->where(function($q) use ($projects) {
                    if(!empty($projects)){
                        $q->whereIn('project_id',$projects);
                    }
                })
                ->where(function($q) use ($organizations) {
                    if(!empty($organizations)){
                        $q->whereIn('org_id',$organizations);
                    }
                })  
                ->where(function($q) use ($location) {
                    foreach ($location as $level => $location) {
                        $q->whereIn('location.' . $level, $location);
                    }
                })               
                ->orderBy($field, $order)
                ->paginate($limit);
        
        $users_list = [];
        foreach($users->items() as &$user){
            $user_data = $this->getUserAssociatedData($user);
            array_push($users_list,$user_data);
        }
        //exit;

        $result = [];
        $result['Per Page'] = $users->perPage();
        $result['Total Pages'] = $users->lastPage();
        $result['Total number of records'] = $users->total(); 

        return response()->json(['status' => 'success',
                                    'metadata' => [$result],
                                    'data' => $users_list,
                                    'message '=> ''],
                                    200);
    }
	
	public function addmember1(Request $request,$org_id)
	{
		  /* $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }  */

        $data=User::all();
        
		if($data)
		{
			$response_data = array('status' =>'success','data' => $data);
            return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'error','data' => 'No rows found please check user id');
            return response()->json($response_data,300); 
		}
		
	}
}

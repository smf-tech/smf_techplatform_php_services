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
use App\JurisdictionType;
use App\Jurisdiction;
use Carbon\Carbon;
use Illuminate\Support\Arr;

use Illuminate\Support\Facades\Storage;

 
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
		$this->logInfoPath = "logs/Profile/DB/logs_".date('Y-m-d').'.log';
        $this->logLocationUpdate = "logs/LocationUpdate/DB/logs_".date('Y-m-d').'.log';
    }

    public function getUserDetails() {
         $user = $this->request->user();
         
        $header = getallheaders();
          if(isset($header['orgId']) && ($header['orgId']!='') 
            && isset($header['projectId']) && ($header['projectId']!='')
            && isset($header['roleId']) && ($header['roleId']!='')
            )
          { 
            $userProfile = [];
            $userProfile['org_id'] =  $header['orgId'];
            $userProfile['project_id'] =  $header['projectId'];
            $userProfile['role_id'] =  $header['roleId'];

             if($user && $userProfile) {
                $userProfileInfo = $this->getUserProfileData($user,$userProfile);


                return response()->json(['status'=>'success', 'data'=>$userProfileInfo, 'message'=>''],200);
            }else{
                return response()->json(['status'=>'error', 'data'=>$user, 'message'=>'User Not Found'],404);
            }



          }else{

            
            $user = $this->request->user();


           

            if(isset($user->orgDetails))
            {
                
                
                $userFirstProfile = $user->orgDetails[0];
                if($userFirstProfile)
                 {   
                    $userProfile['org_id'] =  $userFirstProfile['org_id'];
                    $userProfile['project_id'] =  $userFirstProfile['project_id'];
                    $userProfile['role_id'] =  $userFirstProfile['role_id'];

                    if($user && $userProfile) {
                        $userProfileInfo = $this->getUserProfileData($user,$userProfile);


                        return response()->json(['status'=>'success', 'data'=>$userProfileInfo, 'message'=>''],200);
                    }else{
                        return response()->json(['status'=>'error', 'data'=>$user, 'message'=>'User Not Found'],404);
                    }
               
                }else
                {

                    $message['message'] = "insufficent orgdetails info";
                    $message['function'] = 'getUserDetails'; 
                    $this->logData($this->logerrorPath ,$message,'Error');
                    $response_data = array('status' =>'404','message'=>$message);
                    return response()->json($response_data,200);
                } 

            }
            else
            {
              
                return response()->json(['status'=>'success', 'data'=>$user, 'message'=>''],200);
            }
            
                  
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



    public function userLocationUpdate(Request $request)
    {
         $user = User::
                select('location','_id','project_id','org_id','role_id','orgDetails','phone','approve_status')
                ->where('role_id','5ddfc592d6e2ef4f78207515')
                //->where('phone','9552528581')
                ->get()->toArray();
      
        // echo json_encode($user);
        // die();
        $updateUserData = [];        
        foreach($user as $keyFirst => $valueFirst)
         {
            
                foreach ($valueFirst as $key => $value) {
                   if($key == 'project_id' )
                   {
                        $this->locationInsert($valueFirst);
                   }
                }
              
            
        }
    }

    public function locationInsert($userData)
    {
         $orgArray = [];
         $orgDetailsFlag = 0;
         $orgdetailsUserData=[];   
        foreach ($userData as $key => $value) {
          
            
            if($key == 'orgDetails')
            {
                
                if(empty($value[0]) ){
                    $orgDetailsFlag = 0;
                }
                else{
                     
                     $orgDetailsFlag = 1;
                }
                
            }
        }
        $orgDetailsFlag =0;
        if($orgDetailsFlag == 1)
        {
                   
            $chkUserDetails = User::where('_id',$userData['_id'])
                    ->first();
            $orgProjectIndex = 0;
            $chkUserDetails['orgDetails.'.$orgProjectIndex.'.location'] = $userData['location']??'';

            $status['status']= $chkUserDetails['approve_status'];

            $status['action_by']= $chkUserDetails['_id'];
            $status['reason']= "";

            $chkUserDetails['orgDetails.'.$orgProjectIndex.'.status'] = $status;

            if(is_array($userData['project_id'])){
           $chkUserDetails['orgDetails.'.$orgProjectIndex.'.project_id'] = $userData['project_id'][0]??'';

           }else
           {
           $chkUserDetails['orgDetails.'.$orgProjectIndex.'.project_id'] = $userData['project_id']??'';
           }
            $this->logData($this->logLocationUpdate,$chkUserDetails,'DB');  
            $chkUserDetails->update();
        }
        else{
            
            $userUpdates = User::where('_id',$userData['_id'])->first();

            $status['status']= $userData['approve_status'];
            $status['action_by']= $userData['_id'];
            $status['reason']= "";
            

            $orgArray = [
                    'org_id'=>$userData['org_id'],
                    'project_id'=>$userData['project_id'][0],
                    'role_id'=>$userData['role_id'],
                    'address'=>'',
                    'location'=>$userData['location']??'',
                    'leave_type'=>'ew',
                    'lat'=>'',
                    'long'=>'',
                    'status'=>$status,
                    'approver_user_id'=>$aprovals??'',
                    'dataTime'=>new \MongoDB\BSON\UTCDateTime(Carbon::now()),
                    ];
            $userUpdates['orgDetails'] = [$orgArray];
            //echo json_encode($userUpdates);
            //die();
            $this->logData($this->logLocationUpdate,$userUpdates,'DB');         
            $userUpdates->update();
                 

        }
              
    }

    public function update(Request $request , $phone)
    {  
        $user = User::where('phone', $phone)->first();
		$userId = $user['id'];
         
		$newrolename = ''; 
		$userLocation = $user['location'];
        if($user) {
            if(isset($user['role_id']) && $user['role_id']!='')
            {
    			 $rolename = \App\Role::select('display_name')->where("_id",$user['role_id'])->first();
                
    			 if(isset($rolename['display_name']) && $rolename['display_name']!='')
    			 {
    				 $newrolename =  $rolename['display_name'];
    			 }
            }
            $update_data = json_decode(file_get_contents('php://input'), true);
		    $update_data['function']  = "update";

             

            $update_project_id = (isset($update_data['project_id']) && is_array($update_data['project_id']))?$update_data['project_id'][0]:$update_data['project_id'];
            
            //$update_data['org_id'];
            

			$this->logData($this->logInfoPath,$update_data,'DB');  
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


			//$user['device_id'] = ($this->request->has('device_id')) ? $this->request->device_id : '';		
			
			//$update_data['status.action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s'))); 
			
            if(isset($update_data['role_id']) && $update_data['role_id'] != $user['role_id']){
                 $user['location'] = []; 
                 $user->save(); 
            }
			$user['status.status'] = 'pending';
			$user['status.action_by'] = $userId;	 
			$user['status.reason'] = '';
			//
			
            $status['status'] = 'pending';
            $status['action_by'] = $userId;     
            $status['reason'] = '';
			
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
				$aprovals = [];

				foreach($approverList as $approvals)
				{
					array_push($aprovals, $approvals['_id']);
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
			
            $profileCnt = 0;
            $insertNewProfile = '';
            $projectFound = 0;
            $projectIndex = 0;
            
            if( isset($user->orgDetails) && count($user->orgDetails) > 0)
            {
                
             
                foreach ($user->orgDetails as $keyOne => $orgData)
                {
                 foreach ($orgData as $key => $value)
                    {
                        if($key == 'project_id')
                            { 
                               if($value == $update_project_id)
                               { 
                                    $projectFound = 1;
                                    $projectIndex =  $keyOne;
                                    break;
                               }
                               
                                $profileCnt = $profileCnt + 1;
                            }  
                    }
                }
            }


             
            $orgArray = [
                'org_id'=>$update_data['org_id'],
                'project_id'=>$update_project_id,
                'role_id'=>$update_data['role_id'],
                'address'=>'',
                'location'=>$update_data['location']??'',
                'leave_type'=>'ew',
                'lat'=>'',
                'long'=>'',
                'status'=>$status,
                'approver_user_id'=>$aprovals??'',
                'dataTime'=>new \MongoDB\BSON\UTCDateTime(Carbon::now()),
                ];


         
          if($projectFound == 1)
            {
               
                $user['orgDetails.'.$projectIndex] = $orgArray; 

            }else
            {
		
                if(isset($user['orgDetails']) && count($user['orgDetails'])>0)
                {    
                    $newArrayIndex = count($user['orgDetails']); 
                    $user['orgDetails.'.$newArrayIndex] = $orgArray;
                }
                else
               {
                  $user['orgDetails'] = [$orgArray]; 
               } 
            }
            
            $user->update($update_data);
            $this->connectTenantDatabase($this->request,$update_data['org_id']);
			 
				$approvalLogId = $this->addApprovalLog($this->request, $userId, self::ENTITY_USER, $approverIds, self::STATUS_PENDING, $userId," ",$update_data['org_id']);
             
			foreach ($firebaseIds as $firebaseId) {  
				$this->sendPushNotification(
                    $this->request,
					self::NOTIFICATION_TYPE_APPROVAL,
					$firebaseId,
					[ 
						'phone' => $phone,
						'update_status' => self::STATUS_APPROVED,
						'approval_log_id' => $approvalLogId,
						'rolename' => $newrolename
                    ],
                    $update_data['org_id']
				);
			} 
            $user['approvers'] = $approverUsers;

           

        if(isset($update_data['role_id'])){

            $database = $this->connectTenantDatabase($this->request,$update_data['org_id']);
            //$multiple_location_level = array();
            if ($database !== null) {
            
                $roleConfig = \App\RoleConfig::where('role_id',$update_data['role_id'])->get();
               
                if($roleConfig && isset($roleConfig[0]->level))
                {
                 $jurisdictionLevel = \App\Jurisdiction::find($roleConfig[0]->level);
                 
                  $jurisdictionLevel_object = new \stdClass;
                 $jurisdictionLevel_object->_id = $jurisdictionLevel->_id;
                 $jurisdictionLevel_object->name = $jurisdictionLevel->levelName;
                 
                }

                $user->multiple_location_level = $jurisdictionLevel_object;
            }    
         }



            $user = $this->getUserAssociatedData($user);
			$response_data = ['status'=>'success', 'data'=>$user, 'message'=>''];
			
			$this->logData($this->logInfoPath,$response_data,'DB');
			
            return response()->json($response_data,200);
        }else{
			$response_data = ['status'=>'error', 'data'=>$user, 'message'=>'Invalid Mobile Number'];
			
			$this->logData($this->logInfoPath,$response_data,'DB'); 			
            return response()->json($response_data,404);
        }
        
    }

    public function getUserAssociatedData($user){


        DB::setDefaultConnection('mongodb');
        if(isset($user->org_id)){
            $organisation = Organisation::find($user->org_id);
            $org_object = new \stdClass;
            $org_object->_id = $organisation->_id;
            $org_object->name = $organisation->name;
            $user->org_id = $org_object;
        }
        if(isset($user->role_id)){
            $role = \App\Role::find($user->role_id);
            $role_object = new \stdClass;
            $role_object->_id = $role->_id;
            $role_object->name = $role->display_name;
			
			if (isset($role->role_code)) {
				$role_object->role_code = $role->role_code;
			} else {
				$role->role_code = '100';
			}
            $user->role_id = $role_object;
        }
       
        if(isset($user->location) && isset($user->org_id)){

           
            $database = $this->connectTenantDatabase($this->request,$organisation->_id);
            if ($database !== null) {
                $location =  new \stdClass;
                foreach($user->location as $level => $location_level){
                    $level_data = array(); 
                    foreach ($location_level as $location_id){
						if(isset($location_id) && $location_id !='') { 
                        if ($level == 'country'){
                            $location_obj = \App\Country::find($location_id);
                        }
    					if ($level == 'city'){
                            $location_obj = \App\City::find($location_id);
                        }
    					if ($level == 'state'){
                            $location_obj = \App\State::find($location_id);
                        }
                        if ($level == 'district'){
                            $location_obj = \App\District::find($location_id);
                        }
                        if ($level == 'taluka'){
                            $location_obj = \App\Taluka::find($location_id);
                        }
                         if ($level == 'cluster'){
                                $location_obj = \App\Cluster::find($location_id);
                        }
                        if ($level == 'village'){
                            $location_obj = \App\Village::find($location_id);
                        }
                        if ($level == 'school'){
                            $location_obj = \App\School::find($location_id);
                        }
                        
                         }
                    
                    //$location_std_obj =  new \stdClass;
                     if(!empty($location_obj)){    
                    //$location_std_obj =  new \stdClass;  
                    $location_std_obj['_id'] = $location_obj->_id; 
                    $location_std_obj['name'] = $location_obj->name; 
                    array_push($level_data,$location_std_obj);
                    }}
                    $location->{$level} = $level_data;

                   $user->location = $location;
                     
                }

                 if(empty($location_obj)){    
                   unset( $user->location);
                  }  
            }

        }

        if(isset($user->project_id)){
            $database = $this->connectTenantDatabase($this->request,$organisation->_id);
            $projects = array();
            if ($database !== null) {
           
            foreach($user->project_id as $project_id){
                
                $project = Project::find($project_id); 
                //var_dump($database); exit;
                $project_object = new \stdClass;
                $project_object->_id = $project->_id;
                $project_object->name = $project->name;
                array_push($projects,$project_object);
            }
            
            $user->project_id = $projects;
            if(isset($project->logo_path) && $project->logo_path!= '' )
            {
                $user->current_project_logo = $project->logo_path;
            }else{
                $user->current_project_logo = "https://sujlamsuflam.s3.ap-south-1.amazonaws.com/octops.png";
            } 

            $jurisdictionType = JurisdictionType::select('_id','jurisdictions')
                            ->where('project_id',$user->project_id[0]->_id)
                            ->where('is_deleted',0)
                            ->first();
                if($jurisdictionType){              
                $user->jurisdiction_type_id = $jurisdictionType->_id;
                }
            }
        }



       
        

        return $user;
    }



    public function getUserProfileData($user,$userProfile)
    {
   
        DB::setDefaultConnection('mongodb');
        if(isset($userProfile['org_id'])){
            $organisation = Organisation::find($userProfile['org_id']);
            $org_object = new \stdClass;
            $org_object->_id = $organisation->_id;
            $org_object->name = $organisation->name;
            $user->org_id = $org_object;
        }
        if(isset($userProfile['role_id'])){
            $role = \App\Role::find($userProfile['role_id']);
            $role_object = new \stdClass;
            $role_object->_id = $role->_id;
            $role_object->name = $role->display_name;
            
            if (isset($role->role_code)) {
                $role_object->role_code = $role->role_code;
            } else {
                $role->role_code = '100';
            }
            $user->role_id = $role_object;
        }

        if(isset($userProfile['project_id'])){
            $database = $this->connectTenantDatabase($this->request,$userProfile['org_id']);
            $projects = array();
            if ($database !== null) {
           
          //  foreach($userProfile['project_id'] as $project_id){
                
                $project = Project::find($userProfile['project_id']); 
                //var_dump($database); exit;
                $project_object = new \stdClass;
                $project_object->_id = $project->_id;
                $project_object->name = $project->name;
                array_push($projects,$project_object);
            //}
            
            $user->project_id = $projects;
            $user->jurisdiction_type_id = $project->jurisdiction_type_id;
            if(isset($project->logo_path) && $project->logo_path!= '' )
            {
                $user->current_project_logo = $project->logo_path;
            }else{
                $user->current_project_logo = "https://sujlamsuflam.s3.ap-south-1.amazonaws.com/octops.png";
            } 

            // $jurisdictionType = JurisdictionType::select('_id','jurisdictions')
            //                 ->where('project_id',$user->project_id[0]->_id)
            //                 ->where('is_deleted',0)
            //                 ->first();
            //     if($jurisdictionType){              
            //     $user->jurisdiction_type_id = $jurisdictionType->_id;
            //     }
            }
        }


        if(isset($userProfile['role_id'])){

            $database = $this->connectTenantDatabase($this->request,$userProfile['org_id']);
            //$multiple_location_level = array();
            if ($database !== null) {
            
                $roleConfig = \App\RoleConfig::where('role_id',$userProfile['role_id'])->get();
                if($roleConfig && isset($roleConfig[0]->level))
                {
                 $jurisdictionLevel = \App\Jurisdiction::find($roleConfig[0]->level);
                 
                 $jurisdictionLevel_object = new \stdClass;
                 $jurisdictionLevel_object->_id = $jurisdictionLevel->_id;
                 $jurisdictionLevel_object->name = $jurisdictionLevel->levelName;
                // array_push($multiple_location_level,$jurisdictionLevel_object);
                 
                }
                else{

                    $response_data = array('status' =>'200','message'=>'Role configuration not found');
                    return response()->json($response_data,403);
                }


                $user->multiple_location_level = $jurisdictionLevel_object;
            }    
        }
       
        $orgDetailsCnt = 0;
        $profileCnt = 0;
        
        foreach($user->orgDetails as $userDataArr)
         {
             foreach($userDataArr as $key => $value)
             {
                if($key == 'project_id' && ($userProfile['project_id'] == $value))
                  {

                    $profileCnt = $orgDetailsCnt;

                  } 

             }
             $orgDetailsCnt = $orgDetailsCnt+1;
         }

    
        if(isset($user->orgDetails[$profileCnt]['location']) && isset($userProfile['org_id'])){

       
                $location =  new \stdClass;
                foreach($user->orgDetails[$profileCnt]['location'] as $level => $location_level){
                    $level_data = array(); 
                    foreach ($location_level as $location_id){
                        if(isset($location_id) && $location_id !='') { 
                        if ($level == 'country'){
                            $location_obj = \App\Country::find($location_id);
                        }
                        if ($level == 'city'){
                            $location_obj = \App\City::find($location_id);
                        }
                        if ($level == 'state'){
                            $location_obj = \App\State::find($location_id);
                        }
                        if ($level == 'district'){
                            $location_obj = \App\District::find($location_id);
                        }
                        if ($level == 'taluka'){
                            $location_obj = \App\Taluka::find($location_id);
                        }
                         if ($level == 'cluster'){
                                $location_obj = \App\Cluster::find($location_id);
                        }
                        if ($level == 'village'){
                            $location_obj = \App\Village::find($location_id);
                        }
                        if ($level == 'school'){
                            $location_obj = \App\School::find($location_id);
                        }
                         }
                    
                    //$location_std_obj =  new \stdClass;
                     if(!empty($location_obj)){    
                    //$location_std_obj =  new \stdClass;  
                    $location_std_obj['_id'] = $location_obj->_id; 
                    $location_std_obj['name'] = $location_obj->name; 
                    array_push($level_data,$location_std_obj);
                    }}
                    $location->{$level} = $level_data;

                   $user->location = $location;
                   $user->status = $user->orgDetails[$profileCnt]['status'];
                   $user->approve_status = $user->orgDetails[$profileCnt]['status']['status'];
                    
                }
                   
                 if(empty($location_obj)){    
                   unset( $user->location);
                  }  
          

        }
        unset($user->orgDetails);
        
        //echo json_encode($user);
        //die();
        return $user;
  

    }

    public function getUserProfileDetails(){

        $user = $this->request->user();
        $profileCnt = 0;
        $profileArr =[];
        if($user and $user->orgDetails != '')
           {
                foreach($user->orgDetails as $dataArr){

                    foreach($dataArr as $key => $value)
                    {
                        if($key == 'role_id')
                        {
                            DB::setDefaultConnection('mongodb');
                            $profileArr[$profileCnt]['role_id'] =$value;
                            
                            $roletData =  \App\Role::
                                          select('display_name')
                                          ->where('_id',$value)
                                          ->get();
                                                         
                           $profileArr[$profileCnt]['role_title'] = $roletData[0]['display_name']??'Unknown';
                        }
                       // die();


                        if($key == 'org_id')
                        {
                            DB::setDefaultConnection('mongodb');
                            $profileArr[$profileCnt]['org_id'] =$value;
                            
                            $orgtData =  Organisation::
                                          select('display_name')
                                          ->where('_id',$value)
                                          ->get();
                                                         
                            $profileArr[$profileCnt]['org_title'] = $orgtData[0]['display_name']??'Unknown';
                        }

                        if($key == 'project_id')
                        {
                            $database = $this->connectTenantDatabase($this->request);
                            $profileArr[$profileCnt]['project_id'] =$value;
                            $projectData =  \App\Project::
                                                        select('display_name')
                                                        ->where('_id',$value)
                                                        ->get();
                                                     
                            $profileArr[$profileCnt]['project_title'] = $projectData[0]['display_name']??'Unknown';
                        }

                        else {

                            $profileArr[$profileCnt][$key] = $value;
                        }
                    } 
                    $profileCnt = $profileCnt + 1;
                }

           } else{

                $response_data = array('status' =>'200','message'=>'user or organization details are not found');
                return response()->json($response_data,403);
           }
        

        if($profileArr)
        {
            $response_data = array('status' =>'200','message'=>'success','data' => $profileArr);
            return response()->json($response_data,200); 
        }
        else
        {
            $response_data = array('status' =>'200','message'=>'error');
            return response()->json($response_data,403);
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
		// return response()->json(['status' => 'success', 'data' => 'ddddddddddddd', 'message' => 'Image successfully uploaded in S3']);
        
		
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
            'profile' => 'profile',
            'form' => 'forms',
            'event' => 'events',
            'story' => 'stories',
			'pdf' => 'pdf'
        ];
        if (!isset($types[$this->request->type])) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'Invalid type value'], 400);
        }
        if ($this->request->file('image')->isValid()) {
			
            $fileInstance = $this->request->file('image');
            $name = $fileInstance->getClientOriginalName();
            
            //$s3Path = $this->request->file('image')->storePubliclyAs($types[$this->request->type], $name, 's3');
			$s3Path = $this->request->file('image')->storePubliclyAs(env('BJSOCTOPUS_ENV').'/'.$types[$this->request->type], $name, 'octopus');
					
            if ($s3Path == null || !$s3Path) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
            }
            //$result = 'https://' . env('AWS_BUCKET') . '.' . env('AWS_URL') . '/' . $s3Path;
			$result = 'https://' . env('BJSOCTOPUS_AWS_CDN_PATH') . '/'.$s3Path;
			
			$this->logData($this->logInfoPath,$result,'DB');
			
            return response()->json(['status' => 'success', 'data' => ['url' => $result], 'message' => 'Image successfully uploaded in S3']);
        }
    }

	public function uploadImages()
	{
			// return response()->json(['status' => 'success', 'data' => 'ddddddddddddd', 'message' => 'Image successfully uploaded in S3']);
     
       
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
				//$s3Path = $image->storePubliclyAs($this->types[$this->request->type], $name, 's3');
				$s3Path = $image->storePubliclyAs(env('BJSOCTOPUS_ENV').'/'.$types[$this->request->type], $name, 'octopus');
			

				if ($s3Path == null || !$s3Path) {
					continue;
				}
				//$urls[] = 'https://' . env('AWS_BUCKET') . '.' . env('AWS_URL') . '/' . $s3Path;
				$urls[] = 'https://' . env('BJSOCTOPUS_AWS_CDN_PATH') . '/'.$s3Path;
			
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
        //echo json_encode($user);


        //set pagination variables
        $limit = (int)$this->request->filled('limit') ?(int)$this->request->input('limit'):50;
        $order = $this->request->filled('order') ? $this->request->input('order'):'desc';
        $field = $this->request->filled('field') ? $this->request->input('field'):'createdDateTime';
        
        //check for query params
        $role = $this->request->filled('role')?$this->request->input('role'):null;
        $project = $this->request->filled('project')?$this->request->input('project'):null;
        $organization = $this->request->filled('organization')?$this->request->input('organization'):null;
        $location = $this->request->filled('location')?$this->request->input('location'):null;
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


    public function updateFirebaseId(Request $request)
    {

        $user = $this->request->user();
        $requestjson = json_decode(file_get_contents('php://input'), true);
		$requestjson['function'] = 'updateFirebaseId';
        $this->logData($this->logInfoPath,$requestjson,'DB');  
       if($user){
          $user_data = User::where('_id',$user->_id)->first();  
          $user_data['firebase_id'] = $requestjson['firebase_id'];   
          $user_data->save(); 
          return response()->json(['status'=>'success', 'data'=>$user, 'message'=>'success'],200);
        }else{
            return response()->json(['status'=>'error','message'=>'Invalid Mobile Number'],404);
        }

    }
	
	public function awsImageMove(Request $request) {		
		
		$user = $this->request->user();
    	
		if ($request->type == 1) {
				$userList = User::all();
				
				foreach( $userList as $userData) {
					
					if (isset($userData->profile_pic) && $userData->profile_pic != '' && $userData->profile_pic != null) {
						
						$filePath = $userData->profile_pic;
						
						//$exists = Storage::disk('octopus')->has($filePath);
						//$file = Storage::disk('octopus')->exists($filePath);
						//echo "ewrwrwerwe";exit;
						//var_dump( $file);exit;
						//if ($file) {

							$filecontent = file_get_contents($filePath);
							//$ext = pathinfo($filePath, PATHINFO_EXTENSION); 
							$fileName = basename($filePath);
							
							$aswFileName = env('BJSOCTOPUS_ENV').'/profile/'.$fileName;
							Storage::disk('octopus')->put($aswFileName, $filecontent);
							Storage::disk('octopus')->setVisibility($aswFileName, 'public');
							$url = Storage::disk('octopus')->url($aswFileName);
							$fileName =  'https://'.env('BJSOCTOPUS_AWS_CDN_PATH').'/'.env('BJSOCTOPUS_ENV').'/profile/'.$fileName;
							//print_r( $url);exit;
							if ($url) {
								
								try {
									$user = User::find($userData->_id);
									$user->profile_pic = $fileName;
									$user->save();
									$data = array ('userId' => $user->_id, 
												'message' => 'Profile pic updated',
												'oldImage'=>$filePath,
												'newImage'=>$url);
									$this->logData($this->logInfoPath,$data,'DB');
									
									echo '<pre>';print_r($data);
									
								} catch(\Exception $exception) {
									
									/*$error = array('status' => 'error',							
											'message' => $exception->getMessage()
										);
										$this->logData($this->logInfoPath,$error,'DB');*/
							
										
									//return response()->json($error);
								} 	
								$data = array ('userId' => $user->_id, 
												'message' => 'Profile pic updated',
												'oldImage'=>$filePath,
												'newImage'=>$url);
								$this->logData($this->logInfoPath,$data,'DB');
							
							} else {
							$data = array ('userId' => $user->_id, 
											'message' => 'Not able upload image', 
											'oldImage'=>$filePath,
											'url' => $url);
											
							$this->logData($this->logInfoPath,$data,'DB');
							
							echo '<pre>';print_r($data);
						}
					/*} else {

						$data = array ('userId' => $user->_id, 'message' => 'Profile pic does not exist');
						$this->logData($this->logInfoPath,$data,'DB');
						
						echo '<pre>';print_r($data);
					}*/
					/*$s3 = Storage::disk('octopus');
					$existingImagePath = 'https://bjsoctopus.s3.ap-south-1.amazonaws.com/staging/Profile/test/test.jpg'; // this returns the path of the file stored in the db
					$dd = $s3->delete($existingImagePath);
					var_dump( $dd);exit;*/
					
					} else {

						$data = array ('userId' => $user->_id, 'message' => 'Profile pic is empty');
						$this->logData($this->logInfoPath,$data,'DB');
							
						echo '<pre>';print_r($data);

					}
				}//foreach ends here
		}  else if ($request->type == 2) {

			$database = $this->connectTenantDatabase($request,'5dcfa18c5dda7605c043f2b3');
			
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

			$plannerData = \App\PlannerTransactions::all();
			
			foreach( $plannerData as $data) {
					
					if (isset($data->thumbnail_image) && $data->thumbnail_image != '' && $data->thumbnail_image != null) {
						
						$filePath = $data->thumbnail_image;
				
					
							$filecontent = file_get_contents($filePath);
							//$ext = pathinfo($filePath, PATHINFO_EXTENSION); 
							$fileName = basename($filePath);
							
							$aswFileName = env('BJSOCTOPUS_ENV').'/events/'.$fileName;
							Storage::disk('octopus')->put($aswFileName, $filecontent);
							Storage::disk('octopus')->setVisibility($aswFileName, 'public');
							$url = Storage::disk('octopus')->url($aswFileName);
							$fileName =  'https://'.env('BJSOCTOPUS_AWS_CDN_PATH').'/'.env('BJSOCTOPUS_ENV').'/events/'.$fileName;
							//print_r( $url);exit;
							if ($url) {
								
								try {
									$plData = \App\PlannerTransactions::find($data->_id);
									$plData->thumbnail_image = $fileName;
									$plData->save();
									$result = array ('Event Id' => $plData->_id, 
												'message' => 'Event pic updated',
												'oldImage'=>$filePath,
												'newImage'=>$url);
									$this->logData($this->logInfoPath,$result,'DB');
									
									echo '<pre>';print_r($result);
									
								} catch(\Exception $exception) {
									
									/*$error = array('status' => 'error',							
											'message' => $exception->getMessage()
										);
										$this->logData($this->logInfoPath,$error,'DB');*/
							
										
									//return response()->json($error);
								} 	
								$result = array ('Event Id' => $data->_id, 
												'message' => 'Event pic updated',
												'oldImage'=>$filePath,
												'newImage'=>$url);
								$this->logData($this->logInfoPath,$result,'DB');
							
							} else {
							$result = array ('Event Id' => $data->_id, 
											'message' => 'Not able upload image', 
											'oldImage'=>$filePath,
											'url' => $url);
											
							$this->logData($this->logInfoPath,$result,'DB');
							
							echo '<pre>';print_r($result);
						}
					/*} else {

						$data = array ('userId' => $user->_id, 'message' => 'Profile pic does not exist');
						$this->logData($this->logInfoPath,$data,'DB');
						
						echo '<pre>';print_r($data);
					}*/
					/*$s3 = Storage::disk('octopus');
					$existingImagePath = 'https://bjsoctopus.s3.ap-south-1.amazonaws.com/staging/Profile/test/test.jpg'; // this returns the path of the file stored in the db
					$dd = $s3->delete($existingImagePath);
					var_dump( $dd);exit;*/
					
					} else {

						$result = array ('Event Id' => $data->_id, 'message' => 'Event pic is empty');
						$this->logData($this->logInfoPath,$result,'DB');
							
						echo '<pre>';print_r($result);

					}
				}//foreach ends here
		}	
	}	



      public function mvUserInfo(Request $request)
    {
        $header = getallheaders();
          //$user = $this->request->user();
          if(isset($header['orgId']) && ($header['orgId']!='') 
            && isset($header['projectId']) && ($header['projectId']!='')
            && isset($header['roleId']) && ($header['roleId']!='')
            )
          { 
            $org_id =  $header['orgId'];
            $project_id =  $header['projectId'];
            $role_id =  $header['roleId'];
          }else{

            
            $message['message'] = "insufficent header info";
            $message['function'] = 'checkUser'; 
            $this->logData($this->logInfoPath ,$message,'Error');
            $response_data = array('status' =>'404','message'=>$message);
            return response()->json($response_data,200); 
            // return $message;
          }

           $data = json_decode(file_get_contents('php://input'), true);

           $this->logData($this->logInfoPath,$data,'DB');

           //echo json_encode($data["phone"]);
           if(isset($data["phone"]) && $data["phone"]!='')
           {
              $userData =  User::select('phone','name')
                        ->where('orgDetails.project_id', '=', $project_id)
                        ->where('phone',$data["phone"])->get();

              // echo json_encode($userData);         
               if($userData && count($userData)>0)
                {
                    $response_data = array('status' =>'200','message'=>'success','data' => $userData);
                    return response()->json($response_data,200); 
                }
                else
                {
                    
                    $response_data = array('status' =>'403','message'=>'User not found');
                    return response()->json($response_data,200);
                }          

                        

           }
           else{

            $message['message'] = "phone number is missing";
                        $message['function'] = 'checkUser'; 
                        $this->logData($this->logerrorPath ,$message,'Error');
                        $response_data = array('status' =>'404','message'=>$message);
                        return response()->json($response_data,200);

           }
          // die();
    }
}

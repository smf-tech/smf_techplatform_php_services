<?php 
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\User;
use Maklad\Permission\Models\Role;
use Maklad\Permission\Models\Permission;
use Dingo\Api\Routing\Helpers;
use App\Organisation;
use App\Project;
use App\Module;
use App\RoleConfig;
use App\Event;
use App\EventType; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\ApprovalLog;
use Carbon\Carbon;
use App\Category;
use App\User_detail;
use App\Survey;
use App\PlannerTransactions;
use DateTimeImmutable;
use DateTime;
use App\PlannerLeaveApplications;
use App\UserController;
use App\ApprovalsPending; 
use App\PlannerClaimCompoffRequests; 
use App\Entity;
use App\PlannerHolidayMaster;
use App\PlannerAttendanceTransaction;
use App\PlannerUserLeaveBalance;
use App\MachineMou;
use App\Machine;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;

use Illuminate\Support\Arr;

class EventTaskController extends Controller
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
		$this->logInfoPath = "logs/Event_task/DB/logs_".date('Y-m-d').'.log';
		$this->logerrorPath = "logs/Event_task/ERROR/logs_".date('Y-m-d').'.log';
	}

	
	//fetch all the data from EventType collection
	public function getEventType(Request $request,$org_id)
	{
		$header = getallheaders();
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
			$message['function'] = "getEventType";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}

		$data=EventType::all();
		if($data)
		{
			$response_data = array('status' =>'success','data' => $data);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'success' );
			return response()->json($response_data,200); 
		}
	}
	
	
	//fetch categories from EventType collection
	public function getEventCategory(Request $request,$org_id)
	{
		$header = getallheaders();
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
			$message['function'] = "getEventCategory";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
			}

		$data=EventType::select('id','name')->get();
		if($data)
		{
			$response_data = array('status' =>'200','message'=>'success','data' => $data);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'300','message'=>'No events categories found...' );
			return response()->json($response_data,200); 
		}
	}
	
	//fetch all the status count for pending, rejected, approved
	public function statuscount(Request $request,$user_id,$org_id)
	{
		$header = getallheaders();
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
			$message['function'] = "statuscount";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}

		$datapending=ApprovalLog::where('status','pending')->where('userName',$user_id)->get();
		$datarejected=ApprovalLog::where('status','rejected')->where('userName',$user_id)->get();
		$datarapproved=ApprovalLog::where('status','approved')->where('userName',$user_id)->get();
		
		$data = array(
		"Pending" =>count($datapending),
		"Rejected" =>count($datarejected),
		"Approved" =>count($datarapproved),
		);
		$maindata=array();
		
		foreach($data as $key=>$value)
		{
		  $data1=array(
		  'type' =>$key,
		  'count' =>$value
		  );
		  array_push($maindata,$data1);
		}
		
		if($maindata)
		{
			$response_data = array('status' =>'success','data' => $maindata);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'No rows found please check user id');
			return response()->json($response_data,200); 
		}
		
	}
	
	//fetch all the list of members according to filter
	public function addmember(Request $request)
	{
		$header = getallheaders();
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
			$message['function'] = "addmember";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$request = json_decode(file_get_contents('php://input'), true);
		$request['function'] = "addmember";
		$this->logData($this->logInfoPath ,$request,'DB');
		
		$userdetails = $this->request->user();
		 
		$maindata = array(); 
		if(isset($request['org_id']))
		{	
	
			$org_id = explode(',',$request['org_id']);
			$maindata=User::select('name','orgDetails')->whereIn('orgDetails.org_id',$org_id)->orderBy('name','asc');
			
			if($request['role'] !='')
			{     
				$role = explode(',',$request['role']);
				$maindata->whereIn('orgDetails.role_id',$role);
				  if($request['state']!='')
				{
					$state = explode(',',$request['state']);
					$maindata->whereIn('orgDetails.location.state',$state);
				}
				if($request['district']!='')
				{
					$district = explode(',',$request['district']);
					$maindata->whereIn('orgDetails.location.district',$district);
				}
				if($request['taluka']!='')
				{
					$taluka = explode(',',$request['taluka']);
					$maindata->whereIn('orgDetails.location.taluka',$taluka);
				}
				if($request['village']!='')
				{
					$village = explode(',',$request['village']);
					$maindata->whereIn('orgDetails.location.village',$village); 
				} 
				
				 
			}else{
				$response_data = array('status' =>'404','message'=>'No Roles are Selected');
				return response()->json($response_data,200); 
			}
		    $tempData = $maindata->get();

			$main =array();
			foreach($tempData as $row)
			{ 
				foreach($row['orgDetails'] as $orgRow)
				{
				$role_name = Role::select('display_name')->where('_id',$orgRow['role_id'])->get();
				
				 if(count($role_name)==0){
					
				$temp_arr = array(
				'id'=>$row['_id'],
				'name'=>$row['name'],
				'role_name'=>''
				);
				}
				else{ 
				$temp_arr = array(
				'id'=>$row['_id'],
				'name'=>$row['name'],
				'role_name'=>$role_name[0]['display_name']
				);
				array_push($main,$temp_arr);  
				}
				}
			}
			
			
		}
		 
		 
		if($main)
		{
			$response_data = array('status' =>'200','message'=>'success','data' => $main);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'404','message'=>'No Members Found..');
			return response()->json($response_data,200); 
		}
		
	} 
	
	//fetch all the list of form filter by project and org
	public function addform(Request $request)
	{
		$header = getallheaders();
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
			$message['function'] = "addform";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		// $org_id = $this->request->user();
		$data = json_decode(file_get_contents('php://input'), true);
		$data['function'] = "addform";
		$this->logData($this->logInfoPath ,$data,'DB');
		$projects = '';
		foreach($data['projectIds'] as $project_ids)
		{ 
		  $projects = $projects.",".$project_ids['_id'];
		}	
		 
		$projects = explode(',',$projects);	
		unset($projects[0]);
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
		 
		$maindata=Survey::select('name','entity_id')->whereIn('project_id',$projects)->get();
		
		 
		if($maindata)
		{
			$response_data = array('status' =>'200','message'=>'success','data' => $maindata);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'404','message'=>'error');
			return response()->json($response_data,200); 
		}
		
	}
	
	//submit the attendance for the event(if required)
	public function generateAttendanceCode(Request $request,$eventId)
	{
		
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "generateAttendanceCode";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		$data['payload'] = $eventId;
		$data['function'] = 'generateAttendanceCode';
		$this->logData($this->logInfoPath ,$data,'DB');
		$timestamp = Date('Y-m-d H:i:s');
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
		$currentTime = Carbon::now();  
		$maindata=PlannerTransactions::where('_id',$eventId)->where('is_mark_attendance_required',true)
		->where('schedule.starttiming','<=',$currentTime)->where('schedule.endtiming','>=',$currentTime)
		->first(); 
		  
		if($maindata) 
		{ 
			/* $dateInterval=date_diff(date_create($maindata['mark_attendance_attributes']['generated_on']),date_create($timestamp));
			$reference = new DateTimeImmutable;
			$endTime = $reference->add($dateInterval);
			$sec = $endTime->getTimestamp() - $reference->getTimestamp();
			$milisecond = $sec * 1000; */
			  
			if(!isset($maindata['mark_attendance_attributes']['otp']) || $maindata['mark_attendance_attributes']['otp'] =="")
			{ 
				$otp =  rand(100000,999999);  
				$maindata['mark_attendance_attributes.otp'] = $otp;
				$maindata['mark_attendance_attributes.generated_on'] = $timestamp;
				$maindata['mark_attendance_attributes.otp_ttl'] = '432000';
				$maindata['default.org_id'] = $org_id;
				$maindata['default.updated_by'] = $user->_id;
				$maindata['default.updated_on'] = $timestamp;
				$maindata['default.project_id'] = $project_id;
				
				 try{
				 $maindata->save(); 
				}catch(Exception $e)
				{
					return $e;
				}  
			}
			else{
				$response_data = array('status' =>'200','AttencdenceCode' =>$maindata['mark_attendance_attributes.otp']);
				return response()->json($response_data,200); 
			}
			
		}else{
				$response_data = array('status' =>'200','message'=>'Code can be generated only when Event is Active');
				return response()->json($response_data,200); 
			}
		  
		if($maindata)
		{
			$response_data = array('status' =>'200','message'=>'success','AttencdenceCode' => $otp);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'404','message'=>'Records not found');
			return response()->json($response_data,200); 
		}
		
	}  			
	//submit the attendance for the event(if required)
	public function submitAttendanceEvent(Request $request)
	{
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "submitAttendanceEvent";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		$timestamp = Date('Y-m-d H:i:s');
		 $database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
		
		$request = json_decode(file_get_contents('php://input'), true);
		$request['function'] = "submitAttendanceEvent";
		$this->logData($this->logInfoPath ,$request,'DB');
		$eventId = $request['eventId'];	
		$userId = $request['userId'];	
		$AttendanceCode = $request['AttendanceCode'];	
		 
		$maindata=PlannerTransactions::where('_id',$eventId)->where('is_mark_attendance_required',true)->first();
		  
		if($maindata['mark_attendance_attributes']['otp'] == $AttendanceCode)
		{
			$dateInterval=date_diff(date_create($maindata['mark_attendance_attributes']['generated_on']),date_create($timestamp));
			$reference = new DateTimeImmutable;
			$endTime = $reference->add($dateInterval);
			$sec = $endTime->getTimestamp() - $reference->getTimestamp();
			$milisecond = $sec * 1000;
			  
			// if($maindata['mark_attendance_attributes']['otp_ttl'] >= $milisecond)
			// {
				 
				$count = 0; 
				foreach($maindata['participants'] as $ids)
				{
					if($ids['id'] == $userId)
					{
						if($maindata['participants.'.$count.'.attended_completed'] == false)
						{
						$maindata['attended_completed'] = $maindata['attended_completed'] + 1 ; 
						}
						else{
							$response_data = array('status' =>'200','message'=>'Attendance is Already Marked');
							return response()->json($response_data,200);
						}
						$maindata['participants.'.$count.'.attended_completed'] = true;
						$maindata['default.org_id'] = $org_id;
						$maindata['default.updated_by'] = $user->_id;
						$maindata['default.updated_on'] = $timestamp;
						$maindata['default.project_id'] = $project_id; 
						
						try{
						$maindata->save();	
						$response_data = array('status' =>'200','message'=>'success');
						return response()->json($response_data,200);
						}catch(Exception $e)
						{
							$response_data = array('status' =>'300','message'=>'error','data' => $e);
							return response()->json($response_data,200);
						}
					}
					 else{
						 if(count($maindata['participants']) == $count){
						$response_data = array('status' =>'300','message'=>'error','data' => 'Invalid User');
						return response()->json($response_data,200);
						 }
					} 
					$count++;
				}  
				
				
				
			// }
			// else{
				// $response_data = array('status' =>'200','message'=>'OTP Expired','data' => 'OTP Expired');
				// return response()->json($response_data,200);
			// }
			
		}
		else{
			$response_data = array('status' =>'200','message'=>'Invalid OTP','data' => 'Invalid OTP');
			return response()->json($response_data,200);
		}
		 
	}
	
	
	//fetch all the list of form filter by project and org
	public function event_task(Request $request)
	{ 
 		$header = getallheaders();
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
			$message['function'] = "event_task";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
 

		$user = $this->request->user();
		$database = $this->connectTenantDatabase($request,$user->org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
		$requestjson = json_decode(file_get_contents('php://input'), true);
		$requestjson['function'] = "event_task";
		$this->logData($this->logInfoPath,$requestjson,'DB');
		$timestamp = Date('Y-m-d H:i:s');
		 
		if(isset($requestjson['_id']))
		{
			$maindata = PlannerTransactions::find($requestjson['_id']) ;	
			$maindata['default.updated_by'] = $user['_id'];
			$maindata['default.updated_on'] = $timestamp;

			$maindata['default.org_id'] = $org_id;
			$maindata['default.project_id'] = $project_id;
			$maindata['default.role_id'] = $role_id;

			$actionFlag = 'edit';
			$members = $maindata['participants'];
			 
		}	
		else{
					
			  $maindata = new PlannerTransactions;	
			  $maindata['default.updated_by'] = "";
			  $maindata['default.created_by'] = $user['_id'];
			  $maindata['default.created_on'] = $timestamp;    
			  $maindata['default.updated_on'] = "";
			  $maindata['default.org_id'] = $org_id;
			  $maindata['default.project_id'] = $project_id;
			  $maindata['default.role_id'] = $role_id;
			  $maindata['mark_complete'] = false;
			  $actionFlag = 'insert';
		}
		 
		$maindata['type'] = $requestjson['type'];
		$maindata['title']  = $requestjson['title'];
		$maindata['ownerid']  = $user->_id;
		$maindata['ownername']  = $user->name;
		$maindata['address']  = $requestjson['address'];
		$maindata['description'] = $requestjson['description'];
		if(isset($requestjson['thumbnail_image']))
		$maindata['thumbnail_image'] = $requestjson['thumbnail_image'];
		else $maindata['thumbnail_image'] = '';
		$maindata['schedule.startdatetime'] = $requestjson['schedule']['startdatetime']; 
		$maindata['schedule.enddatetime'] = $requestjson['schedule']['enddatetime'];
		$maindata['schedule.starttiming'] =  new \MongoDB\BSON\UTCDateTime($requestjson['schedule']['startdatetime']);
		$maindata['schedule.endtiming'] = new \MongoDB\BSON\UTCDateTime($requestjson['schedule']['enddatetime']);
	
		$maindata['required_forms'] = $requestjson['required_forms'];
		$maindata['event_status'] = "Active";
		if($requestjson['registration_required'] == "true"){
		$maindata['registration_required'] = $requestjson['registration_required']; 
		 
		$maindata['registration_schedule.startdatetime'] = $requestjson['registration_schedule']['startdatetime'];  
		$maindata['registration_schedule.enddatetime'] = $requestjson['registration_schedule']['enddatetime'];  
		$maindata['registration_schedule.starttiming'] = new \MongoDB\BSON\UTCDateTime($requestjson['registration_schedule']['startdatetime']);  
		$maindata['registration_schedule.endtiming'] = new \MongoDB\BSON\UTCDateTime($requestjson['registration_schedule']['enddatetime']); 
		}else{
			$maindata['registration_required'] = $requestjson['registration_required'];
		} 
		
		if($requestjson['is_mark_attendance_required'] == "true"){ 
			$maindata['is_mark_attendance_required'] = $requestjson['is_mark_attendance_required'];
			//$otp =  rand(100000,999999); 
			$timestamp = Date('Y-m-d H:i:s');
			$maindata['mark_attendance_attributes.otp'] = '';//$otp;
			$maindata['mark_attendance_attributes.generated_on'] = '';//$timestamp;     
			$maindata['mark_attendance_attributes.otp_ttl'] = '';//'3600000'; 
		}
		  else{
			$maindata['is_mark_attendance_required'] = $requestjson['is_mark_attendance_required'];
		}  
		
		$maindata['default.org_id'] = $user['org_id']; 
		$maindata['default.project_id'] = $user['project_id'][0]; 
		
		if(count($requestjson['participants']) > 0)
		{		
		  $count=0;
		 DB::setDefaultConnection('mongodb');
		  
		 foreach($requestjson['participants'] as $participant)
		 {
			$maindata['participants.'.$count] =  $participant;
			$maindata['participants.'.$count.'.attended_completed'] =  false; 
			$count++;
		 }
		 $database = $this->connectTenantDatabase($request,$user->org_id);
			 if($actionFlag == 'insert')
			 {	
			 	$maindata['participants_count'] = $count;
			 }	
			 $maindata['attended_completed'] = 0;
		
		}
		
		else {$maindata['participants'] = [];
		$maindata['participants_count'] = 0;
		}		
		try{ 
		     //echo json_encode($maindata);die();
			$success = $maindata->save();
			 
			DB::setDefaultConnection('mongodb');
			  if($actionFlag == 'insert'){
			foreach($requestjson['participants'] as $row)
			{   
				 
				$firebase_id = User::where('_id',$row['id'])->first(); 
				 $rolename = \App\Role::select('display_name')->where("_id",$firebase_id['role_id'])->first();
		
					 if(isset($rolename['display_name']))
					 { 
						$newrolename =  $rolename['display_name'];
					 }	
				if($requestjson['type'] == 'Event'&& isset($firebase_id['firebase_id']) && $firebase_id['firebase_id'] !="" && $firebase_id['firebase_id'] !="\n")
				{				
					$this->sendPushNotification(
					$this->request,
					self::NOTIFICATION_TYPE_EVENT_CREATED,
					$firebase_id['firebase_id'],
					[
						'phone' => "9881499768",
						'title' => $requestjson['title'],
						'update_status' => self::NOTIFICATION_TYPE_EVENT_CREATED,
						'model' => "Planner",
						'rolename' => $newrolename,
						'approval_log_id' => "Testing"
					],
					$firebase_id['org_id']
					); 
				} 
				if($requestjson['type'] == 'Task' && isset($firebase_id['firebase_id']) && $firebase_id['firebase_id'] !="" && $firebase_id['firebase_id'] !="\n")
				{		
					$rolename = \App\Role::select('display_name')->where("_id",$firebase_id['role_id'])->first();
		
					 if(isset($rolename['display_name']))
					 { 
						$newrolename =  $rolename['display_name'];
					 }	
					 
					$this->sendPushNotification(
					$this->request,
					self::NOTIFICATION_TYPE_TASK_ASSIGN,
					trim($firebase_id['firebase_id'],'\n'),
					[
						'phone' => "9881499768",
						'title' => $requestjson['title'],
						'model' => "Planner",
						'rolename' => $newrolename,
						'update_status' => self::NOTIFICATION_TYPE_TASK_ASSIGN,
						'approval_log_id' => "Testing"
					],
					$firebase_id['org_id']
					); 
				}   
			 
				 
			  }}
			  else{
				  foreach($members as $mem)
				  {
					 $firebase_id = User::where('_id',$mem['id'])->first(); 
					 
					$rolename = \App\Role::select('display_name')->where("_id",$firebase_id['role_id'])->first();
		
					 if(isset($rolename['display_name']))
					 { 
						$newrolename =  $rolename['display_name'];
					 }	
				if($requestjson['type'] == 'Event' && isset($firebase_id['firebase_id']))
				{				
					  $this->sendPushNotification(
					$this->request,
					self::NOTIFICATION_TYPE_EVENT_CHANGES,
					$firebase_id['firebase_id'],
					[
						'phone' => "9881499768",
						'title' => $requestjson['title'],
						'rolename' => $newrolename,
						'update_status' => self::NOTIFICATION_TYPE_EVENT_CHANGES,
						'model' => "Planner",
						'approval_log_id' => "Testing"
					],
					$firebase_id['org_id']
					);   
				} 
				if($requestjson['type'] == 'Task')
				{		
					$rolename = \App\Role::select('display_name')->where("_id",$user['role_id'])->first();
		
					 if(isset($rolename['display_name']))
					 { 
						$newrolename =  $rolename['display_name'];
					 }	
					  
					/* $this->sendPushNotification(
					$this->request,
					self::NOTIFICATION_TYPE_TASK_CHANGES,
					$firebase_id['firebase_id'],
					[
						'phone' => "9881499768",
						'title' => $requestjson['title'],
						'model' => "Planner",
						'rolename' => $newrolename,
						'update_status' => self::NOTIFICATION_TYPE_TASK_CHANGES,
						'approval_log_id' => "Testing"
					],
					$firebase_id['org_id']
					); */ 
				} 
				  }
			  }
						
			}catch(Exception $e)
			{
			$response_data = array('status' =>'200','message'=>'error','data' => $e);
			return response()->json($response_data,200); 
			}  
		
		if($success)
		{
			$response_data = array('status' =>'200','message'=>'success');
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'404','message'=>'Event Not Created.');
			return response()->json($response_data,200); 
		}
		
		
	}
	
	//Fetch all the events by Month
	public function getEventByMonth(Request $request)
	{
		 $user = $this->request->user();
		 $header = getallheaders();
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
			$message['function'] = "getEventByMonth";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
		$requestjson = json_decode(file_get_contents('php://input'), true);
		$requestjson['function'] = "getEventByMonth";
		$this->logData($this->logInfoPath ,$requestjson,'DB');
		$type = $requestjson['type'];
		$month = $requestjson['month'];
		$year = $requestjson['year'];
		$userId = $requestjson['userId'];
		$start = strtotime($year."-".$month."-01");
		$end = strtotime($year."-".$month."-31");
		
		$start_date_str = Carbon::createFromTimestamp($start)->toDateTimeString();
		$start_date_time = Carbon::parse($start_date_str)->startOfDay(); 
		$end_date_str = Carbon::createFromTimestamp($end)->toDateTimeString();
		$end_date_time = Carbon::parse($end_date_str)->endOfDay(); 	 
		  
		$maindata = PlannerTransactions::
		whereBetween('schedule.starttiming',array(new \MongoDB\BSON\UTCDateTime(new DateTime($year."-".$month."-01")),new \MongoDB\BSON\UTCDateTime(new DateTime($year."-".$month."-31"))))
		->where('type',$type)
		->whereOr('ownerid',$userId)
		->whereOr('participants.id',$userId)
		->whereOr('schedule.starttiming',new \MongoDB\BSON\UTCDateTime(new DateTime($year."-".$month."-01")))
		->whereOr('schedule.starttiming',new \MongoDB\BSON\UTCDateTime(new DateTime($year."-".$month."-31")))
		->orderby('schedule.starttiming','asc')
		->get();
		  
		  $data = array();  
		if($maindata){
			foreach($maindata as $row)
			{  
				if($row['ownerid'] == $userId ) 
				{
				 array_push($data,$row);
				} 
				else
				{
					if($row['participants'])
					{
						foreach($row['participants'] as $participants )
						{
							if($participants['id'] == $userId){
								 array_push($data,$row);
							}
						}
					}
					 
				}  
			}		
		}
		 
		$mainarray = array();
		
		  foreach($data as $row)
		{
			$startdatetime = $row['schedule']['startdatetime'] / 1000;
			$enddatetime = $row['schedule']['enddatetime'] / 1000; 
			 for ($i=$startdatetime; $i<=$enddatetime; $i+=86400) {  
				array_push($mainarray, date("Y-m-d", $i)); 
				
			}   
			array_push($mainarray, date("Y-m-d", $enddatetime));  
		}  
		
			
		
		if($mainarray)
		{
			$response_data = array('status' =>'200','message'=>'success','data' =>array_values(array_unique($mainarray)));
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'300','message'=>'No Data Found..');
			return response()->json($response_data,200); 
		}  
		
	}
	
	
	
	//Fetch all the events by Day
	public function getEventByDay(Request $request)
	{
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "getEventByDay";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
		$requestjson = json_decode(file_get_contents('php://input'), true);
		$requestjson['function'] = "getEventByDay";
		$this->logData($this->logInfoPath ,$requestjson,'DB');
		$type = $requestjson['type'];
		$month = $requestjson['month'];
		$day = $requestjson['day'];
		$year = $requestjson['year'];
		 
		 $userId = $user['_id'];
		//exit;
		$timestamp = strtotime($year."-".$month."-".$day);
		 
		$start_date_str = Carbon::createFromTimestamp($timestamp)->toDateTimeString();
		$start_date_time = Carbon::parse($start_date_str)->startOfDay(); 
		//$start_date_time1 = $start_date_time->subDays(1); 
		 
		$end_date_str = Carbon::createFromTimestamp($timestamp)->toDateTimeString();
		$end_date_time = Carbon::parse($end_date_str)->endOfDay(); 			 
		//$end_date_time1 = $end_date_time->addDays(1); 			 
		// echo $start_date_time."-".$end_date_time;  
		$maindata = PlannerTransactions:://select('type','title','thumbnail_image','address','description','schedule','ownerid','required_forms','event_status','registration_required','is_mark_attendance_required','participants_count','attended_completed','registration_schedule')
										where('schedule.starttiming','<=',$end_date_time)
										->where('schedule.endtiming','>=',$start_date_time)
										->where('type',$type)
										->orderby('schedule.starttiming','asc')
										->get();

	 
		$data = array(); 
		
		if($maindata){
		foreach($maindata as $row)
		{ 
			if($row['ownerid'] == $userId ) 
			{
			 array_push($data,$row);
			  unset($data["participants"]); 
			} 
			else
			{
				if($row['participants'])
				{
					foreach($row['participants'] as $participants )
					{
						if($participants['id'] == $userId){
							unset($row["participants"]); 
							 array_push($data,$row);
						}
					}
				}
				 
			}  
		}		
		}
		DB::setDefaultConnection('mongodb');
		
		// $maindata['name'] = $org_id->name; 
		if(count($data)>0)
		{
			$response_data = array('status' =>'200','message'=>'success','data' => $data);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'300','message'=>'No Data Found..');
			return response()->json($response_data,200); 
		}
		
	}
	
	 
	//Fetch all the categories of tasks
	public function addmembertask(Request $request)
	{  
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "addmembertask";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		$userLocation = $user['location'];
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
			 
			$approverRoleConfig = \App\RoleConfig::where('approver_role', $role_id)->get();
			
			if(count($approverRoleConfig) >0) 
			{
				
			$levelIds = [];
			$jurisdictionIds = [];
			$roleIds = [];
			
			foreach($approverRoleConfig as $approverData)
			{
				array_push($levelIds,$approverData['level']);
				array_push($jurisdictionIds,$approverData['jurisdiction_type_id']);
				array_push($roleIds,$approverData['role_id']);
				
				
			}
			
			if(!empty($approverRoleConfig))
			{  
				$levelDetail = \App\Jurisdiction::whereIn('_id',$levelIds)->get(); 
				$levelname = $levelDetail[0]->levelName;
				$jurisdictions = \App\JurisdictionType::whereIn('_id',$jurisdictionIds)->pluck('jurisdictions')[0];
				
				DB::setDefaultConnection('mongodb'); 
				$userList =\App\User::whereIn('role_id', $roleIds);
				 
					 foreach ($jurisdictions as $singleLevel) { 
						if (isset($userLocation[strtolower($singleLevel)])) {
							$userList->whereIn('location.' . strtolower($singleLevel), $userLocation[strtolower($singleLevel)]); 
							if ($singleLevel == $levelname) { 
								break;
							} 
						} 
					}  
			}
		 
			$users = $userList->get(); 
		  
		if($users !=null)
		{	 
			$username = $users->pluck('_id'); 
			 
			DB::setDefaultConnection('mongodb');
			$maindata=User::select('name','role_id')->whereIn('_id',$username)->get();
			
			$main =array();
			foreach($maindata as $row)
			{
				$role_name = Role::select('display_name')->where('_id',$row['role_id'])->get();
				
				 if(count($role_name)==0){
					
				$temp_arr = array(
				'id'=>$row['_id'],
				'name'=>$row['name'],
				'role_name'=>''
				);
				}
				else{ 
				$temp_arr = array(
				'id'=>$row['_id'],
				'name'=>$row['name'],
				'role_name'=>$role_name[0]['display_name']
				);
				array_push($main,$temp_arr);  
				}
			}
		
		}
		 
		if($main)
		{
			$response_data = array('status' =>'200','message'=>'success','data' => $main);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'404','message'=>'No members found');
			return response()->json($response_data,200); 
		}
	}{
			$response_data = array('status' =>'404','message'=>'No members found');
			return response()->json($response_data,200); 
		}
		
	}
	
	public function deletemember(Request $request)
	{ 
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "deletemember";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
		$requestjson = json_decode(file_get_contents('php://input'), true);
		$requestjson['function'] = "deletemember";
		$this->logData($this->logInfoPath ,$requestjson,'DB');
		$eventDetails = PlannerTransactions::find($requestjson['eventId']);
		 
		if($eventDetails)
		{
			$memberarray = array();
			foreach($eventDetails['participants'] as $memberlist)
			{
				if($memberlist['id'] == $requestjson['memberId'])
				{
					//nothing to do..
				}
				else{
					array_push($memberarray,$memberlist);
				}
			}
			
			$eventDetails['participants'] = $memberarray;
			$eventDetails['participants_count'] = $eventDetails['participants_count'] - 1;
			$eventDetails->save();
			
			DB::setDefaultConnection('mongodb');
			$rolename = \App\Role::find($role_id);
		 
				 if(isset($rolename['display_name']))
				 {
					$newrolename =  $rolename['display_name'];
				 }
				$firebase_id = User::where('_id',$requestjson['memberId'])->first(); 
				 
				$this->sendPushNotification(
				$this->request,
				self::NOTIFICATION_TYPE_MEMBER_DELETED,
				$firebase_id['firebase_id'],
				[
					'phone' => "9881499768",
					'type' => $eventDetails['type'],
					'title' => $eventDetails['title'],
					'rolename' => $newrolename,					 
					'update_status' => self::NOTIFICATION_TYPE_MEMBER_DELETED,
					'approval_log_id' => $user['name']
				],
				$firebase_id['org_id']
				); 
				
			if($eventDetails)
			{
				$response_data = array('status' =>'200','message'=>'successfully Deleted');
				return response()->json($response_data,200); 
			}
			else
			{
				$response_data = array('status' =>'404','message'=>'No members found');
				return response()->json($response_data,200); 
			}	
		}
		else
			{
				$response_data = array('status' =>'404','message'=>'No Events found');
				return response()->json($response_data,200); 
			}	
		
	}
	

	public function createLeave(Request $request)
	{
		$user = $this->request->user(); 
		$header = getallheaders();
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
			$message['function'] = "createLeave";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		$timestamp = Date('Y-m-d H:i:s');
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
		$requestjson = json_decode(file_get_contents('php://input'), true); 
		$requestjson['function'] = "createLeave";
		$this->logData($this->logInfoPath ,$requestjson,'DB');
		 
		$start_date = Carbon::createFromTimestamp($requestjson['startdate']/1000 );
		$startdate = new Carbon($start_date);
        $startdate->timezone = 'Asia/Kolkata';
        $start_date_str = new \MongoDB\BSON\UTCDateTime($startdate->startOfDay()->addHours(5)->addMinutes(30));
		
	    $end_date = Carbon::createFromTimestamp($requestjson['enddate']/1000 );
		$enddate = new Carbon($end_date);
        $enddate->timezone = 'Asia/Kolkata';
		$end_date_str =  new \MongoDB\BSON\UTCDateTime($enddate->endOfDay());
		
		$start_date_str1 = Carbon::createFromTimestamp($requestjson['startdate'] / 1000)->toDateTimeString();
		$end_date_str1 = Carbon::createFromTimestamp($requestjson['enddate'] / 1000)->toDateTimeString();
		$start_date_time1 = Carbon::parse($start_date_str1)->startOfDay();  
		$end_date_time1 = Carbon::parse($end_date_str1)->endOfDay();
	   
		//code to check user leave balance start here
		$days = $start_date_time1->diffInDays($end_date_time1)+1;
					
		$userLeaveData = PlannerUserLeaveBalance::select('leave_balance')->where('user_id',$user->_id)->get();
	    
	   $userLeaveTypeData = $userLeaveData[0]['leave_balance'];
	  
	   foreach ($userLeaveTypeData as $leaveData) {
			if($leaveData['type'] == $requestjson['leave_type'])
			{
				 $userLeaveBalance = $leaveData['balance'];

				if(($userLeaveBalance-$days < 0))
				{
					$leaveBalancecResponse = array('status' =>'200','message'=>'You do not have sufficient leave balance.');
					return response()->json($leaveBalancecResponse,200); 

				}
			}
	   }

	   //code to check user leave balance ends here



		$attendanceInfo = PlannerAttendanceTransaction:://select('created_at')
						  whereBetween('created_at',array($start_date_str,$end_date_str))
						  ->where('user_id',$user->_id)->get();
	   

		if(count($attendanceInfo) > 0)
		  {
			  $response_data = array('status' =>'300','message'=>"You have marked attendance in selected date range");
			  return response()->json($response_data,200); 
		  }                              
				  


		$holidayInfo = PlannerHolidayMaster::select('holiday_date','type')
						   ->where('Date',">=",$start_date_str)
						   ->where('Date',"<=",$end_date_str)
						   ->where('type', 'holiday')
						   ->get();

		
		if(count($holidayInfo) == 0){
		 
		$PlannerLeaveApp = PlannerLeaveApplications::
						  select('user_id','startdate','enddate')
						  ->where('user_id',$user->_id)
						  ->where('status.status','!=','rejected')
						->WhereBetween('startdates',array($start_date_str,$end_date_str))
						//->whereOr('startdate',">=",$start_date_time1)
						//->whereOr('enddate',"<=",$end_date_time1)
						->whereOr('startdates',$end_date_str)
						->whereOr('enddates',$start_date_str)
						->get();
		//die(); 				 

		 //echo json_encode($PlannerLeaveApp);die();
		if(count($PlannerLeaveApp) > 0){
			$response_data1 = array('status' =>'200','message'=>'You have already applied leave on this date.Please Change Your Date.');
			return response()->json($response_data1,200); 
		}
		 else{
		$maindata = new PlannerLeaveApplications;
		$maindata['leave_type'] = $requestjson['leave_type'];
		$maindata['full_half_day'] = $requestjson['full_half_day'];
		$maindata['startdate'] = (int)$requestjson['startdate'] ;
		$maindata['enddate'] = (int)$requestjson['enddate'];
		$maindata['startdates'] = $start_date_str;
		$maindata['enddates'] = $end_date_str;
		$maindata['user_id'] = $user->_id;
		$maindata['reason'] = $requestjson['reason'];
		$maindata['paid_leave'] = true;
		$maindata['status.status'] = "pending";
		$maindata['status.action_by'] = "";
		$maindata['status.action_on'] = "";
		$maindata['status.rejection_reason'] = "";
		
		$maindata['default.org_id'] = $org_id;
		$maindata['default.updated_by'] = "";
		$maindata['default.created_by'] = $user['_id'];
		$maindata['default.created_on'] = $timestamp;    
		$maindata['default.updated_on'] = "";
		$maindata['default.project_id'] = $project_id;
		 // echo json_encode($maindata);die();
		try{
			 
			$maindata->save();
			$last_inserted_id = $maindata->id;
			
			 if($last_inserted_id)
			{
				
				$approverUsers = array();
				$approverList = $this->getApprovers($this->request, $role_id, $user['location'], $org_id);
				 
				// echo json_encode($approverList);die();
				$approverIds =array();
				foreach($approverList as $approver) { 
				$approverIds = $approver['id'];  
				
				array_push($approverUsers,$approverIds);
				} 
				 
				$rolename = \App\Role::select('display_name')->where("_id",$role_id)->first();
		
					 if(isset($rolename['display_name']))
					 { 
						$newrolename =  $rolename['display_name'];
					 } 
				 
				$ApprovalLogs = new ApprovalLog;
				 
				$ApprovalLogs['entity_id'] = $last_inserted_id;
				$ApprovalLogs['entity_type'] = "leave";
				$ApprovalLogs['approver_ids'] = $approverUsers;
				$ApprovalLogs['status'] = "pending";
				$ApprovalLogs['userName'] = $user->_id;
				$ApprovalLogs['reason'] = "";
				$ApprovalLogs['startdate'] = (int)($requestjson['startdate'] / 1000);
				$ApprovalLogs['enddate'] = (int)($requestjson['enddate'] / 1000);
				$ApprovalLogs['is_deleted'] = false;
				
				$ApprovalLogs['default.org_id'] = $org_id;
				$ApprovalLogs['default.updated_by'] = "";
				$ApprovalLogs['default.created_by'] = $user['_id'];
				$ApprovalLogs['default.created_on'] = $timestamp;    
				$ApprovalLogs['default.updated_on'] = "";
				$ApprovalLogs['default.project_id'] = $project_id;	
				$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				} 
				try{

					$ApprovalLogs->save(); 

					  
				}catch(exception $e)
					{
						$response_data = array('status' =>'200','message'=>'success','data' => $e);
						return response()->json($response_data,200); 
					}
				
				$ApprovalsPending = new ApprovalsPending;
				
				$ApprovalsPending['entity_id'] = $last_inserted_id;
				$ApprovalsPending['entity_type'] = "leave";
				$ApprovalsPending['approver_ids'] = $approverUsers;
				$ApprovalsPending['status'] = "pending";
				$ApprovalsPending['userName'] = $user->_id;
				$ApprovalsPending['reason'] = "";
				
				$ApprovalsPending['startdate'] =  new \MongoDB\BSON\UTCDateTime($requestjson['startdate'] );

				$ApprovalsPending['enddate'] = new \MongoDB\BSON\UTCDateTime($requestjson['enddate'] );
				$ApprovalsPending['is_deleted'] = false;
				
				$ApprovalsPending['default.org_id'] = $org_id;
				$ApprovalsPending['default.updated_by'] = "";
				$ApprovalsPending['default.created_by'] = $user['_id'];
				$ApprovalsPending['default.created_on'] = $timestamp;    
				$ApprovalsPending['default.updated_on'] = "";
				$ApprovalsPending['default.project_id'] = $project_id;	
				$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				} 
				try{
					$ApprovalsPending->save(); 
			 
					foreach($approverUsers as $row)
					{  
						DB::setDefaultConnection('mongodb');
						$firebase_id = User::where('_id',$row)->first(); 
						$this->sendPushNotification(
						$this->request,
						self::NOTIFICATION_TYPE_LEAVE_APPROVAL,
						$firebase_id['firebase_id'],
						[
							'phone' => "9881499768",
							'update_status' => self::STATUS_PENDING,
							'approval_log_id' => "Testing",
							'rolename'=>$newrolename
						],
						$firebase_id['org_id']
						);
					} 
				}
				catch(exception $e)
					{
						$response_data = array('status' =>'200','message'=>'success','data' => $e);
						return response()->json($response_data,200); 
					}
				
			} 
			else{
				$response_data = array('status' =>'200','message'=>'success','data' => "Leave is not applied");
				return response()->json($response_data,200); 
			}
			}
		
			catch(exception $e)
			{
				$response_data = array('status' =>'200','message'=>'success','data' => $e);
				return response()->json($response_data,200); 
			}
		
		if($ApprovalsPending)
		{
			$response_data = array('status' =>'200','message'=>'Your leave application submitted successfully');
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'404','message'=>'error');
			return response()->json($response_data,200); 
		}
	}
	}else{
			$response_data = array('status' =>'404','message'=>'Leave Cannot be Applied, Date Range consist of Holiday.');
			return response()->json($response_data,200); 
	}
	
		
		
	}
	
	//edit leave which is already applied
	public function editLeave(Request $request)
	{
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "editLeave";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$timestamp = Date('Y-m-d H:i:s');
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
		$requestjson = json_decode(file_get_contents('php://input'), true);
		$requestjson['function'] = "editLeave";
		$this->logData($this->logInfoPath ,$requestjson,'DB');
		
		$maindata = PlannerLeaveApplications::where('_id',$requestjson['_id'])->first();
		$maindata['leave_type'] = $requestjson['leave_type'];
		$maindata['full_half_day'] = $requestjson['full_half_day'];
		$maindata['startdate'] = $requestjson['startdate'] ;
		$maindata['enddate'] = $requestjson['enddate'];
		$maindata['startdates'] = new \MongoDB\BSON\UTCDateTime($requestjson['startdate'] * 1000);
		$maindata['enddates'] = new \MongoDB\BSON\UTCDateTime($requestjson['enddate'] * 1000);
		$maindata['user_id'] = $requestjson['user_id'];
		$maindata['reason'] = $requestjson['reason']; 
		  
		$maindata['default.updated_by'] = $user['_id'];
		$maindata['default.updated_on'] = $timestamp;    
		 try{
				$maindata->save(); 
				$response_data = array('status' =>'200','message'=>'success');
				return response()->json($response_data,200); 			   
			}
			catch(exception $e)
			{
				$response_data = array('status' =>'200','message'=>'success','data' => $e);
				return response()->json($response_data,200); 
			}
	}
	
	
	//delete leave which is already applied
	public function deleteLeave(Request $request,$leaveId)
	{
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "deleteLeave";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$data['function'] = "deleteLeave";
		$data['leaveId'] = $leaveId;
		$this->logData($this->logInfoPath ,$data,'DB');
		
		$timestamp = Date('Y-m-d H:i:s');
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
		try{

			$leaveInfo = ApprovalsPending::where('entity_id',$leaveId)->first();
		 
			if($leaveInfo['entity_type'] == 'compoff')
			{
				$compoff = PlannerClaimCompoffRequests::find($leaveId);  
				if($compoff){
				$compoff->delete();
				}
			}else{
					$leave=PlannerLeaveApplications::find($leaveId);    
					if($leave){
					$leave->delete();
				}
			}
					// $ApprovalsPending=ApprovalsPending::where('entity_id',$leaveId)->first();
					if($leaveInfo){
					$leaveInfo->delete();
					}			
					$response_data = array('status' =>'200','message'=>'Deleted successfully');
					return response()->json($response_data,200); 	
							
			}
			catch(exception $e)
			{
				$response_data = array('status' =>'200','message'=>'success','data' => $e);
				return response()->json($response_data,200); 
			}		
	}
	
	 
	
	//delete task
	public function deleteTask(Request $request,$taskId)
	{
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "deleteTask";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$data['function'] = "deleteTask";
		$data['taskId'] = $taskId;
		$this->logData($this->logInfoPath ,$data,'DB');
		$timestamp = Date('Y-m-d H:i:s');
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
		try{
				$task=PlannerTransactions::find($taskId);
			if($task){
				$members = $task['participants'];
				$type = $task['type'];
				$title = $task['title'];
				 
				$task->delete($task->id); 
				DB::setDefaultConnection('mongodb'); 
				if($members){
				foreach($members as $mem)
				{
					$firebase_id = User::where('_id',$mem['id'])->first(); 
					$rolename = \App\Role::select('display_name')->where("_id",$firebase_id['role_id'])->first();
		
					 if(isset($rolename['display_name']))
					 { 
						$newrolename =  $rolename['display_name'];
					 }
					/* $this->sendPushNotification(
					$this->request,
					self::NOTIFICATION_TYPE_EVENT_DELETED,
					$firebase_id['firebase_id'],
					[
						'phone' => "9881499768",
						'title' => $title,
						'type' => $type,
						'rolename' => $newrolename,
						'update_status' => self::NOTIFICATION_TYPE_EVENT_DELETED,
						'model' => $type,
						'approval_log_id' => "Testing"
					],
					$firebase_id['org_id']
					);  */
						
				}
				
				$response_data = array('status' =>'200','message'=>'success');
				return response()->json($response_data,200); 		
				}				
			}else{
				$response_data = array('status' =>'200','message'=>'No Event Found');
				return response()->json($response_data,200); 
			}	
		}
			catch(exception $e)
			{
				$response_data = array('status' =>'200','message'=>'success','data' => $e);
				return response()->json($response_data,200); 
			}
			
	
	}
	
	//get the events members
	public function getEventMembers(Request $request,$eventId)
	{
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "getEventMembers";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$data['function'] = "getEventMembers";
		$data['eventId'] = $eventId;
		$this->logData($this->logInfoPath ,$data,'DB');
		$timestamp = Date('Y-m-d H:i:s');
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			
		try{
				$memberlist = PlannerTransactions::select('participants')->where('_id',$eventId)->get();
				
				if(count($memberlist) > 0){ 
					$data = $memberlist[0]['participants'];
					$response_data = array('status' =>'200','message'=>'success','data'=>$data);
					return response()->json($response_data,200); 	
				}
				else{
					$response_data = array('status' =>'200','message'=>'success');
					return response()->json($response_data,200); 	
				}
			}
			catch(exception $e)
			{
				$response_data = array('status' =>'200','message'=>'success','data' => $e);
				return response()->json($response_data,200); 
			}	
			
	} 
	
	public function taskMarkComplete(Request $request,$taskId)
	{
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "taskMarkComplete";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$timestamp = Date('Y-m-d H:i:s'); 
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}	
			
		$requestjson = json_decode(file_get_contents('php://input'), true);	
		$requestjson['function'] = "taskMarkComplete";
		$this->logData($this->logInfoPath ,$requestjson,'DB');
		$userId = $user->_id;
		
		$maindata = PlannerTransactions::where('_id',$taskId)->first();
		if($maindata)
		{ 
			if($maindata['participants'])
			{
				$count = 0; 
				
				foreach($maindata['participants'] as $ids)
				{
					
					if($ids['id'] == $userId)
					{
						if($maindata['participants.'.$count.'.attended_completed'] == false)
						{
						$maindata['attended_completed'] = $maindata['attended_completed'] + 1 ; 
						}
						$maindata['participants.'.$count.'.attended_completed'] = true;
						$maindata['default.org_id'] = $org_id;
						$maindata['default.updated_by'] = $user->_id;
						$maindata['default.updated_on'] = $timestamp;
						$maindata['default.project_id'] = $project_id; 
						
						try{
						$maindata->save();	
						$response_data = array('status' =>'200','message'=>'Task completed successfully');
						return response()->json($response_data,200);
						}catch(Exception $e)
						{
							$response_data = array('status' =>'300','message'=>'error','data' => $e);
							return response()->json($response_data,200);
						}
					} 
					 
					if($userId == $maindata['ownerid']){
						 
						$loop = 0;						
						foreach($maindata['participants'] as $participant)
						{
							$maindata['participants.'.$loop.'.attended_completed'] = true; 
							$loop ++;
						}
							$maindata['default.org_id'] = $org_id;
							$maindata['default.updated_by'] = $user->_id;
							$maindata['default.updated_on'] = $timestamp;
							$maindata['default.project_id'] = $project_id; 
							$maindata['mark_complete'] = true; 
							$maindata['event_status']='Completed';
							$maindata['attended_completed'] = count($maindata['participants']); 
							try{
							$maindata->save();	
							$response_data = array('status' =>'200','message'=>'Task completed successfully');
							return response()->json($response_data,200);
							}catch(Exception $e)
							{
								$response_data = array('status' =>'300','message'=>'error','data' => $e);
								return response()->json($response_data,200);
							}
					}
					 /* else{
						 
						 if(count($maindata['participants']) - 1 == $count){ 
						 echo $count ."<br>";
						  echo count($maindata['participants']) - 1;die();
						$response_data = array('status' =>'300','message'=>'This User is not in the List','data' => 'Invalid User');
						return response()->json($response_data,200);
						 }
					} */ 
					$count++;
				}
			}
			else{
				$maindata['mark_complete'] = true; 
				$maindata['event_status']='Completed';
				$maindata['default.org_id'] = $org_id;
				$maindata['default.updated_by'] = $user->_id;
				$maindata['default.updated_on'] = $timestamp;
				$maindata['default.project_id'] = $project_id; 
				try{
						$maindata->save();	
						$response_data = array('status' =>'200','message'=>'Task completed successfully');
						return response()->json($response_data,200);
						}catch(Exception $e)
						{
							$response_data = array('status' =>'300','message'=>'error','data' => $e);
							return response()->json($response_data,200);
						}
			}	
		} else{
					$response_data = array('status' =>'300','message'=>'error','data' =>'No Event Found..');
					return response()->json($response_data,200);
				}
	}
	
	public function applyCompoff(Request $request)
	{
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "taskMarkComplete";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}	
		$timestamp = Date('Y-m-d H:i:s');	
		$requestjson = json_decode(file_get_contents('php://input'), true);	
		$requestjson['function'] = "taskMarkComplete";
		$this->logData($this->logInfoPath ,$requestjson,'DB');		
		$start_date = Carbon::createFromTimestamp(($requestjson['startdate'] / 1000));
		
		$startdate = new Carbon($start_date);
        $startdate->timezone = 'Asia/Kolkata';
        $start_date_time = new \MongoDB\BSON\UTCDateTime($startdate->startOfDay()->addHours(5)->addMinutes(30));


		$end_date = Carbon::createFromTimestamp(($requestjson['enddate'] / 1000));

		$enddate = new Carbon($end_date);
        $enddate->timezone = 'Asia/Kolkata';
        $end_date_time = new \MongoDB\BSON\UTCDateTime($enddate->endOfDay()->addHours(5)->addMinutes(30));
 
		$PlannerClaimCompoffRequests = PlannerClaimCompoffRequests::where('user_id',$user->_id)
															  ->where('startdates','>=',$start_date_time)
															  ->where('enddates','<=',$end_date_time)
															  ->get();
	   
		if(count($PlannerClaimCompoffRequests) == 0 )
		{
			$PlannerClaimCompoffRequests = new PlannerClaimCompoffRequests;
		}
		else
		{
			$response_data = array('status' =>'300','message'=>'Conflict in Dates Please Change your Date Range');
			return response()->json($response_data,200);
		}



		$attendanceInfo = PlannerAttendanceTransaction:://select('created_at')
						  whereBetween('created_at',array($start_date_time,$end_date_time))
						  ->where('user_id',$user->_id)->get();
	  
		if(count($attendanceInfo) == 0)
		  {
			  $response_data = array('status' =>'300','message'=>"You haven't marked any attendance in selected date range");
			  return response()->json($response_data,200); 
		  } 


		
	
		$PlannerClaimCompoffRequests['entity_type'] = "compoff";
		$PlannerClaimCompoffRequests['full_half_day'] = $requestjson['full_half_day'];
		$PlannerClaimCompoffRequests['startdate'] = $requestjson['startdate'] ;
		$PlannerClaimCompoffRequests['enddate'] = $requestjson['enddate'];
		$PlannerClaimCompoffRequests['startdates'] = $start_date_time;
		$PlannerClaimCompoffRequests['enddates'] =$end_date_time;
		$PlannerClaimCompoffRequests['user_id'] = $user->_id;
		$PlannerClaimCompoffRequests['reason'] = $requestjson['reason'];

		$PlannerClaimCompoffRequests['status.status'] = "pending";
		$PlannerClaimCompoffRequests['status.action_by'] = "";
		$PlannerClaimCompoffRequests['status.action_on'] = "";
		$PlannerClaimCompoffRequests['status.rejection_reason'] = ""; 
		  
		$PlannerClaimCompoffRequests['default.org_id'] = $org_id;
		$PlannerClaimCompoffRequests['default.updated_by'] = "";
		$PlannerClaimCompoffRequests['default.created_by'] = $user->_id;
		$PlannerClaimCompoffRequests['default.created_on'] = $timestamp;    
		$PlannerClaimCompoffRequests['default.updated_on'] = "";
		$PlannerClaimCompoffRequests['default.project_id'] = $project_id;

		try{ 
			$PlannerClaimCompoffRequests->save();
			 
			$last_inserted_id =  $PlannerClaimCompoffRequests->id;			
		   }
		catch(Exception $e)
		{
			$response_data = array('status' =>'300','message'=>'error','data' => $e);
			return response()->json($response_data,200);
		}
		$start_date_str = Carbon::createFromTimestamp(($requestjson['startdate'] / 1000))->toDateTimeString();
		$end_date_str = Carbon::createFromTimestamp(($requestjson['enddate'] / 1000))->toDateTimeString();
		$start_date_time = Carbon::parse($start_date_str)->startOfDay();  
		$end_date_time = Carbon::parse($end_date_str)->endOfDay();  
		 
		$approverUsers = array();
		$approverList = $this->getApprovers($this->request, $role_id, $user['location'], $org_id);
		  
		$approverIds =array();
		foreach($approverList as $approver) { 
		$approverIds = $approver['id'];  
		
		array_push($approverUsers,$approverIds);
		} 
		$rolename = \App\Role::select('display_name')->where("_id",$role_id)->first();
		 if(isset($rolename['display_name']))
		 {
			$newrolename =  $rolename['display_name'];
		 }
		$database = $this->connectTenantDatabase($request,$org_id);
		
		$ApprovalLogs = new ApprovalLog;
		$ApprovalLogs['entity_type'] = "compoff";
		$ApprovalLogs['entity_id'] = $last_inserted_id;
		$ApprovalLogs['approver_ids'] = $approverUsers;
		$ApprovalLogs['full_half_day'] = $requestjson['full_half_day'];
		$ApprovalLogs['startdate'] = $requestjson['startdate'] ;
		$ApprovalLogs['enddate'] = $requestjson['enddate'];
		$ApprovalLogs['startdates'] = new \MongoDB\BSON\UTCDateTime($requestjson['startdate']);
		$ApprovalLogs['enddates'] = new \MongoDB\BSON\UTCDateTime($requestjson['enddate']);
		$ApprovalLogs['userName'] = $user->_id;
		$ApprovalLogs['reason'] = $requestjson['reason']; 
		$ApprovalLogs['status'] = "pending";  
		  
		$ApprovalLogs['default.org_id'] = $org_id;
		$ApprovalLogs['default.updated_by'] = "";
		$ApprovalLogs['default.created_by'] = $user->_id;;
		$ApprovalLogs['default.created_on'] = $timestamp;    
		$ApprovalLogs['default.updated_on'] = "";
		$ApprovalLogs['default.project_id'] = $project_id;	 
		
		try{
			$ApprovalLogs->save(); 
			  
			}
		catch(Exception $e)
		{
			$response_data = array('status' =>'300','message'=>'error','data' => $e);
			return response()->json($response_data,200);
		}
		
		$ApprovalsPending = ApprovalsPending::where('leave_type',"earn compoff")->where('user_id',$requestjson['user_id'])->first();
		
		if(!$ApprovalsPending)
		$ApprovalsPending = new ApprovalsPending;
	
		$ApprovalsPending['entity_type'] = "compoff";
		$ApprovalsPending['entity_id'] = $last_inserted_id;
		$ApprovalsPending['approver_ids'] = $approverUsers;
		$ApprovalsPending['full_half_day'] = $requestjson['full_half_day'];
		$ApprovalsPending['startdate'] = $requestjson['startdate'] ;
		$ApprovalsPending['enddate'] = $requestjson['enddate'];
		$ApprovalsPending['startdates'] = new \MongoDB\BSON\UTCDateTime($requestjson['startdate']);
		$ApprovalsPending['enddates'] = new \MongoDB\BSON\UTCDateTime($requestjson['enddate']);
		$ApprovalsPending['userName'] = $user->_id;;
		$ApprovalsPending['reason'] = $requestjson['reason']; 
		$ApprovalsPending['status'] = "pending"; 
		
		  
		$ApprovalsPending['default.org_id'] = $org_id;
		$ApprovalsPending['default.updated_by'] = "";
		$ApprovalsPending['default.created_by'] = $user->_id;;
		$ApprovalsPending['default.created_on'] = $timestamp;    
		$ApprovalsPending['default.updated_on'] = "";
		$ApprovalsPending['default.project_id'] = $project_id;	
		try{
			 $ApprovalsPending->save();
			 foreach($approverUsers as $row)
					{  
						DB::setDefaultConnection('mongodb'); 
						$firebase_id = User::where('_id',$row)->first(); 
						$this->sendPushNotification(
						$this->request,
						self::NOTIFICATION_TYPE_COMOFF_APPROVAL,
						$firebase_id['firebase_id'],
						[
							'phone' => "9881499768",
							'rolename'=>$newrolename,
							'update_status' => self::STATUS_PENDING,
							'approval_log_id' => "Testing"
						],
						$firebase_id['org_id']
						);
					}
			$response_data = array('status' =>'200','message'=>'Your request for CompOff is submitted Successfully','data'=>'CompOff Applied Successfully');
			return response()->json($response_data,200);			
		   }
		catch(Exception $e)
		{
			$response_data = array('status' =>'300','message'=>'error','data' => $e);
			return response()->json($response_data,200);
		}
		
	}
	 
	 public function getSurveyDetail($survey_id)
	{
		 $user = $this->request->user();
		 $header = getallheaders();
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
			$message['function'] = "getSurveyDetail";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		$data['function'] = "getSurveyDetail";
		$data['survey_id'] = $survey_id;
		$this->logData($this->logInfoPath ,$data,'DB');
		$database = $this->connectTenantDatabase($this->request);
		if ($database === null) {
			return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
		}

		// Obtaining '_id','name','json', active','editable','multiple_entry','category_id','microservice_id','project_id','entity_id','assigned_roles','form_keys' of a Survey
		// alongwith corresponding details of 'microservice','project','category','entity'
		$entity_id = Survey::where('_id',$survey_id)->select('entity_id')->get();
		$data = Survey::with('microservice')->with('project')
		->with('category')->with('entity')        
		->select('category_id','microservice_id','project_id','entity_id','assigned_roles','_id','name','json','active','approve_required','editable','multiple_entry','form_keys')
		->find($survey_id);

		// unset() removes the element from the 'row' object
		unset($data->category_id);
		unset($data->microservice_id);
		unset($data->project_id);
		unset($data->entity_id);

		if (isset($data['microservice'])) {
			$data['microservice']->route = $data['microservice']->route . '/' . $survey_id;
		}
		
		// json_decode function takes a JSON string and converts it into a PHP variable
		$data->json = json_decode($data->json,true);
		return response()->json(['status'=>'success','data' => $data,'message'=>'']);
	}
	
	public function showResponses($survey_id)
	{
		$database = $this->connectTenantDatabase($this->request);
		if ($database === null) {
			return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
		}

		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "showResponses";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$data['function'] = "showResponses";
		$data['survey_id'] = $survey_id;
		$this->logData($this->logInfoPath ,$survey_id,'DB');
		$survey = Survey::find($survey_id);
		
		$limit = (int)$this->request->input('limit') ?:50;
		$offset = $this->request->input('offset') ?:0;
		$order = $this->request->input('order') ?:'desc';
		$field = $this->request->input('field') ?:'createdDateTime';
		$page = $this->request->input('page') ?:1;
		$endDate = $this->request->input('start_date') ?:Carbon::now('Asia/Calcutta')->getTimestamp();
		$startDate = $this->request->input('end_date') ?:Carbon::now('Asia/Calcutta')->subMonth()->getTimestamp();
	
		// $role = $this->request->user()->role_id;
		$roleConfig = \App\RoleConfig::where('role_id', $role_id)->first();
		$jurisdictionTypeId = $roleConfig->jurisdiction_type_id;

		$userLocation = $this->getFullHierarchyUserLocation($this->request->user()->location, $jurisdictionTypeId);
		$locationKeys = $this->getFormSchemaKeys($survey_id);


			
		if(!isset($survey->entity_id)) {
			$collection_name = 'survey_results';
			$surveyResults = DB::collection('survey_results')
								->where('form_id','=',$survey_id)
								->where('userName','=',$user->id)
								->where('isDeleted','!=',true)
								->whereBetween('createdDateTime',array($startDate,$endDate))
								->where(function($q) use ($userLocation, $locationKeys) {
									if (!empty($locationKeys)) {
										foreach ($locationKeys as $locationKey) {
											if (isset($userLocation[$locationKey]) && !empty($userLocation[$locationKey])) {
												$q->whereIn($locationKey, $userLocation[$locationKey]);
											}
										}
									} else {
										foreach ($this->request->user()->location as $level => $location) {
											$q->whereIn('user_role_location.' . $level, $location);
										}
									}
								})
								->orderBy($field,$order)
								->paginate($limit);
		} else { 
			$collection_name = 'entity_'.$survey->entity_id;           
			$surveyResults = DB::collection('entity_'.$survey->entity_id)
								->where('survey_id','=',$survey_id)
								->where('userName','=',$user->id)
								->where('isDeleted','!=',true)
								->whereBetween('createdDateTime',array($startDate,$endDate))
								->where(function($q) use ($userLocation, $locationKeys) {
									if (!empty($locationKeys)) {
										foreach ($locationKeys as $locationKey) {
											if (isset($userLocation[$locationKey]) && !empty($userLocation[$locationKey])) {
												$q->whereIn($locationKey, $userLocation[$locationKey]);
											}
										}
									} else {
										foreach ($this->request->user()->location as $level => $location) {
											$q->whereIn('user_role_location.' . $level, $location);
										}
									}
								})
								->orderBy($field,$order)
								->paginate($limit);

		}      
 
		if ($surveyResults->count() === 0) {
			return response()->json(['status'=>'success','metadata'=>[],'values'=>[],'message'=>'']);
		}
		
		$createdDateTime = $surveyResults[0]['createdDateTime'];
		$responseCount = $surveyResults->count();
	   
		$result = ['form'=>['form_id'=>$survey_id,'userName'=>$surveyResults[0]['userName'],'createdDateTime'=>$createdDateTime, 'submit_count'=>$responseCount]];

		$values = [];

		foreach($surveyResults as &$surveyResult)
		{
			if (!isset($surveyResult['form_id'])) {
				$surveyResult['form_id'] = $survey_id;
			}
			$form_title =$this->generateFormTitle($survey,$surveyResult['_id'],$collection_name);
			$surveyResult['form_title'] = $form_title;
			$status= ApprovalsPending::where('entity_id',$survey->entity_id)->where('userName',$user->id)->select('status')->where('entity_type','form')->get();
			$surveyResult['status']= $status[0]->status;
			// Excludes values 'form_id','user_id','created_at','updated_at' from the $surveyResult array
			//  and stores it in values
			$values[] = Arr::except($surveyResult,['survey_id','userName','createdDateTime', 'user_role_location', 'jurisdiction_type_id']);
		}


		$result['Current page'] = 'Page '.$surveyResults->currentPage().' of '.$surveyResults->lastPage();
		$result['Total number of records'] = $surveyResults->total();
		
		return response()->json(['status'=>'success','metadata'=>[$result],'values'=>$values,'message'=>'']);

	}
	


	public function roleEvent(Request $request)
	{
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "roleEvent";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}	
			$approverRoleConfig = \App\RoleConfig::where('role_id', $role_id)->where('projects', $project_id)->get();
			 
			if(!empty($approverRoleConfig))
			{ 
				$levelDetail = \App\Jurisdiction::find($approverRoleConfig[0]->level);   
				$jurisdictions = \App\JurisdictionType::where('_id',$approverRoleConfig[0]->jurisdiction_type_id)->pluck('jurisdictions')[0];
				 
				if($key = array_search($levelDetail->levelName,$jurisdictions))
				{  
					$levelarray = array();
					for($i= $key;$i<=count($jurisdictions)-1;$i++)
					{  
						$levelId = \App\Jurisdiction::where('levelName',$jurisdictions[$i])->pluck('_id')[0]; 
						array_push($levelarray,$levelId);
					}
					
					$role = \App\RoleConfig::whereIn('level', $levelarray)->get();
					$count = 0;
					 
					$roles = array();
					DB::setDefaultConnection('mongodb');
					foreach($role as $row)
					{  
						$rolename = Role::where('_id', $row['role_id'])->get();
						// echo json_encode($rolename);
						 if(count($rolename) > 0)
						 {
							 array_push($roles,$rolename);
						 }
						 $count++;
					}
					if($roles)
					{
						$response_data = array('status' =>'300','message'=>'sucess','data' => $roles);
						return response()->json($response_data,200);
					}else{
						$response_data = array('status' =>'300','message'=>'No Roles Found..');
						return response()->json($response_data,200);
					}
					
				}					
				
			}
		 
	} 
	public function addmembertoevent(Request $request)
	{
		$user = $this->request->user();
		$header = getallheaders();
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
			$message['function'] = "addmembertoevent";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
		
		$database = $this->connectTenantDatabase($request,$org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}	
		$timestamp = Date('Y-m-d H:i:s');	
		$requestjson = json_decode(file_get_contents('php://input'), true);	
		$requestjson['function'] = "addmembertoevent";
		$this->logData($this->logInfoPath ,$requestjson,'DB');
		
		$maindata = PlannerTransactions::find($requestjson['_id']) ;	
		if(isset($maindata))
		{
			$type = $maindata['type']; 
			$title = $maindata['title']; 
			$maindata['default.updated_by'] = $user['_id'];
			$maindata['default.updated_on'] = $timestamp;
			if(isset($requestjson['participants']))
			{		
				  $count=0;
				  $attendance_count =0;
				 foreach($maindata['participants'] as $participant)
				 { 
					$maindata['participants.'.$count] =  $participant;
					if($maindata['participants.'.$count.'.attended_completed'] ==  true)
					{
						$count ++;
					} 
				 }  
				 
				 $maindata['participants'] = $requestjson['participants'];
				 $maindata['participants_count'] = count($requestjson['participants']);
				 $maindata['attended_completed'] = $count; 
			} 
			else{
					$maindata['participants'] = [];
				}
				try{  
						$storage_path = storage_path();
						 
						$files = fopen($storage_path."/logs/lumen-2019-08-27_testLog.log","a");
								 DB::setDefaultConnection('mongodb');
								foreach($requestjson['participants'] as $row){ 
								$firebase_id = User::where('_id',$row['id'])->first();  
								 fwrite($files,$firebase_id['firebase_id'] ."<br>"); 
								}
								 fclose($files);
					 
							$success = $maindata->save();
							foreach($requestjson['participants'] as $row)
							{   
								DB::setDefaultConnection('mongodb');
								$rolename = \App\Role::find($role_id);
		 
								 if(isset($rolename['display_name']))
								 {
									$newrolename =  $rolename['display_name'];
								 }
								$firebase_id = User::where('_id',$row['id'])->first(); 
								 				
									 $this->sendPushNotification(
									$this->request,
									self::NOTIFICATION_TYPE_EVENT_CREATED,
									$firebase_id['firebase_id'],
									[
										'phone' => "9881499768",
										'rolename' => $newrolename,
										'update_status' => self::NOTIFICATION_TYPE_EVENT_CREATED,
										'model' => "Planner",
										'title' => $title,
										'approval_log_id' => "Testing"
									],
									$firebase_id['org_id']
									);   
							}
							}catch(Exception $e)
							{
							$response_data = array('status' =>'200','message'=>'error','data' => $e);
							return response()->json($response_data,200); 
							}  
						
						if($success)
						{
							$response_data = array('status' =>'200','message'=>'success');
							return response()->json($response_data,200); 
						}
						else
						{
							$response_data = array('status' =>'404','message'=>'success');
							return response()->json($response_data,200); 
						}				
		}else{
			$response_data = array('status' =>'404','message'=>'Invalid Click');
			return response()->json($response_data,200); 
		}		
	}
	
	
	public function push()
	{
		$firebaseId = "cWlN-pghHqg:APA91bEQDfyepmI68A4nwmQ6-BuwLwakRHvt0NbY9oC7ijn-BUsLfyQTE3uP-uvRcVvEv7j49TLE0Yx-9j3WVhuimEhTfSzcjZyzEVRIPE8KRJzhkYl4tnLtczZgj84rFL-qEzy4JVzN";
		$orgId = "5c1b940ad503a31f360e1252";
		$this->sendPushNotification(
					$this->request,
					self::NOTIFICATION_TYPE_APPROVAL,
					$firebaseId,
					[
						'phone' => "9881499768",
						'update_status' => self::STATUS_APPROVED,
						'approval_log_id' => 'title'
					],
					$orgId
				);
	}
	
	
	  public function cron()
	{
		// $file = fopen('storage/logs/Cron/mouexpiration'.date('Y-m-d').'.log', 'a');
		
		try {
             $dbName = 'BJS_5dcfa18c5dda7605c043f2b3';

			$mongoDBConfig = config('database.connections.mongodb');
			$mongoDBConfig['database'] = $dbName;
			\Illuminate\Support\Facades\Config::set(
				'database.connections.' . $dbName,
				$mongoDBConfig
			);
			DB::setDefaultConnection($dbName); 
			
			define('API_ACCESS_KEY','AAAAU1fWiBA:APA91bGf2HfLRZUzgjGFAc0vYmXdS7tMStJXesrxd4B8Q-_z24h8IDAuAHhwxTFzJDuaOkmfBOCi7sRcVlqDlzI_HnT2_qpCPkwNd_nUwbV_M8dy5NFlTY-Bfa5LgTztqAt632YB26qE');
							$fcmUrl = 'https://fcm.googleapis.com/fcm/send';
							
            $todayDate = new \MongoDB\BSON\UTCDateTime(Carbon::now());       
            $MachineMou = MachineMou::where('status_code','!=','114')
								// ->where('mou_details.mou_expiry_date','<=',$todayDate)
								->get();
              
            if(count($MachineMou) > 0)
            {
				 
                foreach($MachineMou as $row)
                { 
					// fwrite($file,"MachineMou_ID = ". $row['_id'] ."\n");
					 
					$Mou = MachineMou::find($row['_id']);
					$Mou['status'] = 'MOU Expired';
					$Mou['status_code'] = '114';
					try{
						//$Mou->save(); 
						if(array_key_exists('provider_information',$Mou)) {
						$machineDetails = Machine::where('_id',$Mou['provider_information']['machine_id'])->get();
						
						DB::setDefaultConnection('mongodb');
						if($machineDetails){
							$user = User::where('location.state',$machineDetails[0]['state_id'])
										  ->orWhere('location.district',$machineDetails[0]['district_id'])	
										  ->orWhere('location.taluka',$machineDetails[0]['taluka_id'])	
										  ->get();
							if($user){
								foreach($user as $row){
									if($row['firebase_id'] != null || $row['firebase_id'] !=''){
									$token[]=$row['firebase_id']; 
									$id[]=$row['_id']; 
									}
								}
								  
									$notification = [
										'title' =>'MOU Expired',
										'body' => 'MOU Expired for Machine code('.$machineDetails[0]['machine_code'].')' ,
										'icon' =>'myIcon', 
										'sound' => 'mySound'
									]; 
									 
									$extraNotificationData = ["message" => $notification,"moredata" =>'dd'];

									$fcmNotification = [
										'registration_ids' => $token, 
										// 'to'        => $token, //single token
										'notification' => $notification,
										'data' => $extraNotificationData
									];
									 
									$headers = [
										'Authorization: key=' . API_ACCESS_KEY,
										'Content-Type: application/json'
									];


									$ch = curl_init();
									curl_setopt($ch, CURLOPT_URL,$fcmUrl);
									curl_setopt($ch, CURLOPT_POST, true);
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
									curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
									curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
									$result = curl_exec($ch);
									curl_close($ch);
								 
							}			  
						}
					}
						//
					}catch(Exception $e)
					{}
				 
                }
            }
             
        } catch (Exception $e) {
            fwrite($file, $e);
            fclose($file);
            $this->error("An error occurred");
        } 
	}  
}

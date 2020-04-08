<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Organisation;
use Illuminate\Support\Facades\DB;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;

use App\NotificationSchema;
use App\NotificationLog;
use App\Survey;
use App\ApprovalLog;
use App\ApprovalsPending;
use App\Jurisdiction;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Controller extends BaseController
{
	const NOTIFICATION_TYPE_USER_APPROVED = 'User Approved';//notification for matrimony
	const NOTIFICATION_TYPE_USER_REJECTION = 'User Rejection';//notification for matrimony
	const NOTIFICATION_TYPE_PROFILE_CREATION = 'Profile Creation';//notification for matrimony
	const NOTIFICATION_TYPE_MEET_ATTENDANCE = 'Meet Attendance';//notification for matrimony
	const NOTIFICATION_TYPE_MEET_INTERVIEW = 'Meet Interview';//notification for matrimony 
    
	 
	const NOTIFICATION_TYPE_APPROVAL = 'user_approval';
    const NOTIFICATION_TYPE_APPROVED = 'user approved';
    const NOTIFICATION_TYPE_REJECTED = 'user rejected';   
	
	const NOTIFICATION_TYPE_LEAVE_APPROVAL = 'leave approval';
    const NOTIFICATION_TYPE_LEAVE_APPROVED = 'leave approved';
    const NOTIFICATION_TYPE_LEAVE_REJECTED = 'leave rejected';
	
	const NOTIFICATION_TYPE_ATTENDANCE_APPROVAL = 'attendance approval';
	const NOTIFICATION_TYPE_CHECKIN_APPROVAL = 'check in to approval';
    const NOTIFICATION_TYPE_ATTENDANCE_APPROVED = 'attendance approved';
    const NOTIFICATION_TYPE_ATTENDANCE_REJECTED = 'attendance rejected';
	
	const NOTIFICATION_TYPE_COMOFF_APPROVAL = 'Comoff Approval';
	const NOTIFICATION_TYPE_COMOFF_APPROVED = 'Comoff Approved';
	const NOTIFICATION_TYPE_COMOFF_REJECTED = 'Comoff Rejected';
	const NOTIFICATION_TYPE_EVENT_CREATED = 'Event created';
	const NOTIFICATION_TYPE_EVENT_CHANGES = 'Event Updated';
	const NOTIFICATION_TYPE_MEMBER_DELETED = 'Member deleted';
	const NOTIFICATION_TYPE_TASK_ASSIGN = 'Task Creation';
	const NOTIFICATION_TYPE_TASK_CHANGES = 'Task Updated';
	const NOTIFICATION_TYPE_FORM_FILLED = 'Form filled';
	const NOTIFICATION_TYPE_CHECKIN= 'check in';
	const NOTIFICATION_TYPE_EVENT_DELETED= 'Event Deletion';

	
	const NOTIFICATION_TYPE_CHECKOUT= 'check out';
	
	const ENTITY_USER = 'userapproval';
	const ENTITY_LEAVE = 'leave';
	const ENTITY_ATTENDANCE = 'leave';
	const ENTITY_FORM = 'form';
	const ENTITY_EVENT = 'Event';

	const STATUS_PENDING = 'pending';
	const STATUS_APPROVED = 'approved';
	const STATUS_REJECTED = 'rejected';

	//matrimony Const 
	const NOTIFICATION_TYPE_USER_ATTENDANCE = 'attendance';
	const NOTIFICATION_TYPE_USER_INTERVIEW = 'interview';
	const NOTIFICATION_TYPE_USER_APPROVAL = 'User Approval';
	const NOTIFICATION_TYPE_USER_REJECTED = 'User Rejected';
	
	//structure Variables
	const NOTIFICATION_STRUCTURE_APPROVED = 'approved_structure';
	const NOTIFICATION_STRUCTURE_NONCOMPLAINT = 'non_compliant';
	const NOTIFICATION_STRUCTURE_PREPARED = 'prepared';
	const NOTIFICATION_STRUCTURE_PARTIALLY_COMPLETED = 'partially_completed';
	const NOTIFICATION_STRUCTURE_COMPLETED = 'structure_completed';
	const NOTIFICATION_STRUCTURE_CLOSED = 'structure_closed';
	const NOTIFICATION_STRUCTURE_PARTIALLY_CLOSED = 'partially_structure_closed';
	
	//machine notification variables
	const NOTIFICATION_MACHINE_MOU = 'mou_done';
	const NOTIFICATION_MACHINE_MOU_TERMINATED = 'MOU_terminated';
	const NOTIFICATION_MACHINE_MOU_EXP = 'MOU Expired';
	const NOTIFICATION_MACHINE_AVAILABLE = 'machine_available';
	const NOTIFICATION_MACHINE_DEPLOYED = 'machine_deployed';
	const NOTIFICATION_MACHINE_HALTED = 'machine_halted';
	const NOTIFICATION_MACHINE_SHIFTED = 'machine_shifted';
	const NOTIFICATION_MACHINE_FREE = 'machine_free';
	const NOTIFICATION_MACHINE_STATUS = 'machine_status';
	const NOTIFICATION_OPRATOR_LOGIN = 'oprator_login';
	
	const NOTIFICATION_OPERATOR_RELEASE = 'operator_release';
	const NOTIFICATION_OPERATOR_ASSIGNED = 'operator_assigned';
	
	
	public function logData($path, $requestData,$type,$infoData = array()) {
		 
		$this->log = new Logger('octopus_dev');
		
		$this->log->pushHandler(new StreamHandler(storage_path($path)), Logger::INFO);
		
		$infoData['requestData'] = $requestData;
		
		if ($type == 'Error') {
			
			$this->log->error(json_encode( $infoData));
			
			return true;		
		}	
		$this->log->info(json_encode( $infoData));		
 
		return true;		
	}
    


    /**
     * Sets database configuration
     *
     * @param Request $request
     * @return string
     */
    public function connectTenantDatabase(Request $request, $orgId = null)
    {
		DB::setDefaultConnection('mongodb');
        $organisation = null;
        if ($orgId instanceof Organisation) {
            $organisation = $orgId;
        } else {
            if ($orgId === null) {
                $orgId = $request->user()->org_id;
            }
            $organisation = Organisation::find($orgId);
        }
        if ($organisation === null) {
            return null;
        }
        $dbName = $organisation->name.'_'.$organisation->id;

        $mongoDBConfig = config('database.connections.mongodb');
        $mongoDBConfig['database'] = $dbName;
        \Illuminate\Support\Facades\Config::set(
            'database.connections.' . $dbName,
            $mongoDBConfig
        );
        DB::setDefaultConnection($dbName);
        return $dbName;
    }
	

    /**
     * Sends push notification to mentioned device
     *
     * @param string $type Type of notification
     * @param string $firebaseId Token of a device
	 * @params array $params List of parameters
     *
     * @return boolean
     */
    public function sendPushNotification(Request $request ,$type, $firebaseId, $params = [],$orgId)
    {
		$userdetails = $this->request->user();
		$model = "Planner"; 
        $database=$this->connectTenantDatabase($request, $orgId);
		
        if ($database === null) 
        {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        $notificationSchema = null;
		$service = '';
		$parameters = [];
		try{
        switch ($type) {
            case self::NOTIFICATION_TYPE_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_APPROVAL)->first();
				
				$notificationSchema['message.en'] =$userdetails->name ."(".$params['rolename'] .") " .$notificationSchema['message.en'];
				
				$model = "userApproval";
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break; 
				
                case self::NOTIFICATION_TYPE_APPROVED:

                $notificationSchema = NotificationSchema::where('type',self::NOTIFICATION_TYPE_APPROVED)->first();
               
				$notificationSchema['message.en'] = $userdetails->name ."(".$params['rolename'] .") " .$notificationSchema['message.en'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				 
				$model = "userApproval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
                case self::NOTIFICATION_TYPE_REJECTED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_REJECTED)->first();
				$notificationSchema['message.en'] = $userdetails->name ."(".$params['rolename'] .") " .$notificationSchema['message.en'] .". \nReason: ".$params['reason'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "userApproval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;

				case self::NOTIFICATION_TYPE_LEAVE_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_LEAVE_APPROVAL)->first();
				$notificationSchema['message.en'] = $userdetails->name ."(". $params['rolename'] .") ". $notificationSchema['message.en'];	
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "leaveApproval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_LEAVE_APPROVED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_LEAVE_APPROVED)->first();
				$notificationSchema['message.en'] = $userdetails->name ."(". $params['rolename'] .") ". $notificationSchema['message.en'] ;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "leave";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				
				case self::NOTIFICATION_TYPE_COMOFF_APPROVED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_COMOFF_APPROVED)->first();
				$notificationSchema['message.en'] = $userdetails->name ."(". $params['rolename'] .") ". $notificationSchema['message.en'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "compoffApproval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_COMOFF_REJECTED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_COMOFF_REJECTED)->first();
				$notificationSchema['message.en'] =$userdetails->name ."(". $params['rolename'] .") ". $notificationSchema['message.en'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "compoffApproval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_LEAVE_REJECTED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_LEAVE_REJECTED)->first();
				$notificationSchema['message.en'] =$userdetails->name ."(". $params['rolename'] .") ". $notificationSchema['message.en'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "leave";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_ATTENDANCE_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_ATTENDANCE_APPROVAL)->first(); 
				$notificationSchema['message.en'] = $userdetails->name." ".$notificationSchema['message.en'] ;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "attendanceApproval"; 
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_ATTENDANCE_APPROVED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_ATTENDANCE_APPROVED)->first(); 

				$notificationSchema['message.en'] = $userdetails->name ."(". $params['rolename'] .") ". $notificationSchema['message.en'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "attendanceApproval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				
				case self::NOTIFICATION_TYPE_ATTENDANCE_REJECTED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_ATTENDANCE_REJECTED)->first(); 
				$notificationSchema['message.en'] = $userdetails->name ."(". $params['rolename'] .") ". $notificationSchema['message.en'];;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "attendanceApproval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_EVENT_CREATED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_EVENT_CREATED)->first(); 
				$notificationSchema['message.en'] = $userdetails->name ."(".$params['rolename'].")" .$notificationSchema['message.en'];
				$model = 'event';//$params['model'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				
				case self::NOTIFICATION_TYPE_EVENT_CHANGES:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_EVENT_CHANGES)->first(); 
				$notificationSchema['message.en'] =  $userdetails->name ."(".$params['rolename'].")" .$notificationSchema['message.en'] ."-".$params['title'];
				 
				$model = "event";
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_EVENT_DELETED:
				 
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_EVENT_DELETED)->first();  
				$notificationSchema['message.en'] =  $userdetails->name ."(".$params['rolename'].")" .$notificationSchema['message.en'] .$params['type'];
				 
				$model = "event"; 
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_TASK_ASSIGN: 
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_TASK_ASSIGN)->first();  
				$notificationSchema['message.en'] = $userdetails->name ."(".$params['rolename'].")" .$notificationSchema['message.en'];
			 
				$model = 'task';//$params['model'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
				
                break;
				
				
				case self::NOTIFICATION_TYPE_TASK_CHANGES:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_TASK_CHANGES)->first(); 
				 
				$notificationSchema['message.en'] = $userdetails->name ."(".$params['rolename'].")" .$notificationSchema['message.en'] ."-".$params['title'];
				$model = 'task';
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				
				case self::NOTIFICATION_TYPE_COMOFF_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_COMOFF_APPROVAL)->first(); 
				
				$notificationSchema['message.en'] = $userdetails->name ."(".$params['rolename'].")" .$notificationSchema['message.en'] ;
				$model = 'compoffApproval';
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_FORM_FILLED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_FORM_FILLED)->first(); 
				$model = 'formApproval';
				$notificationSchema['message.en'] =$params['approval_log_id'] ." ".$notificationSchema['message.en']."".$userdetails->name;
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_CHECKIN:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_CHECKIN)->first(); 
				$model = 'Attendances';
				$notificationSchema['message.en'] = $notificationSchema['message.en'];
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;

                case self::NOTIFICATION_TYPE_CHECKIN_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_CHECKIN_APPROVAL)->first(); 
				$model = 'Attendances';
				$notificationSchema['message.en'] = $notificationSchema['message.en'];
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;


                case self::NOTIFICATION_TYPE_CHECKOUT:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_CHECKOUT)->first(); 
				$model = 'Attendances';
				$notificationSchema['message.en'] = $notificationSchema['message.en'];
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_MEMBER_DELETED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_MEMBER_DELETED)->first(); 
				$model = 'event';
				$notificationSchema['message.en'] = $userdetails->name ."(".$params['rolename'].")" .$notificationSchema['message.en'] .$params['type'] ."-".$params['title'];
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_USER_ATTENDANCE:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_USER_ATTENDANCE)->first(); 
				$model = 'meet';
				$notificationSchema['message.en'] = $notificationSchema['message.en'];
				 
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				 
				case self::NOTIFICATION_TYPE_USER_INTERVIEW:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_USER_INTERVIEW)->first(); 
				$model = 'meet';
				$notificationSchema['message.en'] = $notificationSchema['message.en'];
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_USER_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_USER_APPROVAL)->first(); 
				$model = 'meet';
				$notificationSchema['message.en'] = $notificationSchema['message.en'];
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_USER_REJECTED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_USER_REJECTED)->first(); 
				$model = 'meet';
				$notificationSchema['message.en'] = $notificationSchema['message.en'];
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_USER_APPROVED:   
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_USER_APPROVED)->first();
				 $model = 'meet';
				//echo json_encode($notificationSchema);exit;	
				
				$notificationSchema['message.en'] =$notificationSchema['message.en'];
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_STRUCTURE_APPROVED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_APPROVED)->first();
										
					$strMsg = str_replace('#StructureCode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#Taluka', $request->talukaName, $strMsg); 
					$notificationSchema['message.en'] = str_replace('#Village', $request->villageName, $strMsg); 

					//echo $notificationSchema['message.en'];exit;
					$notificationSchema['message.en'] = $userdetails->name ."(".$params['rolename'] .") " .$notificationSchema['message.en'];
					$model = "StructureApproved";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					break;
							
				case self::NOTIFICATION_STRUCTURE_NONCOMPLAINT:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_NONCOMPLAINT)->first();
					
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "Non Complaint";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					break;
					
				case self::NOTIFICATION_STRUCTURE_PREPARED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_PREPARED)->first();
					
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 					
					$notificationSchema['message.en'] = $strMsg;
					$model = "Structure Prepared";
					
					//echo $notificationSchema['message.en'];exit;
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					break;					
					
				case self::NOTIFICATION_STRUCTURE_PARTIALLY_COMPLETED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_PARTIALLY_COMPLETED)->first();
					//echo "ggggg".$params['code'];exit;
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "Structure Partially Completed";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					break;
				
				case self::NOTIFICATION_STRUCTURE_COMPLETED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_COMPLETED)->first();
					
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "Structure Completed";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;

				case self::NOTIFICATION_STRUCTURE_CLOSED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_CLOSED)->first();
					
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "Structure Completed";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;	 	
				
				case self::NOTIFICATION_MACHINE_MOU:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_MOU)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "Machine MOU Done";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;						
				
				case self::NOTIFICATION_MACHINE_MOU_TERMINATED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_MOU_TERMINATED)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "Machine MOU Terminated";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;

				case self::NOTIFICATION_MACHINE_AVAILABLE:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_AVAILABLE)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#TalukaName', $params['params']['talukaName'], $strMsg); 
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "Machine Available";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break; 
					
				case self::NOTIFICATION_MACHINE_SHIFTED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_SHIFTED)->first();
					//echo $params['code'];exit;
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#oldStructureCode', $params['params']['current_structure_code'], $strMsg);
					$strMsg = str_replace('#newStructureCode', $params['params']['new_structure_code'], $strMsg);
				
					//newStructureCode
					$notificationSchema['message.en'] = $strMsg;
					$model = "Machine Available";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;
					
				case self::NOTIFICATION_MACHINE_DEPLOYED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_DEPLOYED)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#StructureCode', $params['params']['struture_code'], $strMsg);
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "Machine Available";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break; 

				case self::NOTIFICATION_MACHINE_STATUS:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_STATUS)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#status', $params['params']['status'].'ed', $strMsg);
					//echo $strMsg;exit;
					$notificationSchema['message.en'] = $strMsg;
					$model = "Machine Status";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break; 

				case self::NOTIFICATION_MACHINE_HALTED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_HALTED)->first();
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg);
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "Machine Status";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;
					
				case self::NOTIFICATION_OPRATOR_LOGIN:
					
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_OPRATOR_LOGIN)->first();
					
					$strMsg = str_replace('#name', $params['params']['userName'], $notificationSchema['message.en']);
					$strMsg = str_replace('#machinecode', $params['code'], $strMsg);
										
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "Oprator Login";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;
							
						
				case self::NOTIFICATION_MACHINE_FREE:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_FREE)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#status', $params['params']['status'].'ed', $strMsg);
					//echo $strMsg;exit;
					$notificationSchema['message.en'] = $strMsg;
					$model = "Machine Free From Taluka";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break; 


				case self::NOTIFICATION_STRUCTURE_PARTIALLY_CLOSED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_PARTIALLY_CLOSED)->first();
					
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "Structure Completed";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;
						

        }
      
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);
			 
		$notificationBuilder = new PayloadNotificationBuilder($notificationSchema['title']['en']);
		$notificationBuilder->setBody($notificationSchema['message.en'])->setSound('default');

		$dataBuilder = new PayloadDataBuilder();	 	 
		$dataBuilder->addData([
			'notification' => [
				'text' => $notificationSchema['message'],
				'click_action' => $notificationSchema['type']	
			],
			'to' => $firebaseId,
			'title'=>$notificationSchema['title']['en'],
			'message'=>$notificationSchema['message.en'],			
			'toOpen' => $model,
			'data' => [
				'action' => [
					'service' => $service,
					'params' => $parameters, 
					'method' => $notificationSchema['method']
				] 
			]
		]);
  
		$option = $optionBuilder->build();
		$notification = $notificationBuilder->build();
		$data = $dataBuilder->build();

		$token = $firebaseId;
 
		$downstreamResponse = FCM::sendTo($token, $option, $notification, $data);
		$fcmResponse = [
			'number of success' => $downstreamResponse->numberSuccess(),
			'number of failure' => $downstreamResponse->numberFailure(),
			'number of modification' => $downstreamResponse->numberModification()
		];
 
		$notificationLog = NotificationLog::create([
			'firebase_id' => $firebaseId,
			'firebase_response' => $fcmResponse
		]);
		$notificationLog->notificationSchema()->associate($notificationSchema);
		$notificationLog->save();
		}
		catch(Exception $e)
			{
			$response_data = array('status' =>'200','message'=>'error','data' => $e);
			return response()->json($response_data,200); 
			} 
			return true;		
    } 
	
	//notification sending for community
	public function SendNotification(Request $request ,$type, $firebaseId,$param =[],$orgId)
	{
		define('API_ACCESS_KEY','AAAAU1fWiBA:APA91bGf2HfLRZUzgjGFAc0vYmXdS7tMStJXesrxd4B8Q-_z24h8IDAuAHhwxTFzJDuaOkmfBOCi7sRcVlqDlzI_HnT2_qpCPkwNd_nUwbV_M8dy5NFlTY-Bfa5LgTztqAt632YB26qE');
		$fcmUrl = 'https://fcm.googleapis.com/fcm/send';
		$token=$firebaseId;
		// $this->connectTenantDatabase($request, $orgId);
		$database = $this->connectTenantDatabase($request, $orgId);
		if ($database === null) {
			return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
		}
		 
		try{
        switch($type){
            case self::NOTIFICATION_TYPE_PROFILE_CREATION:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_PROFILE_CREATION)->first(); 
				$message = $notificationSchema['message.en']; 
				$title = $notificationSchema['title.en']; 
                break; 
				
            case self::NOTIFICATION_TYPE_USER_APPROVED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_USER_APPROVED)->first();  
				$message = $notificationSchema['message.en'] .$param['title'] .".";  
				$title = $notificationSchema['title.en']; 
                break; 
				
            case self::NOTIFICATION_TYPE_USER_REJECTION:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_USER_REJECTION)->first(); 
				$message = $notificationSchema['message.en'] .$param['title'] ."."; 
				$title = $notificationSchema['title.en']; 
                break; 
				
            case self::NOTIFICATION_TYPE_MEET_ATTENDANCE:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_MEET_ATTENDANCE)->first(); 
				$message = $notificationSchema['message.en'] .$param['title'] .".";  
				$title = $notificationSchema['title.en']; 
                break; 
				
            case self::NOTIFICATION_TYPE_MEET_INTERVIEW:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_MEET_INTERVIEW)->first(); 
				$message = $notificationSchema['message.en']; 
				$title = $notificationSchema['title.en']; 
                break;
				 
            case self::NOTIFICATION_TYPE_MEET_INTEREST:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_MEET_INTEREST)->first(); 
				$message = $param['name'] ." ". $notificationSchema['message.en']; 
				$title = $notificationSchema['title.en']; 
                break;
			}
		}catch(Exception $e)
		{
			return $e;
		}
		
		$notification = [
            'title' =>$title,
            'body' => $message,
            'icon' =>'myIcon', 
            'sound' => 'mySound'
        ]; 
		 
        $extraNotificationData = ["message" => $notification,"moredata" =>'dd'];

        $fcmNotification = [
            //'registration_ids' => $tokenList, //multple token array
            'to'        => $token, //single token
            'notification' => $notification,
            'data' => $extraNotificationData
        ];
		// echo json_encode($fcmNotification);die();		
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
		
		return true;
		
	}
	
	
	
     /**
     * generates form title for a particular form id and form response
     *
     * @param string $form_obj_id form schema object id
     * @param string $formresponse_obj_id form response object id
	 * @param string $collection_name name of collection where form response is stored
     *
     * @return string 
     */   
    public function generateFormTitle($form_obj_id,$formresponse_obj_id,$collection_name='survey_results'){
        
        $levels = $this->getLevels();
        if ($form_obj_id instanceof Survey) {
            $form_obj = $form_obj_id;
        }else{
            $form_obj = Survey::find($form_obj_id);
        }
        
        $formresponse_obj = DB::collection($collection_name)->where('_id', $formresponse_obj_id)->first();

        if (is_null($formresponse_obj) || is_null($form_obj)) {
            return '';
        }

        $title_pretext=(isset($form_obj->pretext_title) && $form_obj->pretext_title != '')? $form_obj->pretext_title.' ' : '';
        $title_posttext=(isset($form_obj->posttext_title) && $form_obj->posttext_title != '')? ' '.$form_obj->posttext_title : '';
        $title_fields = isset($form_obj->title_fields)?$form_obj->title_fields:[];
        $separator = isset($form_obj->separator)?$form_obj->separator:'';

        $title_fields_str = '';
        if(!empty($title_fields)){
            if($separator != ''){
                $separator = ' '.$separator.' ';      
            }else{
                $separator = ' ';
            }
            $field_values = [];
            foreach($title_fields as $title_field){
                $model_name = '';
                foreach($levels as $level){
                    if(stripos($title_field, $level) !== false){
                        $model_name = $level;
                        break;
                    }
                }
                $field_name = trim($title_field);
                if($model_name != ''){
                    if($collection_name != 'survey_results' && stripos($collection_name,'entity_') === false){
                        $field_value = array_key_exists(trim($title_field).'_id',$formresponse_obj) ? DB::collection($model_name)->where('_id', $formresponse_obj[trim($title_field).'_id'])->first():null;
                    }else{
                        $field_value = array_key_exists(trim($title_field),$formresponse_obj)? DB::collection($model_name)->where('_id', $formresponse_obj[trim($title_field)])->first() :null;
                    }
                    $value = '';
                    if($field_value !== null){
                        $value = $field_value['name'];
                    }

                    $field_values[] = $value;
                }else{
                    $field_values[] = isset($formresponse_obj[$field_name]) ? $formresponse_obj[$field_name] : '';
                }
            }
            $title_fields_str = implode($separator,$field_values);
        }
        $form_title =$title_pretext.$title_fields_str.$title_posttext;
        $form_title =$title_fields_str;
		
        return $form_title;

    }

	/**
	 * Compares location data
	 * @param array $requestLocation
	 * @param array $storedLocation
	 * @return boolean
	 */
	public function compareLocation(array $requestLocation, array $storedLocation)
	{
		$requestJurisdictions = array_keys($requestLocation);
		$storedJurisdictions = array_keys($storedLocation);

		if ($requestJurisdictions != $storedJurisdictions) {
			return true;
		}

		foreach ($requestJurisdictions as $jurisdiction) {
			if ($requestLocation[$jurisdiction] != $storedLocation[$jurisdiction]) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Creates Approval Log record.
	 *
	 * @param Request $request
	 * @param string $entityId
	 * @param string $entityType
	 * @param array $approverIds
	 * @param string $status
	 * @param string $userName
	 * @param string $reason
	 * @return string
	 */
	public function addApprovalLog(Request $request, $entityId, $entityType, $approverIds, $status, $userName, $reason = '', $orgId)
	{
		$this->connectTenantDatabase($request, $orgId);
		$approverLog = ApprovalLog::create([
			'entity_id' => $entityId,
			'entity_type' => "userapproval",
			'approver_ids' => $approverIds,
			'status' => $status,
			'userName' => $userName,
			'reason' => $reason,
			'createdDateTime' => \Carbon\Carbon::now()->getTimestamp(),
			'updatedDateTime' => \Carbon\Carbon::now()->getTimestamp()
		]);
		

		$AApprovalsPending = ApprovalsPending::where('entity_id',$entityId)->where('entity_type',$entityType)->first(); 
		 
		if(empty($AApprovalsPending)){
			$AApprovalsPending = new ApprovalsPending;
		}
			$AApprovalsPending->entity_id = $entityId;
			$AApprovalsPending->entity_type = "userapproval";
			$AApprovalsPending->approver_ids = $approverIds;
			$AApprovalsPending->status = $status;
			$AApprovalsPending->userName = $userName;
			$AApprovalsPending->reason = $reason;  
			$AApprovalsPending->createdDateTime = new \MongoDB\BSON\UTCDateTime(\Carbon\Carbon::now()->getTimestamp());
			$AApprovalsPending->updatedDateTime = new \MongoDB\BSON\UTCDateTime(\Carbon\Carbon::now()->getTimestamp());
			$AApprovalsPending->save();
		
			return $AApprovalsPending->id;
	}

	/**
	 * Get approvers based on location of User
	 *
	 * @param Request $request
	 * @param string $roleId
	 * @param array $userLocation
	 * @param string $orgId
	 * @return array
	 */
	public function getApprovers(Request $request, $roleId, $userLocation, $orgId)
	{
		$this->connectTenantDatabase($request, $orgId);
		$roleConfig = \App\RoleConfig::where('role_id', $roleId)->first();
		
		$approverRoleConfig = \App\RoleConfig::where('role_id', $roleConfig->approver_role)->first();
		
		if ($approverRoleConfig === null) {
			/* $approvers = \App\User::where('org_id', $orgId)->where('is_admin',true)->where('approved',true)->first();
			print_R($approvers);die();
			return $approvers; */
			return [];
		}
		
		$levelDetail = \App\Jurisdiction::find($approverRoleConfig->level); 
		// $jurisdictions = \App\JurisdictionType::where('_id',$roleConfig->jurisdiction_type_id)->pluck('jurisdictions')[0];
		$jurisdictions = \App\JurisdictionType::where('_id',$roleConfig->jurisdiction_type_id)->get();
		 
		DB::setDefaultConnection('mongodb');
		$approvers = \App\User::where('orgDetails.role_id', $roleConfig->approver_role);
		if($jurisdictions){
		 foreach ($jurisdictions as $singleLevel) {
			
			if (isset($userLocation[strtolower($singleLevel)])) {
				$approvers->whereIn('orgDetails.location.' . strtolower($singleLevel), $userLocation[strtolower($singleLevel)]);
				
				if ($singleLevel == $levelDetail->levelName) {
					break;
				}
			}
		} 
		
		return $approvers->get()->all();
		}else{
		return [];	
		}
	}

	/**
	 * Returns status
	 *
	 * @param string $status
	 * @return boolean|string
	 */
	public function getStatus($status)
	{
		if ($status == self::STATUS_PENDING) {
			return self::STATUS_PENDING;
		} elseif ($status == self::STATUS_APPROVED) {
			return self::STATUS_APPROVED;
		} elseif ($status == self::STATUS_REJECTED) {
			return self::STATUS_REJECTED;
		}
		return false;
    }
    
    public function getLevels(){
        return Jurisdiction::all()->pluck('levelName');
    }

    public function getFullHierarchyUserLocation(array $userLocation, $jurisdictionTypeId)
    {
        $locations = \App\Location::where('jurisdiction_type_id', $jurisdictionTypeId);
        foreach ($userLocation as $levelName => $values) {
            $locations->whereIn($levelName . '_id', $values);
        }
        $data = $locations->get();
        $getJurisdictionTypeLevels = \App\JurisdictionType::find($jurisdictionTypeId)->jurisdictions;
        foreach ($getJurisdictionTypeLevels as $level) {
            if (!isset($userLocation[strtolower($level)])) {
                $userLocation[strtolower($level)] = $data->pluck(strtolower($level) . '_id')->unique()->values()->all();
            }
        }
        return $userLocation;
    }

    public function getFormSchemaKeys($formId)
    {
        $keys = [];
        $locationKeys = [];
        $levels = array_map('strtolower', $this->getLevels()->toArray());
        $formSchema = Survey::find($formId)->json;

        foreach(json_decode($formSchema, true)['pages'] as $page) {
            // Accessing the value of key elements to obtain the names of the questions
            foreach($page['elements'] as $element) {
                if($element['type'] == 'matrixdynamic'){
                    $columns = array_key_exists('columns',$element)? $element['columns']: [];
                    foreach($columns as $column) {
                        $keys[] = $column['name'];
                    }
                }else{
                    $keys[] = $element['name'];
                }
            }
        }
        foreach ($keys as $key) {
            if (in_array($key, $levels)) {
                $locationKeys[] = $key;
            }
        }
        return $locationKeys;
    }
	
	public function sendSSNotification($request,$params, $roleArr, $user= null) {		
		
		
		$logInfoPath = "logs/".$params['modelName']."/DB/Notification/logs_".date('Y-m-d').'.log';
		$errorPath = "logs/".$params['modelName']."/Error/Notification/logs_".date('Y-m-d').'.log';

		$stateId = $params['stateId'];
		if ($user != null) {
			
			$params['userName'] = $user->name;
			
		}	
		$districtId = $params['districtId'];
		$talukaId = $params['talukaId'];
		$villageId = isset($params['villageId']) ? $params['villageId'] : '';
		
		
		//loop for role
		foreach ($roleArr as  $roleCode) {		
			DB::setDefaultConnection('mongodb');
			$roleData = \App\Role::where(['role_code'=>$roleCode, 'org_id'=>$params['org_id']])->first();
			
			if (!$roleData) {
				$responseData = array( 'code'=>400,
									   'status' =>'error',
									   'roleCode'=>$roleCode,									  
									   'structureCode'=>$params['code'],
									   'message'=> 'Role missing in role collection');									
				
				$this->logData($errorPath,$params,'Error',$responseData);
				
				return true;
					
			}//echo $roleData->_id;exit;
			$stArry = array($stateId);			
			$query = \App\User::where(['role_id' => $roleData->_id])			
			 ->whereIn('location.state',$stArry);
			
			if ($roleCode == '110') {
				$dtArry = array($districtId);	
				$query->whereIn('location.district',$dtArry);
			}
			// 'location.district' => $districtId]);
			
			if ($roleCode == '111') {
				$talukaIds  = array($talukaId);
				$query->whereIn('location.taluka',$talukaIds);
			}

			if ($roleCode == '114') {
				$villageIds  = array($villageId);
				$query->whereIn('location.village',$villageIds);
			}			
			$userDetails = $query->select('firebase_id','name','phone')
										->get()->toArray();
			
			//print_r($userDetails);exit;
			foreach($userDetails as $userData) {
				//echo "rwerewr".$userData['firebase_id'];exit;
				if (!isset($userData['firebase_id'])) {
					
					$responseData = array( 'code'=>400,
									   'status' =>'error',
									   'roleCode'=>$roleCode,									  
									   'structureCode'=>$params['code'],
									   'message' => 'firebase_id missing in User collection');									
				
				$this->logData($errorPath,$params,'Error',$responseData);
					//echo "ewrwerwr";exit;
					return true;				
				}
				$dataD = $this->sendPushNotification(
					$request,
					$params['request_type'],
					$userData['firebase_id'],
					//'eI_FdLaocPU:APA91bE_HZck00WgG4HJmIuDQJu6jolos0rFeyO_fN1N9qwqOUrHFv1adpLRQTX4n3Y1w6MKCEFtBk9iQOUsDHcS3G1AGWEl2rQgX39gn1y4Oqmnlh2eXs0uUNUVhdGkQG7L6HNjkM7h',
					[ 
						'phone'=> $userData['phone'],
						//'9028724868',
						'update_status' => $params['update_status'],						
						'rolename' => $roleData->display_name,
						'code'=> $params['code'],
						'params'=>$params
					],
				   $params['org_id']
				);
				if ($dataD) { 
				
					$responseData = array('code'=>200,
										  'status' =>'success',
										   'roleName'=>$roleData->display_name,
										   'roleUserCode'=>$userData['_id'],
										   'structureCode'=>$params['code']);									
					
					$this->logData($logInfoPath,$params,'DB',$responseData);
				
				} else {
					$responseData = array( 'code'=>400,
										   'status' =>'error',
										   'roleName'=>$roleData->display_name,
										   'roleUserCode'=>$userData['_id'],
										   'structureCode'=>$params['code']);									
					
					$this->logData($logInfoPath,$params,'DB',$responseData);
				
				}
			}	
		}
		return true;
	}

}

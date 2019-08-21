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

class Controller extends BaseController
{

    const NOTIFICATION_TYPE_APPROVAL = 'user_approval';
    const NOTIFICATION_TYPE_APPROVED = 'user approved';
    const NOTIFICATION_TYPE_REJECTED = 'user rejected';   
	
	const NOTIFICATION_TYPE_LEAVE_APPROVAL = 'leave approval';
    const NOTIFICATION_TYPE_LEAVE_APPROVED = 'leave approved';
    const NOTIFICATION_TYPE_LEAVE_REJECTED = 'leave rejected';
	
	const NOTIFICATION_TYPE_ATTENDANCE_APPROVAL = 'attendance approval';
    const NOTIFICATION_TYPE_ATTENDANCE_APPROVED = 'attendance approved';
    const NOTIFICATION_TYPE_ATTENDANCE_REJECTED = 'attendance rejected';
	
	const NOTIFICATION_TYPE_COMOFF_APPROVAL = 'Comoff Approval';
	const NOTIFICATION_TYPE_COMOFF_APPROVED = 'Comoff Approved';
	const NOTIFICATION_TYPE_COMOFF_REJECTED = 'Comoff Rejected';
	const NOTIFICATION_TYPE_EVENT_CREATED = 'Event created';
	const NOTIFICATION_TYPE_EVENT_CHANGES = 'Event changes';
	const NOTIFICATION_TYPE_MEMBER_DELETED = 'Member deleted';
	const NOTIFICATION_TYPE_TASK_ASSIGN = 'Task assigned';
	const NOTIFICATION_TYPE_TASK_CHANGES = 'Task changes';
	const NOTIFICATION_TYPE_FORM_FILLED = 'Form filled';
	const NOTIFICATION_TYPE_CHECKIN= 'check in';

	const ENTITY_USER = 'userapproval';
	const ENTITY_LEAVE = 'leave';
	const ENTITY_ATTENDANCE = 'leave';
	const ENTITY_FORM = 'form';
	const ENTITY_EVENT = 'Event';

	const STATUS_PENDING = 'pending';
	const STATUS_APPROVED = 'approved';
	const STATUS_REJECTED = 'rejected';

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
        $this->connectTenantDatabase($request, $orgId);
        $notificationSchema = null;
		$service = '';
		$parameters = [];

        switch ($type) {
            case self::NOTIFICATION_TYPE_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_APPROVAL)->first();
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;
				$model = "Approval";
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break; 
				
                case self::NOTIFICATION_TYPE_APPROVED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_APPROVED)->first();
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "Approval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
                case self::NOTIFICATION_TYPE_REJECTED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_REJECTED)->first();
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name .". \nReason: ".$params['reason'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "Approval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;

				case self::NOTIFICATION_TYPE_LEAVE_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_LEAVE_APPROVAL)->first();
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;	
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "Approval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_LEAVE_APPROVED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_LEAVE_APPROVED)->first();
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "Approval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				
				case self::NOTIFICATION_TYPE_COMOFF_APPROVED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_COMOFF_APPROVED)->first();
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "Approval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_COMOFF_REJECTED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_COMOFF_REJECTED)->first();
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "Approval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_LEAVE_REJECTED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_LEAVE_REJECTED)->first();
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "Approval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_ATTENDANCE_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_ATTENDANCE_APPROVAL)->first(); 
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "Approval"; 
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_ATTENDANCE_APPROVED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_ATTENDANCE_APPROVED)->first(); 
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "Approval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				
				case self::NOTIFICATION_TYPE_ATTENDANCE_REJECTED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_ATTENDANCE_REJECTED)->first(); 
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;
				$service = $notificationSchema->service . '/' . $params['phone'];
				$model = "Approval";
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_EVENT_CREATED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_EVENT_CREATED)->first(); 
				$notificationSchema['message.en'] = $notificationSchema['message.en'] ."".$userdetails->name;
				$model = $params['model'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				
				case self::NOTIFICATION_TYPE_EVENT_CHANGES:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_EVENT_CHANGES)->first(); 
				$notificationSchema['message.en'] = $userdetails->name .$notificationSchema['message.en'] ;
				$model = "Planner";
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_TASK_ASSIGN:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_TASK_ASSIGN)->first(); 
				
				$notificationSchema['message.en'] = $notificationSchema['message.en']."".$userdetails->name ." in ".$params['update_status'];
				$model = $params['model'];
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				
				case self::NOTIFICATION_TYPE_TASK_CHANGES:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_TASK_CHANGES)->first(); 
				
				$notificationSchema['message.en'] = $userdetails->name ." ".$notificationSchema['message.en'];
				$model = 'Planner';
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				
				case self::NOTIFICATION_TYPE_COMOFF_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_COMOFF_APPROVAL)->first(); 
				
				$notificationSchema['message.en'] = $notificationSchema['message.en']." ".$userdetails->name ;
				$model = 'Planner';
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_FORM_FILLED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_FORM_FILLED)->first(); 
				$model = 'Form';
				$notificationSchema['message.en'] =$params['approval_log_id'] ." ".$notificationSchema['message.en']."".$userdetails->name;
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_CHECKIN:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_CHECKIN)->first(); 
				$model = 'Planner';
				$notificationSchema['message.en'] = $notificationSchema['message.en'];
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
                break;
				
				case self::NOTIFICATION_TYPE_MEMBER_DELETED:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_MEMBER_DELETED)->first(); 
				$model = 'Planner';
				$notificationSchema['message.en'] = $notificationSchema['message.en'].$params['title'];
				
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['approval_log_id'], 'approval_log_id' => $params['approval_log_id']];
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
		$jurisdictions = \App\JurisdictionType::where('_id',$roleConfig->jurisdiction_type_id)->pluck('jurisdictions')[0];
		 
		DB::setDefaultConnection('mongodb');
		$approvers = \App\User::where('role_id', $roleConfig->approver_role);
		 foreach ($jurisdictions as $singleLevel) {
			
			if (isset($userLocation[strtolower($singleLevel)])) {
				$approvers->whereIn('location.' . strtolower($singleLevel), $userLocation[strtolower($singleLevel)]);
				
				if ($singleLevel == $levelDetail->levelName) {
					break;
				}
			}
		} 
 
		return $approvers->get()->all();
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

}

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
use App\Jurisdiction;

class Controller extends BaseController
{

    const NOTIFICATION_TYPE_APPROVAL = 'user_approval';

	const ENTITY_USER = 'user';
	const ENTITY_LEAVE = 'leave';

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
        $this->connectTenantDatabase($request, $orgId);
        $notificationSchema = null;
		$service = '';
		$parameters = [];
        switch ($type) {
            case self::NOTIFICATION_TYPE_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_APPROVAL)->first();
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['update_status'], 'approval_log_id' => $params['approval_log_id']];
                break;
        }

        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);

		$notificationBuilder = new PayloadNotificationBuilder($notificationSchema->title['en']);
		$notificationBuilder->setBody($notificationSchema->message['en'])->setSound('default');

		$dataBuilder = new PayloadDataBuilder();
		$dataBuilder->addData([
			'notification' => [
				'text' => $notificationSchema->message,
				'click_action' => $notificationSchema->type
			],
			'to' => $firebaseId,
			'data' => [
				'action' => [
					'service' => $service,
					'params' => $parameters,
					'method' => $notificationSchema->method
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
			'entity_type' => $entityType,
			'approver_ids' => $approverIds,
			'status' => $status,
			'userName' => $userName,
			'reason' => $reason,
			'createdDateTime' => \Carbon\Carbon::now()->getTimestamp(),
			'updatedDateTime' => \Carbon\Carbon::now()->getTimestamp()
		]);
		return $approverLog->id;
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
        $getJurisdictionTypeLevels = \App\JurisdictionType::find($jurisdictionTypeId)->jurisdictions;
        foreach ($getJurisdictionTypeLevels as $level) {
            if (!isset($userLocation[strtolower($level)])) {
                $userLocation[strtolower($level)] = $locations->pluck(strtolower($level) . '_id')->all();
            }
        }
        return $userLocation;
    }

}

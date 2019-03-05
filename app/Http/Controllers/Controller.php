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

class Controller extends BaseController
{

    const NOTIFICATION_TYPE_APPROVAL = 'user_approval';

    /**
     * Sets database configuration
     *
     * @param Request $request
     * @return string
     */
    public function connectTenantDatabase(Request $request, $orgId = null)
    {
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
    public function sendPushNotification($type, $firebaseId, $params = [])
    {
        $notificationSchema = null;
		$service = '';
		$parameters = [];
        switch ($type) {
            case self::NOTIFICATION_TYPE_APPROVAL:
                $notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_TYPE_APPROVAL)->first();
				$service = $notificationSchema->service . '/' . $params['phone'];
				$parameters = ['update_status' => $params['update_status']];
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
            }
            $field_values = [];
            foreach($title_fields as $title_field){
                $field_values[] = isset($formresponse_obj[$title_field]) ? $formresponse_obj[$title_field] : '';
            }
            $title_fields_str = implode($separator,$field_values);
        }
        $form_title =$title_pretext.$title_fields_str.$title_posttext;
        return $form_title;

    }
}

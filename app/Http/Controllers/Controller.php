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
}

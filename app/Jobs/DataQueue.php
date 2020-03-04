<?php
 
namespace App\Jobs;
 
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 use GuzzleHttp\Client;
//use ShiftOneLabs\LaravelSqsFifoQueue\Bus\SqsFifoQueueable;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Organisation;
use App\NotificationSchema;
use App\NotificationLog;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;
use Carbon\Carbon;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\StructurePreparation;
use App\Structure;
use App\MachineMou;
use App\Role;
use App\User; 
use App\Machine;


ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);

class DataQueue implements ShouldQueue

{
    use InteractsWithQueue, Queueable, SerializesModels;
	 
    public $dateObj;
	public $dd;
	public $tries = 5;
	
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
	

 
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
		$functionName = $request['functionName'];
		switch ($functionName) {
			
			case 'createStructure':

				//get Taluka name
				$talukaName = \App\Taluka::find($request->taluka_id);
				$request['talukaName'] = $talukaName->name;
				
				//get village name
				$villageName = \App\Village::find($request->village_id);
				$request['villageName'] = $villageName->name;
				$this->dateObj = $request->only(['params',
												'roleArr',
												'villageName',
												'talukaName',
												'functionName'
												]
												);
												//$this->handleData();
				//echo '<pre>';print_r($this->dateObj );exit;
				break;				
				
		    case 'saveStructurePreparedData' : 				
					
				$urls = [];
				if ($request->has('imageArraySize')) {
				
					for ($cnt = 0; $cnt < $request['imageArraySize']; $cnt++) {
						
						$fileName = 'Structure'.$cnt; 		
						
						if ($request->file($fileName)->isValid()) {
						
							$fileInstance = $request->file($fileName);
							$name = $fileInstance->getClientOriginalName();
							$ext = $request->file($fileName)->getClientMimeType(); 
							
							$newName = uniqid().'_'.$name.'.jpg';
							$s3Path = $request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_STRUCTURE'), $newName, 'octopusS3');
							
							$urls[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_STRUCTURE').'/' . $newName;
						}					
					}
			}			
		
				$request['urls'] = $urls;
				$this->dateObj = $request->only(['structure_id',
												'params',
												'roleArr',
												'urls',
												'structurePreparationId',
												'saveStructurePreparedData',
												'functionName'
												]
												);
												//$this->handle();
				break;
				
			case 'closeStructure';			
				$urls = [];
				if ($request->has('imageArraySize')) {
					for ($cnt = 0; $cnt < $request['imageArraySize']; $cnt++) {
							
						$fileName = 'Structure'.$cnt; 		
						//echo$this->request['imageArraySize']. "--erwerw r";exit;
						if ($request->file($fileName)->isValid()) {
						
							$fileInstance = $request->file($fileName);
							$name = $fileInstance->getClientOriginalName();
							$ext = $request->file($fileName)->getClientMimeType(); 
							
							$newName = uniqid().'_'.$name.'.jpg';
							$s3Path = $request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_STRUCTURE'), $newName, 'octopusS3');
							
							$urls[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_STRUCTURE').'/' . $newName;
						}
					}
				}				
				$request['urls'] = $urls;	
				$this->dateObj = $request->only(['params',
												'roleArr',
												'functionName',
												'structure_id',
												'structureStatus',
												'urls'
												]
												);
				//$this->handle();								
				//echo '<pre>';print_r($this->dateObj );exit;
				break;				
				
			case 'machineMou' :

				$accountImage = 0;
				$licenseImage = 0;
				
				$accountImageUrl = [];
				$licenseImageUrl = [];
				
				if ($request->has('imageArraySize')) {
				
					for ($cnt = 0; $cnt < $request['imageArraySize']; $cnt++) {
						
						
						$fileName = 'accountImage';
							
						if ($request->has($fileName)) {
							
							if ($request->file($fileName)->isValid()) {
						
								$fileInstance = $request->file($fileName);
							
								$name = $fileInstance->getClientOriginalName();
								$ext = $request->file($fileName)->getClientMimeType(); 
								$newName = uniqid().'_'.$name.'.jpg';
								$s3Path = $request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
								
								$accountImageUrl[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
							}
							$accountImage++;	
						}
						
						/*$fileName = 'licenseImage';
							
						if ($request->has($fileName)) {				
							
								if ($request->file($fileName)->isValid()) {
							
									$fileInstance = $request->file($fileName);
								
									$name = $fileInstance->getClientOriginalName();
									
									$newName = uniqid().'_'.$name.'.jpg';
									$s3Path = $request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
									
									$licenseImageUrl[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
									
								}					
							$licenseImage++;			
						}	*/		
						break;		
					}
				}
				$temp = $request['formData'];
				$requestJson = json_decode($temp);

				//$request['licenseImageUrl'] = $licenseImageUrl;
				$request['accountImageUrl'] = $accountImageUrl;
				//$request['provider_information'] = $requestJson->provider_information;
				//$request['operator_details'] = $requestJson->operator_details;
				$request['machine'] = $requestJson->machine;
				
				$this->dateObj = $request->only(['params',
												'roleArr',
												'accountImageUrl',
												//'licenseImageUrl',
												'functionName',
												//'provider_information',
												//'operator_details',
												'machine',
												'mouId',
												//'muCnt'
												]
												);
												//$this->handle();
												
				break;									
				
				case 'MOUTerminateDeployed':
				
					$this->dateObj = $request->only(['params',
												'roleArr',
												'functionName'
												]
												);
												
				//echo '<pre>';print_r($this->dateObj );exit;
				break;	

				case 'statusChange':

					$this->dateObj = $request->only(['params',
												'roleArr',
												'functionName'
												]
												);				
					break;					
				case 'machineDeployed':
					$this->dateObj = $request->only(['params',
												'roleArr',
												'functionName'
												]
												);
					//$this->handle();
					break;
					
				case 'machineWorkingDetails':
				
				/*$url = [];
				if ($request->has('imageArraySize')) {
					for ($cnt = 0; $cnt < $request['imageArraySize']; $cnt++) {
							
						$fileName = 'image'.$cnt; 		
						
						if ($request->file($fileName)->isValid()) {
						
							$fileInstance = $request->file($fileName);
							$name = $fileInstance->getClientOriginalName();
							$ext = $request->file($fileName)->getClientMimeType(); 
							//echo $ext;exit;
							$newName = uniqid().'_'.$name.'.jpg';
							$s3Path = $request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
							
							//if ($s3Path == null || !$s3Path) {
								//return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
							//}
							$url[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
							//return response()->json(['status' => 'success', 'data' => ['url' => $result], 'message' => 'Image successfully uploaded in S3']);
						}
					}
				}
				$request['url'] = $url;*/
				$this->dateObj = $request->only(['params',
												'roleArr',
												'functionName',
												'workLogId',
												'status'												
												]
												);
					//$this->handle();
							
												
					break;

				case 'verifyOTP':
				
				$this->dateObj = $request->only(['params',
												'roleArr',
												'functionName',												
												]
												);
				//	$this->handle();
				break;
				
				case 'machineMouUpload':
				//echo "dsfsdfd";exit;
					$url = [];
					if ($request->has('imageArraySize')) {
						for ($cnt = 0; $cnt < $request['imageArraySize']; $cnt++) {
								
							$fileName = 'image'.$cnt; 		
							
							if ($request->file($fileName)->isValid()) {
							
								$fileInstance = $request->file($fileName);
								$name = $fileInstance->getClientOriginalName();
								$ext = $request->file($fileName)->getClientMimeType(); 
								//echo $ext;exit;
								$newName = uniqid().'_'.$name.'.jpg';
								$s3Path = $request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
								
								//if ($s3Path == null || !$s3Path) {
									//return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
								//}
								$url[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
								//return response()->json(['status' => 'success', 'data' => ['url' => $result], 'message' => 'Image successfully uploaded in S3']);
							}
						}
					}
					$request['url'] = $url;
					$responseData = array('code'=>200,
								  'status' =>200,
								  'message'=>'MOU images URLs'
								  );
						
					$logInfoPath = "logs/Machine/DB/Queue/logs_".date('Y-m-d').'.log';
					$this->logData($logInfoPath,$url,'DB',$responseData);
			
					$this->dateObj = $request->only(['functionName',	
													'url',
													'org_id',
													'params',
													'machineMOU'													
													]);					
					
					$this->handle();
					break;					
				
				
				case 'machineShifting':
					$this->dateObj = $request->only(['params',
												'roleArr',
												'functionName'
												]
												);
					//$this->handle();
					break;
					
				case 'releaseOperator':
					$this->dateObj = $request->only(['params',
												'roleArr',
												'functionName'
												]
												);
					//$this->handle();
					break;
				
				case 'assignOperator':
					$this->dateObj = $request->only(['params',
												'roleArr',
												'functionName'
												]
												);
					//$this->handle();
					break;
					
		}
			
    }
 
    /**
	*
	*
	*
	*/
	public function handle() {		
		
		$path = "logs/Structure/Queue/logs_".date('Y-m-d').'.log';
		//echo "ddasd";exit;
		$logInfoPath = "logs/".$this->dateObj['params']['modelName']."/DB/Queue/logs_".date('Y-m-d').'.log';
		$errorPath = "logs/".$this->dateObj['params']['modelName']."/Error/Queue/logs_".date('Y-m-d').'.log';
		
		$functionName = $this->dateObj['functionName'];
		
		switch ($functionName) {
			
			case 'createStructure':			
				$this->sendSSNotification($this->dateObj);

				break;
			
			case 'saveStructurePreparedData':
			
				$infoData['requestData'] = $this->dateObj['structurePreparationId'];
				
				$this->connectTenantDatabase($this->dateObj['params']['org_id']);
				
				$strDataPr =  StructurePreparation::find($this->dateObj['structurePreparationId']);
				
				$infoData['requestData'] = $strDataPr;
				
				if ($strDataPr) {
					
					$strDataPr->preparaion_structure_images = $this->dateObj['urls'];
					try {
						$strDataPr->save();
						
						$responseData = array('code'=>200,
								  'status' =>200,
								  'message'=>'Structure prepared successfully'
								  );
								  
						$this->logData($logInfoPath,$this->dateObj,'DB',$responseData);
			
						
						$this->sendSSNotification($this->dateObj);

						
					} catch (Exception $e){
					
						$error = array('status' =>400,
										'message' => 'Some error has occured.Please try again',							
										'code' => 400);	
						$infoData['requestData'] = $this->dateObj;						
						$this->logData($errorPath,$this->dateObj,'Error',$responseData);							
					}	
				
				}
				break;
				
			case 'closeStructure':
						$this->connectTenantDatabase($this->dateObj['params']['org_id']);
				
						$strData =  Structure::where(['_id'=>$this->dateObj['structure_id']])
							->first();
						if ($this->dateObj['structureStatus'] == '120') {
								
							$strData->partially_reason = isset($this->dateObj['params']['reason']) ? $this->dateObj['params']['reason'] : '';			
						
							$strData->structurecompleted_images = $this->dateObj['urls'];

						}

						if ($this->dateObj['structureStatus'] == '121') {
							 
							$strData->certificates = $this->dateObj['urls'];
						}
						
						if ($this->dateObj['structureStatus'] == '123') {
							 
							$strData->partially_closed_images = $this->dateObj['urls'];
						}
						
						try {
							$strData->save();
							
							$responseData = array( 'code'=>200,
									   'status' =>'success',
									   'message'=> 'Machine mou  saved successfully');				
						
							$this->logData($logInfoPath,$this->dateObj,'DB',$responseData);						
							$this->sendSSNotification($this->dateObj);
						
							} catch (Exception $e){
							
								$error = array('status' =>400,
												'message' => 'Some error has occured.Please try again',							
												'code' => 400);	
								$this->logData($errorPath,$this->dateObj,'Error',$error);
						
							}
						
					break;
					
			case 'machineMou':
				
				try {
					
					$this->connectTenantDatabase($this->dateObj['params']['org_id']);
				
					
					$data = MachineMou::where('_id', $this->dateObj['mouId'])->update(array('mou_details.MOU_images' => $this->dateObj['accountImageUrl']
					));

					$responseData = array( 'code'=>200,
									   'status' =>'success',
									   'message'=> 'Machine mou  saved successfully');				
						
					$this->logData($logInfoPath,$this->dateObj,'DB',$responseData);
			
					/*if ( $this->dateObj['muCnt'] == 0 ) {	
						$this->createOpratorUser();
					}*/
					
					$this->sendSSNotification($this->dateObj);
				
					} catch (Exception $e){
					
						$error = array('status' =>400,
										'message' => 'Some error has occured.Please try again',							
										'code' => 400);	
						$this->logData($errorPath,$this->dateObj,'Error',$error);
						
					}
				
				break;
				
				case 'MOUTerminateDeployed':				
					$this->sendSSNotification($this->dateObj);
					break;

				case 'statusChange':				
					$this->sendSSNotification($this->dateObj);						
					break;
					
				case 'machineDeployed':
					$this->sendSSNotification($this->dateObj);						
					break;

				case 'machineWorkingDetails':
				
					$this->connectTenantDatabase($this->dateObj['params']['org_id']);
				
					$workLog = \App\MachineDailyWorkRecord::find($this->dateObj['workLogId']);
					if ($this->dateObj['status'] == 'start' || $this->dateObj['status'] == 'stop') {								
						$workLog->meter_reading_image = $this->dateObj['url'];	
					}
					
					try {
						$workLog->save();
						
						$responseData = array( 'code'=>200,
									   'status' =>'success',
									   'message'=> 'Machine work details saved successfully');				
						
						$this->logData($logInfoPath,$this->dateObj,'DB',$responseData);
			
						$this->sendSSNotification($this->dateObj);
					} catch (Exception $e){
					
						$error = array('status' =>400,
										'message' => 'Some error has occured.Please try again',							
										'code' => 400);	
						$this->logData($errorPath,$this->dateObj,'Error',$error);
					
					}	
		

											
					break;
					
				case 'verifyOTP':
				
					$this->sendSSNotification($this->dateObj);						
					break;
					
				case 'machineMouUpload':
				
					$this->connectTenantDatabase($this->dateObj['org_id']);				
					$machineMOU = MachineMou::find($this->dateObj['machineMOU']);
					if ($machineMOU) {								
						$machineMOU->mou_images = $this->dateObj['url'];	
					}
					
					try {
						$machineMOU->save();
						
						$responseData = array( 'code'=>200,
									   'status' =>'success',
									   'message'=> 'MOU images uploaded successfully');				
						
						$this->logData($logInfoPath,$this->dateObj,'DB',$responseData);
			
						
					} catch (Exception $e){
					
						$error = array('status' =>400,
										'message' => 'Some error has occured.Please try again',							
										'code' => 400);
										
						$this->logData($errorPath,$this->dateObj,'Error',$error);
				
					}	
		
					break;

				case 'machineShifting':
				
					$this->sendSSNotification($this->dateObj);						
					break;
					
				case 'releaseOperator':				
					$this->sendSSNotification($this->dateObj);
					break;

				case 'assignOperator':
					$this->sendSSNotification($this->dateObj);
					break;

				
		}//switch case ends here
	}

	 public function connectTenantDatabase($orgId)
    {
		DB::setDefaultConnection('mongodb');
        $organisation = Organisation::find($orgId);
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
	*
	*
	*
	*/
	public function sendSSNotification($request) {
	
		$logInfoPath = "logs/".$request['params']['modelName']."/DB/Notification/logs_".date('Y-m-d').'.log';
		$errorPath = "logs/".$request['params']['modelName']."/Error/Notification/logs_".date('Y-m-d').'.log';

		$stateId = $request['params']['stateId'];
		
		/*if ($user != null) {
			
			$params['userName'] = $user->name;
			
		}*/	
		
		$districtId = $request['params']['districtId'];
		$talukaId = $request['params']['talukaId'];
		$villageId = isset($request['params']['villageId']) ? $request['params']['villageId'] : '';		
		
		//loop for role
		foreach ($request['roleArr'] as  $roleCode) {
			
			DB::setDefaultConnection('mongodb');
			$roleData = \App\Role::where(['role_code'=>$roleCode, 'org_id'=>$request['params']['org_id']])->first();
			
			if (!$roleData) {
				$responseData = array( 'code'=>400,
									   'status' =>'error',
									   'roleCode'=>$roleCode,									  
									   'structureCode'=>$request['params']['code'],
									   'message'=> 'Role missing in role collection');									
				
				$this->logData($errorPath,$request,'Error',$responseData);
				
				return true;
					
			}
			//echo $roleData->_id;exit;
			$stArry = array($stateId);			
			$query = \App\User::where(['orgDetails.role_id' => $roleData->_id])			
			 ->whereIn('orgDetails.location.state',$stArry);
			//projectId
			if ($roleCode == '110') {
				$dtArry = array($districtId);	
				$query->whereIn('orgDetails.location.district',$dtArry);
			}
			// 'location.district' => $districtId]);
			
			if ($roleCode == '111') {
				$talukaIds  = array($talukaId);
				$query->whereIn('orgDetails.location.taluka',$talukaIds);
			}

			if ($roleCode == '114') {
				$villageIds  = array($villageId);
				$query->whereIn('orgDetails.location.village',$villageIds);
			}
			
			$userDetails = $query->select('firebase_id','name','phone')
										->get()->toArray();
			
		//	print_r($userDetails);exit;
			foreach($userDetails as $userData) {
				//echo "rwerewr".$userData['firebase_id'];exit;
				if (!isset($userData['firebase_id'])) {
					
					$responseData = array( 'code'=>400,
									   'status' =>'error',
									   'roleCode'=>$roleCode,									  
									   'structureCode'=>$request['params']['code'],
									   'message' => 'firebase_id missing in User collection');									
				
				$this->logData($errorPath,$request['params'],'Error',$responseData);
					//echo "ewrwerwr";exit;
					return true;				
				}//echo "r gt esrtert";exit;
				$dataD = $this->sendPushNotification(
					$request,
					$request['params']['request_type'],
					$userData['firebase_id'],
					//'cjmWBIhPrTc:APA91bF4VWdfipS17kFcCOEALO-T27rTF03PT2ijYkusVOSNj3hzFMUNI6Q2OP8wsl25g4IFbzBvr0xnMfTSCnkd7JdcdxmDR_48oeWNfRFdRMpfeP9jCKpOi1ECLJWVO8Ya9IwiQ5S2',
					[ 
						'phone'=> $userData['phone'],
						//'9028724868',
						'update_status' => $request['params']['update_status'],						
						'rolename' => $roleData->display_name,
						'code'=> $request['params']['code'],
						'params'=>$request['params']
					],
				   $request['params']['org_id']
				);
				if ($dataD) { 
				
					$responseData = array('code'=>200,
										  'status' =>'success',
										   'roleName'=>$roleData->display_name,
										   'roleUserCode'=>$userData['_id'],
										   'structureCode'=>$request['params']['code']);									
					
					$this->logData($logInfoPath,$request['params'],'DB',$responseData);
				
				} else {
					$responseData = array( 'code'=>400,
										   'status' =>'error',
										   'roleName'=>$roleData->display_name,
										   'roleUserCode'=>$userData['_id'],
										   'structureCode'=>$request['params']['code']);									
					
					$this->logData($logInfoPath,$request['params'],'DB',$responseData);
				
				}
			}	
		}
		return true;
	}
	
	public function sendPushNotification($request ,$type, $firebaseId, $params = [],$orgId)
    {//echo "rt ertet";exit;
		//$userdetails = $this->request->user();
		$model = "Planner"; 
        $database=$this->connectTenantDatabase($orgId);
        if ($database === null) 
        {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        $notificationSchema = null;
		$service = '';
		$parameters = [];
		
		try {
			switch ($type) {
				
				case self::NOTIFICATION_STRUCTURE_APPROVED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_APPROVED)->first();
										
					$strMsg = str_replace('#StructureCode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#Taluka', $request['talukaName'], $strMsg); 
					$notificationSchema['message.en'] = str_replace('#Village', $request['villageName'], $strMsg); 

					//echo $notificationSchema['message.en'];exit;
					//$notificationSchema['message.en'] = $userdetails->name ."(".$params['rolename'] .") " .$notificationSchema['message.en'];
					$model = "structure";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					break;
				
				case self::NOTIFICATION_STRUCTURE_NONCOMPLAINT:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_NONCOMPLAINT)->first();
					
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "structure";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					break;
					
				
				case self::NOTIFICATION_STRUCTURE_PREPARED:
					
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_PREPARED)->first();
					
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 					
					$notificationSchema['message.en'] = $strMsg;
					$model = "structure";
					
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
					$model = "structure";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					break;
				
				case self::NOTIFICATION_STRUCTURE_COMPLETED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_COMPLETED)->first();
					
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "structure";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;

				case self::NOTIFICATION_STRUCTURE_CLOSED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_CLOSED)->first();
					
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "structure";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;	 	
					
				case self::NOTIFICATION_STRUCTURE_PARTIALLY_CLOSED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_STRUCTURE_PARTIALLY_CLOSED)->first();
					
					$strMsg = str_replace('#Code', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "structure";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;
					
					
				case self::NOTIFICATION_MACHINE_MOU:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_MOU)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "machine";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;						
				
				case self::NOTIFICATION_MACHINE_MOU_TERMINATED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_MOU_TERMINATED)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					//$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg); 
					$notificationSchema['message.en'] = $strMsg;
					$model = "machine";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;

				case self::NOTIFICATION_MACHINE_AVAILABLE:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_AVAILABLE)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#TalukaName', $params['params']['talukaName'], $strMsg); 
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "machine";
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
					$model = "machine";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;
					
				case self::NOTIFICATION_MACHINE_DEPLOYED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_DEPLOYED)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#StructureCode', $params['params']['struture_code'], $strMsg);
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "machine";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break; 

				case self::NOTIFICATION_MACHINE_STATUS:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_STATUS)->first();
					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#status', $params['params']['status'].'ed', $strMsg);
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "machine";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break; 

				case self::NOTIFICATION_MACHINE_HALTED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_HALTED)->first();
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#Reason', $params['params']['reason'], $strMsg);
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "machine";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;
					
				case self::NOTIFICATION_OPRATOR_LOGIN:
					
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_OPRATOR_LOGIN)->first();
					
					$strMsg = str_replace('#name', $params['params']['userName'], $notificationSchema['message.en']);
					$strMsg = str_replace('#machinecode', $params['code'], $strMsg);
										
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "machine";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;
							
						
				case self::NOTIFICATION_MACHINE_FREE:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_MACHINE_FREE)->first();					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "machine";
					$service = $notificationSchema->service . '/' . $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break; 
				
				case self::NOTIFICATION_OPERATOR_ASSIGNED:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_OPERATOR_ASSIGNED)->first();					
					$strMsg = str_replace('#machinecode', $params['code'], $notificationSchema['message.en']); 
					$strMsg = str_replace('#name', $params['params']['userName'], $strMsg);
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "machine";
					$service = $notificationSchema->service . '/'. $params['phone'];
					$parameters = ['update_status' => $params['update_status']];
					
					break;
					
				case self::NOTIFICATION_OPERATOR_RELEASE:
				
					$notificationSchema = NotificationSchema::where('type', self::NOTIFICATION_OPERATOR_RELEASE)->first();					
					$strMsg = str_replace('#name', $params['params']['userName'], $notificationSchema['message.en']);
					$strMsg = str_replace('#machinecode', $params['code'], $strMsg);
					
					$notificationSchema['message.en'] = $strMsg;
					$model = "machine";
					$service = $notificationSchema->service . '/'. $params['phone'];
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
			
		} catch(Exception $e) {
			$response_data = array('status' =>'200','message'=>'error','data' => $e);
			return response()->json($response_data,200); 
		} 
		return true;
	}
	
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
    
	public function createOpratorUser() {
		
		DB::setDefaultConnection('mongodb');
		
		$logInfoPath = "logs/".$this->dateObj['params']['modelName']."/DB/logs_".date('Y-m-d').'.log';
		$errorPath = "logs/".$this->dateObj['params']['modelName']."/Error/logs_".date('Y-m-d').'.log';

		//print_r($this->dateObj['operator_details']->first_name);exit;
		$name = (isset($this->dateObj['operator_details']->first_name) ? $this->dateObj['operator_details']->first_name : '' );
		$lname = (isset($this->dateObj['operator_details']->last_name) ? $this->dateObj['operator_details']->last_name : '' );
		$phoneNumber = $this->dateObj['machine']->machine_mobile_number;
		$bjUser = User::where(['phone'=>$phoneNumber])
			->whereNull('org_id')
			->first();
		
		//var_dump($bjUser);exit;
		if ($bjUser) {


		} else  {	
			$bjUser = new User;
		}
		
		$orgId = $this->dateObj['params']['org_id'];		
		$database = $this->connectTenantDatabase($orgId);		
		$machineData = Machine::find($this->dateObj['provider_information']->machine_id);	
		
		$password = app('hash')->make($phoneNumber);
		$bjUser->name = $name.' '.$lname;
		$bjUser->email = 'test@gmail.com';
		$bjUser->password =  $password;
		$bjUser->phone = $phoneNumber ;
		
		$bjUser->approve_status = 'approved';
		//$bjUser->dob = '';
		$bjUser->org_id = $orgId;
		$bjUser->profile_pic = '';
		$projectId = $this->dateObj['params']['projectId'];
		$bjUser->project_id = array($projectId);

		//getoprator role from role collection
		DB::setDefaultConnection('mongodb');
		
		$roleData = \App\Role::where(['role_code'=> '113', 
								'is_deleted' => 0,
								'org_id'=> $orgId])
								->select('_id','role_code')
								->get()
								->toArray();							
								
		if (count($roleData) >0) {			
			$bjUser->role_id = $roleData[0]['_id'];
		} else  {
			
		}
		$orgArray = [
			'org_id'=>$orgId,
			'project_id'=>$projectId,
			'role_id'=>$roleData[0]['_id'],
			'address'=>'',
			'leave_type'=>'',
			'lat'=>'',
			'long'=>'',
			'approver_user_id'=>'',
			];
		$bjUser->orgDetails = $orgArray ;		
		$location =  new \stdClass;
 
		$location->state = array($machineData->state_id);
		$location->district = array($machineData->district_id);
		$location->taluka = array($machineData->taluka_id);

		$bjUser->location = $location;
		
		try {
			
			$bjUser->save();	
			
			$responseData = array('code'=>200,
								  'status' =>200,
								  'message'=>'Operator created successfully'
								  );
								  
			$this->logData($logInfoPath,$bjUser,'DB',$responseData);
			
			return true;
			
		} catch (Exception $e) {
					
			$response_data = array('code'=>300,'status' =>300,'message'=>$e);
			$this->logData($errorPath,$this->dateObj,'DB',$response_data);
			return true;
			//return response()->json($response_data,200);
		}
	}
}
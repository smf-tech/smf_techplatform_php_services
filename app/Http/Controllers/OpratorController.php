<?php

namespace App\Http\Controllers;

use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
/*
use App\Structure;
use App\StructureDepartment;
use App\StructureSubDepartment;
use App\StructureType;
use App\MasterData;
use App\StructurePreparation;
use App\StructureLog;
use App\Machine; */
use App\MachineMou;
use App\MachineDailyWorkRecord;
use App\Machine;
use App\MachineLog;
use App\Jobs\DataQueue;


date_default_timezone_set('Asia/Kolkata'); 
class OpratorController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
		$this->logInfoPah = "logs/Oprator/DB/logs_".date('Y-m-d').'.log';
		$this->errorPath = "logs/Oprator/Error/logs_".date('Y-m-d').'.log';

    }
	
	//get Feedlist from DB
	public function machineWorkingDetails(Request $request) {	
		
		
		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$org_id =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();	
		$this->request->user_id = $user->_id;
		$this->logData($this->logInfoPah, $this->request->all(),'DB');		
		
		/*if ($request->isMethod('post')) {
			
			
		} else {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'Method missing.'],
									 403);
		

		}*/			

		$database = $this->connectTenantDatabase($request,$org_id);
		
		//$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		//$result = $this->request->all();
		if (!$this->request->has('formData')) {
			
			$error = array('status' =>400,
							'msg' => 'Form Data field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}	
		
		$url = [];
		if ($this->request->has('imageArraySize')) {
			for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {
					
				$fileName = 'image'.$cnt; 		
				
				if ($this->request->file($fileName)->isValid()) {
				
					$fileInstance = $this->request->file($fileName);
					$name = $fileInstance->getClientOriginalName();
					$ext = $this->request->file($fileName)->getClientMimeType(); 
					//echo $ext;exit;
					$newName = uniqid().'_'.$name.'.jpg';
					$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
					
					//if ($s3Path == null || !$s3Path) {
						//return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
					//}
					$url[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
					//return response()->json(['status' => 'success', 'data' => ['url' => $result], 'message' => 'Image successfully uploaded in S3']);
				}
			}
		}
		//echo "<pre>";	print_r($url);exit;
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		$errorLog = 0;
		
		if (!isset($requestJson->machine_id)) {
			
			$error = array('status' =>400,
							'msg' => 'Machine Id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
			
		}
		//check machine workTime
		$recordCnt = MachineDailyWorkRecord::where(['workTime' => $requestJson->workTime, 
										'status_code' => $requestJson->status_code,
										'status' => $requestJson->status,
										'machine_id'=>$requestJson->machine_id])->count();										
		//$recordCnt = 0;								
		if ($recordCnt > 0) {
			
			$success = array('status' =>200,								
								'msg' => 'Machine work  log saved successfully',							
								'code' => 200,
								'id'=>$requestJson->_id
								);				
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
			
			return response()->json($success);	
			
		}	
		$machineWorkObj = new MachineDailyWorkRecord;
		
		$machineWorkObj->machine_id = $requestJson->machine_id;
		$machineWorkObj->status = isset($requestJson->status) ? $requestJson->status : '';
		
		$machineWorkObj->workTime = isset($requestJson->workTime) ? $requestJson->workTime : '';
			
		$machineWorkObj->workDate = new \MongoDB\BSON\UTCDateTime( $requestJson->workTime);
		
		//$machineWorkObj->misWorkDate =  date('F j, Y h:m:s A ', ($requestJson->workTime)/1000);
		$machineWorkObj->lat = isset($requestJson->lat) ? (float)$requestJson->lat : 0;
		
		$machineWorkObj->long = isset($requestJson->long) ? (float)$requestJson->long : 0;
		$machineWorkObj->hours = isset($requestJson->hours) ? $requestJson->hours : '';
		$machineWorkObj->total_hours =  isset($requestJson->totalHours) ? $requestJson->totalHours : '';
				
		$machineWorkObj->mis_status = 'pending';
		$machineWorkObj->status_code = $requestJson->status_code;
		$machineWorkObj->project_id = $project_id;
		
		$machineWorkObj->meter_reading = isset($requestJson->meter_reading) ? $requestJson->meter_reading : '';
		
		$machineWorkObj->reason_id =  isset($requestJson->reason_id) ? $requestJson->reason_id : '';
		$machineWorkObj->user_id = $user->_id;
		
		if ($requestJson->status == 'start' || $requestJson->status == 'stop') {								
			$machineWorkObj->meter_reading_image = $url;	
		}
		
		//$machineWorkObj->is_valid = false;			
		try {			
				$machineWorkObj->save();
				
				//update machine status						
				$machineData = Machine::where('_id',$requestJson->machine_id)->first();
				$machineCode = '';
				if (($machineData)) {
					
					$codeArray = array('107','108','109','110','111','112','113');
					
					if (in_array($machineData->status_code, $codeArray)) {					
					//if ($machineData->status_code != '105' && $machineData->status_code != '114' && $machineData->status_code != '115') {
						
							
						$machineCode = $machineData->code;
						$status = $requestJson->status;
						$status_code = $requestJson->status_code;
						
						$params['request_type'] =  self::NOTIFICATION_MACHINE_STATUS;
						$params['update_status'] = 'Machine Free';
						
						if ($requestJson->status_code == '111') {
							
							//if ($requestJson->machine_id != '5df8c9d9db21057ed2407e12') {
							
							$params['request_type'] =  self::NOTIFICATION_MACHINE_HALTED;
							$params['update_status'] = 'Machine Halted';
							
							
							$masterData = \App\MasterData::find($requestJson->reason_id);
							//echo $masterData->value;exit;
							/*$params['org_id'] = $org_id;										
							$params['code'] = $machineData->machine_code;						
							$params['stateId'] = $machineData->state_id;
							$params['districtId'] = $machineData->district_id;
							$params['talukaId'] = $machineData->taluka_id;*/
							$params['reason'] = $masterData->value;
							//}
							//$params['modelName'] = 'Oprator';	
							//$statusCode = \App\StatusCode::where(['statusCode'=>'111', 'type'=>'machine'])->first();
														
						}	
						
						$statusCode = \App\StatusCode::where(['statusCode'=>$status_code, 'type'=>'machine'])->first();
							
						
						if ($requestJson->status_code == '112') {
							
							$statusCode = \App\StatusCode::where(['statusCode'=>'108', 'type'=>'machine'])->first();
							
							//$status = $statusCode['status_name'];
							//$status_code = $statusCode['statusCode'];
						}
						
						$params['status'] = $requestJson->status;
						
						if ($machineData->status_code == '113' && $requestJson->status_code == '112') {

							$params['status'] = 'resum';

						}
						
						if( strtolower($requestJson->status) == 'pause') {
							$params['status'] = 'paus';
						}
						
						if( strtolower($requestJson->status) == 'stop') {
							$params['status'] = 'stopp';
						}
						$machineData->status = $statusCode['status_name'];
						$machineData->status_code = $statusCode['statusCode'];
						$machineData->lat = isset($requestJson->lat) ? (float) $requestJson->lat : 0;
		
						$machineData->long = isset($requestJson->long) ? (float)$requestJson->long : 0;

						$machineData->save();	

					//if ($requestJson->machine_id != '5df8c9d9db21057ed2407e12') {
								
						//send notification
						$roleArr = array('110','111','112','115');
						
						$params['org_id'] = $org_id;										
						$params['code'] = $machineData->machine_code;						
						$params['stateId'] = $machineData->state_id;
						$params['districtId'] = $machineData->district_id;
						$params['talukaId'] = $machineData->taluka_id;
						
						$params['modelName'] = 'Operator';
						//$this->sendSSNotification($this->request,$params, $roleArr);
						$this->request['functionName'] = __FUNCTION__;
						$this->request['params'] =  $params;
						$this->request['roleArr'] = $roleArr;
						$this->request['workLogId'] = $machineWorkObj->id;
						$this->request['status'] = $requestJson->status;
						
						dispatch((new DataQueue($this->request)));
						
					//}
						
					}
					
				}				
				//insert into machine log
				$machineLog =  new MachineLog;		
				$machineLog['code'] = $machineCode;
				$machineLog['action_title'] = 'Daily work log';
				$machineLog['machine_id'] = $requestJson->machine_id;
				
				//$machineLog['action_by'] = $this->request->user_id;
				$machineLog['status'] = $requestJson->status;			
				$machineLog['status_code'] = $requestJson->status_code;			
				$machineLog['user_id'] = $user->_id;

				$machineLog->save();	
				
				$success = array('status' =>200,								
								'msg' => 'Machine work  log saved successfully',
								'id'=>$requestJson->_id,								
								'code' => 200
								);				
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);				
				
			} catch (Exception $e){
				
				$errorLog++;
				$error = array('status' =>400,
								'msg' => 'Some error has occured.Please try again',							
								'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);				
			}			
		//}	
		
		if ($errorLog > 0) {

			$error = array('status' =>400,
								'msg' => 'Some error has occured.Please try again',							
								'code' => 400);						
				
			return response()->json($error);
		}
		$success = array('status' =>200,								
						'msg' => 'Machine work  log saved successfully',
						'id'=>$requestJson->_id,	
						'code' => 200
						);				
		return response()->json($success);				
		
	}
	
	//get Feedlist from DB
	public function machineNonUtilisation(Request $request) {	
		
		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$org_id =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();	
		$this->request->user_id = $user->_id;
		$this->logData($this->logInfoPah, $this->request->all(),'DB');		
		
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}	
		
		if (!$this->request->has('machineId')) {
			
			$error = array('status' =>400,
							'msg' => 'machine Id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}

		if (!$this->request->has('selectedReasonId')) {
			
			$error = array('status' =>400,
							'msg' => 'Reason Id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}

		$machineWorkObj = new MachineDailyWorkRecord;
		
		$machineWorkObj->machine_id = $this->request->machineId;		
		$machineWorkObj->reason_id = isset($this->request->selectedReasonId) ? $this->request->selectedReasonId : '';
		$machineWorkObj->reason_description = ($this->request->has('otherReason')) ? $this->request->otherReason : '';
		
		$machineWorkObj->status = 'Halted';
		$machineWorkObj->status_code = '119';
		$machineWorkObj->project_id = $project_id;
		
		$errorLog = 0;
		try {
			
				$machineWorkObj->save();
				
				//update machine status						
				$machineData = Machine::where('_id',$this->request->machineId)->first();
				//print_r($machineData);exit;
				//$machineCode = '';
				if (($machineData)) {
					
					$codeArray = array('107','108','109','110','111','112','113');
					
					if (in_array($machineData->status_code, $codeArray)) {
						
						$status = 'Halted';
						$status_code = '119';
						$machineData->save();
					}
				}
				$params['request_type'] =  self::NOTIFICATION_MACHINE_HALTED;
				$params['update_status'] = 'Machine Halted';
				
				//send notification
				$roleArr = array('111','112','114');
				//echo $machineData->district_id;exit;
				//get master string
				$masterData = \App\MasterData::find($this->request->selectedReasonId);
				//echo $masterData->value;exit;
				$params['org_id'] = $org_id;										
				$params['code'] = $machineData->machine_code;						
				$params['stateId'] = $machineData->state_id;
				$params['districtId'] = $machineData->district_id;
				$params['talukaId'] = $machineData->taluka_id;
				$params['reason'] = $masterData->value;
				$params['modelName'] = 'Oprator';
				$this->sendSSNotification($this->request,$params, $roleArr);

				
				$success = array('status' =>200,								
								'msg' => 'Machine work  log saved successfully',							
								'code' => 200
								);				
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);				
				
			} catch (Exception $e){
				
				$errorLog++;
				$error = array('status' =>400,
								'msg' => 'Some error has occured.Please try again',							
								'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);				
			}			
		//}	
		
		if ($errorLog > 0) {

			$error = array('status' =>400,
								'msg' => 'Some error has occured.Please try again',							
								'code' => 400);						
				
			return response()->json($error);
		}
		$success = array('status' =>200,								
						'msg' => 'Machine work  log saved successfully',							
						'code' => 200
						);				
		return response()->json($success);				
			
		
	}
	
	public function getMachineData(Request $request) {
		
		
		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$org_id =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();	
		$this->request->user_id = $user->_id;
		$this->logData($this->logInfoPah, $this->request->all(),'DB');		
		
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$data = \App\OperatorMachineMapping::where(['operator_id'=>$user->id])
		->with('machineData')
		//->with('userData')
		->get()->toArray();
		
		/*$data = MachineMou::where('machine_mobile_number',$user->phone)
							->with('machineData')
							//->select('_id','machine_code','provider_information.machine_id as machine_id')
							->get()->toArray();
							
		$moMappingCnt = \App\OperatorMachineMapping::where(['machine_id'=>$data['provider_information']['machine_id'],
													'operator_id'=>$userData->id])
													->count();*/
					
		if (count($data) == 0) {
			return response()->json(['status' => 400, 
									'code' => 400,									 
									 'message' => 'No data available.']
									 );
		}			
		//echo '<pre>';print_r($data);exit;					
		$resultData['machine_id'] = $data[0]['machine_data'][0]['_id'];
		$resultData['machine_code'] = $data[0]['machine_data'][0]['machine_code'];
		
		$resultData['hour_of_day'] = 19;//minutes for day
		$resultData['minute_of_hour'] = 15; // minutes for day
		$resultData['minute_of_pause'] = 2; //for pause duration 
		
		
		$nonutilisationTypeResult = [];
		$nonutilisationTypeHi = \App\MasterData::where('type','machine_nonutilisation')
								->where(['is_active'=>1, 'lang_code'=> 'hindi'])
								->select('_id','value')
								->get();
		$nonutilisationTypeResult['hi']	= 	$nonutilisationTypeHi;
		
		$nonutilisationTypeMr = \App\MasterData::where('type','machine_nonutilisation')
								->where(['is_active'=>1, 'lang_code'=> 'marathi'])
								->select('_id','value')
								->get();
		$nonutilisationTypeResult['mr']	= 	$nonutilisationTypeMr;
		
		$nonutilisationTypeEn = \App\MasterData::where('type','machine_nonutilisation')
								->where(['is_active'=>1, 'lang_code'=> 'en'])
								->select('_id','value')
								->get();
		$nonutilisationTypeResult['en']	= 	$nonutilisationTypeEn;
		
		
		/*if ($nonutilisationTypeData) {
			$accountType['form'] = 'machine_nonutilisation';
			$accountType['field'] = 'machine_nonutilisation';
			$accountType['data'] = $accountTypeData;

			array_push($resultData,$accountType);
			unset($accountType);

		}	*/				
		$resultData['nonutilisationTypeData'] = $nonutilisationTypeResult;
		
		$success = array('status' =>200,								
								'msg' => 'Machine details',
								'data'=>$resultData,								
								'code' => 200
								);

		return response()->json($success);
		
	}

	public function getMachineWorkLogData(Request $request) {
		
		
		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$org_id =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();	
		$this->request->user_id = $user->_id;
		$this->logData($this->logInfoPah, $this->request->all(),'DB');		
		
		
		if (!$this->request->has('machineId')) {
			
			$error = array('status' =>400,
							'msg' => 'Machine id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}
		
		if (!$this->request->has('startDate')) {
			
			$error = array('status' =>400,
							'msg' => 'Start Date field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}
		
		if (!$this->request->has('endDate')) {
			
			$error = array('status' =>400,
							'msg' => 'End Date field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}
		
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		//echo $this->request['machine_id'];exit;
		$startDate = $this->request['startDate'];
		$endDate = $this->request['endDate'];
		
		$start_date = Carbon::createFromTimestamp($startDate/1000 );
		$startdate = new Carbon($start_date);
       // $startdate->timezone = 'Asia/Kolkata';
		
		$endDate = Carbon::createFromTimestamp($endDate/1000 );
		$endDate = new Carbon($endDate);
       // $endDate->timezone = 'Asia/Kolkata';
		
        $start_date_str = new \MongoDB\BSON\UTCDateTime($startdate->startOfDay());
		$end_date_str = new \MongoDB\BSON\UTCDateTime($endDate->endOfDay());
		
		$machineData = \App\MachineDailyWorkRecord::where(['machine_id'=>$this->request['machineId']])
			->where('workDate',">=",$start_date_str)
			->where('workDate',"<=",$end_date_str)
			->orderBy('workDate','ASC')
			//->where('status_code','110')
			->get();
			
		//
		
		//echo '<pre>';print_r($machineData->toArray());exit;
		$startCnt = 0;
		$tmpData = [];
		$finalData = [];
		$cnt = 0;
		$arCnt =1;
		$machineDataCngt = count($machineData->toArray());
		$totalSum = 0;
		
		foreach ($machineData as $data) {
			
			$date =  Carbon::createFromTimestamp($data['workTime']/1000)->toDateString();			
			
			//$date = $date->format('d-m-Y');
			//$date->timezone = 'Asia/Kolkata';
			if ($startCnt == 0) {
				$tmpData['startDate'] = $date;
				$tmpData['totalHrsCunt'] = 0;
				$startCnt++;				
			}
			//echo $data['status_code'].'------>';
			
			if ($tmpData['startDate'] == $date) {
			
				if ($data['status_code'] == '112' && !isset($tmpData['startReading'])) {
					$tmpData['startReading'] = $data['meter_reading'];
					//$tmpData['start_meter_reading_image'] = $data['meter_reading_image'];
					$finalData[$cnt]['startDate'] = $tmpData['startDate'];
					$finalData[$cnt]['startReading'] = $tmpData['startReading'];
					$finalData[$cnt]['workDate'] = $data['workTime'];
					$finalData[$cnt]['machineId'] = $data['machine_id'];
					$finalData[$cnt]['start_id'] = $data['_id'];
					
					$finalData[$cnt]['startMeterReadingImage'] = '';
					if (isset($data['meter_reading_image'][0])) {
						$finalData[$cnt]['startMeterReadingImage'] = $data['meter_reading_image'][0];
					}
					$tmpData['totalHrsCunt'] = 0;
					
				}
				
				if ($data['status_code'] == '110') {
					
					
					
					$tmpData['endReading'] = $data['meter_reading'];
					$finalData[$cnt]['endMeterReadingImage'] = '';
					if (isset($data['meter_reading_image'][0])) {
						//$tmpData['meter_reading_image'] = $data['meter_reading_image'][0];
						$finalData[$cnt]['endMeterReadingImage'] = $data['meter_reading_image'][0];
					}
					$finalData[$cnt]['endReading'] = $tmpData['endReading'];
					$finalData[$cnt]['end_id'] = $data['_id'];
					
					//$totalSum = $totalSum + $tmpData['totalHrsCunt'];
					$tmpData['totalHrsCunt'] = 	$tmpData['totalHrsCunt'] + $data['total_hours'];
					
					$totalSum = $totalSum + $data['total_hours'];
					
					//echo $tmpData['totalHrsCunt'].'<br>';
					$init = $tmpData['totalHrsCunt'] ;
					
					
						$hours = floor($init / 3600);
						$minutes = floor(($init / 60) % 60);
						$seconds = $init % 60;
						$timeStr = '';
						
						if (strlen($hours) == 1) {
							//$timeStr = $hours.' hrs';
							$hours = '0'.$hours;
						}
						if (strlen($minutes) == 1) {
							$minutes = '0'.$minutes;
						}
						$finalData[$cnt]['totalHrsCunt'] = 	"$hours:$minutes"; //echo "fsdfsdf";
					if ($machineDataCngt == $arCnt) {
						
						/*if ($seconds > 0) {
							$timeStr = $timeStr.' '.$seconds.' sec';
						}*/						
						$finalData[$cnt]['totalHrsCunt'] = 	"$hours:$minutes";
						//"$hours:$minutes:$seconds";
					}
					
									
				}
			} else {
				//echo '<pre>';print_r($finalData);exit;	
				$cnt++;
				$tmpData = array();
				$tmpData['startDate'] = $date;
				
			if ($data['status_code'] == '110') {
					
				$init = $data['total_hours'] ;
			    // echo $init;
				$hours = floor($init / 3600);
				$minutes = floor(($init / 60) % 60);
				$seconds = $init % 60;
				$timeStr = '';
				//$hours = 12;
				
				if (strlen($hours) == 1) {
					//$timeStr = $hours.' hrs';
					$hours = '0'.$hours;
				}
				
				if (strlen($minutes) == 1) {
					$minutes = '0'.$minutes;
				}

				/*if ($seconds > 0) {
					$timeStr = $timeStr.' '.$seconds.' sec';
				}*/

				$finalData[$cnt]['totalHrsCunt'] = "$hours:$minutes";
			}
				
				if ($data['status_code'] == '112' && !isset($tmpData['startReading'])) {
					
					$tmpData['startReading'] = $data['meter_reading'];
					//$tmpData['start_meter_reading_image'] = $data['meter_reading_image'];
					$finalData[$cnt]['startDate'] = $tmpData['startDate'];
					$finalData[$cnt]['startReading'] = $tmpData['startReading'];
					$finalData[$cnt]['startMeterReadingImage'] = '';
					$finalData[$cnt]['workDate'] = $data['workTime'];
					$finalData[$cnt]['machineId'] = $data['machine_id'];
					$finalData[$cnt]['start_id'] = $data['_id'];
					
					
					if (isset($data['meter_reading_image'][0])) {
						$finalData[$cnt]['startMeterReadingImage'] = $data['meter_reading_image'][0];
					}
					$tmpData['totalHrsCunt'] = 0;
					
				}			
				$startCnt = 0;			
			}
			$arCnt++;			
		}
		//echo $totalSum;
		$hours = floor($totalSum / 3600);
		$minutes = floor(($totalSum / 60) % 60);
		$seconds = $totalSum % 60;
		if (strlen($hours) == 1) {
					//$timeStr = $hours.' hrs';
					$hours = '0'.$hours;
				}
				
				if (strlen($minutes) == 1) {
					$minutes = '0'.$minutes;
				}
		//$finalData['totalWorkHrs'] = "$hours:$minutes";
		$success = array('status' =>200,								
								'msg' => 'Machine work log details ',
								'data'=>$finalData,								
								'code' => 200,
								'totalWorkHrs'=> "$hours:$minutes"
								);

		return response()->json($success);
		//print_r($finalData);exit;	;	
	}

	public function getMachineWorkLogDetails(Request $request) {
		
		
		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$org_id =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();	
		$this->request->user_id = $user->_id;
		$this->logData($this->logInfoPah, $this->request->all(),'DB');		
		
		
		if (!$this->request->has('machineId')) {
			
			$error = array('status' =>400,
							'msg' => 'Machine id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}
		
		if (!$this->request->has('workDate')) {
			
			$error = array('status' =>400,
							'msg' => 'Work Date field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}	
		
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		//echo $this->request['machine_id'];exit;
		$startDate = $this->request['workDate'];
		$endDate = $this->request['workDate'];
		
		$start_date = Carbon::createFromTimestamp($startDate/1000 );
		$startdate = new Carbon($start_date);
       // $startdate->timezone = 'Asia/Kolkata';
		
		$endDate = Carbon::createFromTimestamp($endDate/1000 );
		$endDate = new Carbon($endDate);
       // $endDate->timezone = 'Asia/Kolkata';
		
        $start_date_str = new \MongoDB\BSON\UTCDateTime($startdate->startOfDay());
		$end_date_str = new \MongoDB\BSON\UTCDateTime($endDate->endOfDay());
		
		$machineData = \App\MachineDailyWorkRecord::where(['machine_id'=>$this->request['machineId']])
			->where('workDate',">=",$start_date_str)
			->where('workDate',"<=",$end_date_str)
			//->where('status_code','110')
			->orderBy('workDate', 'ASC')
			->get();
			
		
		$resultData = [];
		//date_default_timezone_set('Asia/Kolkata'); 
	
		foreach ($machineData as $key=>$data) {
			
			//$date =  Carbon::createFromTimestamp($data['workTime']/1000)->toDateString();
			$resultData[$key]['status'] = 	ucfirst($data['status']);
			$totalSum = $data['total_hours'] ;
			$hours = floor($totalSum / 3600);
			$minutes = floor(($totalSum / 60) % 60);
			$seconds = $totalSum % 60;
			if (strlen($hours) == 1) {
				//$timeStr = $hours.' hrs';
				$hours = '0'.$hours;
			}

			if (strlen($minutes) == 1) {
				$minutes = '0'.$minutes;
			}
			
			$resultData[$key]['totalHours'] = "$hours:$minutes";
			//$resultData[$key]['workDate'] = date('m-d-Y h:m:s A ', floor($data['workTime']/1000));

			$resultData[$key]['workDate'] = date('d M Y g:i a', strtotime($data['workDate']));				
		
		}
		if (count ($resultData) > 0 ) {
			$success = array('status' =>200,								
								'msg' => 'Machine work log details ',
								'data'=>$resultData,								
								'code' => 200,
								);

			return response()->json($success);
		}
		
		$error = array('status' =>400,
							'msg' => 'No data available.',							
							'code' => 400);						
		$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
		return response()->json($error);		
	}	

	public function dataSynch(Request $request) {

		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$org_id =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();	
		$this->request->user_id = $user->_id;
		$this->logData($this->logInfoPah, $this->request->all(),'DB');		
		
		if (!$this->request->has('machie_id')) {
			
			$error = array('status' =>400,
							'msg' => 'Machine Id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}
		
		
		if (!$this->request->has('synch_date')) {
			
			$error = array('status' =>400,
							'msg' => 'Work Date field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}
		
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$machineSynch = new \App\MachineSynch;
		$machineSynch->machine = $this->request->machie_id;
		$machineSynch->synch_timestamp = $this->request->synch_date;
		$machineSynch->synch_date = new \MongoDB\BSON\UTCDateTime( $this->request->synch_date);
		
		
		try {
			$machineSynch->save();
			
			$success = array('status' =>200,								
						'msg' => 'Machine date saved successfully',							
						'code' => 200
						);
			
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
	
			return response()->json($success);				
		
			
		} catch (Exception $e){
				
			$error = array('status' =>400,
								'msg' => 'Some error has occured.Please try again',							
								'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);

			return response()->json($error);
		
		}	
		


	}	
		
		
}
<?php

/**
 * Machine Master Class
 *
 * @library         JetEngage
 *
 * @license         <add Licence here>
 * @link            http://bjsindia.org
 * @author          Reshu Bisen <rbisen@bjsindia.org>
 * @since           Oct 2, 2019
 * @copyright       2019 BJS
 * @version         1.0
 */
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use DateTimeImmutable;
use DateTime;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;
use App\Role;
use App\User; 
use App\Machine;
use App\Structure;
use App\MachineLog;
use App\StructureLog;
use App\MachineMou;
use App\StatusCode;
use App\MachineDailyWorkRecord;
use Illuminate\Support\Arr;
use App\StructureMachineMapping;
use App\Jobs\DataQueue;
use App\MachineSignOff;

date_default_timezone_set('Asia/Kolkata');
 
class MachineController extends Controller
{
    use Helpers;

    protected $request;
	
	public function __construct(Request $request){ 
	
        $this->request = $request;
		$this->logInfoPah = "logs/Machine/DB/logs_".date('Y-m-d').'.log';
		$this->errorPath = "logs/Machine/Error/logs_".date('Y-m-d').'.log';

    }
	
	 
	/**
	* machine list according to location
	* @params object $request
	* @return json $machineData
	*/
	public function machineList(Request $request)
	{
		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$orgId =  $header['orgId'];
			$projectId =  $header['projectId'];
			$roleId =  $header['roleId'];
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}		
		
		if($request) {
			$requestJson = json_decode(file_get_contents('php://input'), true);
			$user = $this->request->user(); 
			
			$user = $this->request->user();	
			$this->request->user_id = $user->_id;
			$this->logData($this->logInfoPah,$request->all(),'DB');		
		
			$database = $this->connectTenantDatabase($request,$orgId);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			
			
			$dateData = time()* 1000;
		//echo $this->request['machine_id'];exit;
		$startDate = $dateData;
		$endDate = $dateData;
		
		$start_date = Carbon::createFromTimestamp($startDate/1000 );
		$startdate = new Carbon($start_date);
       // $startdate->timezone = 'Asia/Kolkata';
		
		$endDate = Carbon::createFromTimestamp($endDate/1000 );
		$endDate = new Carbon($endDate);
       // $endDate->timezone = 'Asia/Kolkata';
		
        $start_date_str = new \MongoDB\BSON\UTCDateTime($startdate->startOfDay());
		$end_date_str = new \MongoDB\BSON\UTCDateTime($endDate->endOfDay());
		
		
			$machine = Machine::select('RTO_numner','chassis_no','updated_at','make_model','status','status_code','disel_tank_capacity','provider_contact_number','type_id','machine_code','state_id','district_id','taluka_id','manufactured_year','owned_by','provider_name','provider_address')
			->where(['project_id'=> $projectId
					])
			
			->with('State')			  
			->with('District')
			->with('Taluka')			
			->with('masterData')
			->with('MasterDatatype')
			->with('machine_make_master')
			->with('MasterManufactureYr')
			->with('machineDeployed.structureDetails')
			->with('operatorData.operatorData')			
			
			->with(['signOff' => function ($query) use ($start_date_str,$end_date_str) {
			   $query->where('created_at',">=",$start_date_str)
					->where('created_at',"<=",$end_date_str);
						
			}]);
			$state = explode(',',$requestJson['state']); 
				
			$machine->whereIn('state_id',$state);				

						
			if (isset($requestJson['district']) && $requestJson['district'] != "") {
				$district = explode(',',$requestJson['district']); 
				
				$machine->whereIn('district_id',$district);				
			}
			
			if (isset($requestJson['taluka']) && $requestJson['taluka']!= ""){
				$taluka = explode(',',$requestJson['taluka']); 
				
				$machine->whereIn('taluka_id',$taluka)->with('Taluka');
			}
			
			$machineDetails = $machine->orderBy('created_at', 'DESC')->get();		
			
			//echo '<pre>';print_r($machineDetails->toArray());exit;
			DB::setDefaultConnection('mongodb');
			//get TC role 
			$userRole = \App\Role::find($roleId);
			
			if (!$userRole) {
				$error = array('status' =>400,
							'message' => 'Invalid role id',							
							'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
				return response()->json($error);
				
			}	
			
			$roleData = \App\Role::where(['role_code'=>'111', 
									 'org_id'=>$orgId,
									 'project_id'=>$projectId])->first();
			//print_r($roleData->toArray());exit;
		
		
			$database = $this->connectTenantDatabase($request,$orgId);
            		
			if(count($machineDetails) > 0)
			{
				$ResponsemachineData = array();
				foreach($machineDetails as $row) {
					
					$machineData = array();
					//get oprator contact nu and name
					$mData = MachineMou::where('provider_information.machine_id',$row['_id'])->get()->last();
					
					$machineData['_id'] = $row['_id'];
					$machineData['make_model'] = $row['machine_make_master']['value'];
					$machineData['owned_by'] = $row['masterData']['value'];
					$machineData['state'] = $row['state']['name'];
					$machineData['stateId'] = $row['state']['_id'];
					$machineData['district'] = $row['district']['name'];
					$machineData['districtId'] = $row['district']['_id'];
					$machineData['taluka'] = $row['taluka']['name'];
					$machineData['talukaId'] = $row['taluka']['_id'];
					$machineData['provider_name'] = $row['provider_name'];
					$machineData['provider_address'] = isset($row['provider_address']) ? $row['provider_address'] : '';
					$machineData['machinetype'] = $row['MasterDatatype']['value'];
					$machineData['machine_code'] = $row['machine_code'];
					$machineData['disel_tank_capacity'] = $row['disel_tank_capacity'];
					$machineData['provider_contact_number'] = $row['provider_contact_number'];
					$machineData['status'] = ucfirst( $row['status']);
					$machineData['statusCode'] = $row['status_code'];
					$machineData['updatedDate'] = date('d M Y g:i a', strtotime($row['updated_at']));
					
					$machineData['machine_location'] = $row['taluka']['name'];
					$machineData['talukaId'] = $row['taluka']['_id'];
					$machineData['owned_by'] = $row['ownedBy']['value'];
					
					$machineData['operator_contact_number'] = '';
					$machineData['operator_name'] = '';
					$machineData['isMouUploaded'] =  false;
					$machineData['isOperatorassigned'] =  false;
						
					if ($mData ) {
					
						if (!empty($row['operatorData'])) {
							
							/*if ($row['_id']  == '5e44e91c0da6c4647350d8a8' ) {
								
								echo '<pre>';print_r($row['operatorData']['operatorData']['phone']);exit;
							}*/
							$machineData['isOperatorassigned'] =  true;
					
							$machineData['operator_contact_number'] = $row['operatorData']['operatorData']['phone'];
							$machineData['operator_name'] = $row['operatorData']['operatorData']['name'];
							$machineData['operator_id'] = $row['operatorData']['operatorData']['_id'];
						}
						
						if (isset($mData['mou_images'])) {
							$machineData['isMouUploaded'] =  true;
						
						}					
					}

					$machineData['isMachineSignOff'] = false; 

					if (!empty($row['signOff'])) {

						$machineData['isMachineSignOff'] = true; 

					}		
			
					if (!empty($row['machineDeployed']) ) { 
					
						$machineData['deployedStrutureId'] = $row['machineDeployed']['structure_id'];
						$machineData['deployedStrutureCode'] = $row['machineDeployed']['structureDetails']['code'];
					}
					
					$machineData['haltReason'] = '';
					if ($row['status_code'] == '111') {
						
						$machineHaltReason = MachineDailyWorkRecord::where(['machine_id'=>$row['_id'], 'status_code'=>'111'])->get()->last();
						if ($machineHaltReason) {
						
							$masterData = \App\MasterData::find($machineHaltReason['reason_id']);
							$machineData['haltReason'] = $masterData->value;
						}
					}
					
					if ($userRole->role_code == '111' && $row['status_code'] == '115') {
						continue;
					}	
					 $machineData['tc_name'] = '';
					 $machineData['tc_contact_number'] = '';
					
					$dtArry = array($row['district_id']);
					$talukaIds  = array($row['taluka_id']);	

					if ($roleData ) {	
						$data = \App\User::where(['orgDetails.role_id' => $roleData->_id])
						
						 ->whereIn('location.district',$dtArry)						
						 ->whereIn('location.taluka' , $talukaIds)
						 ->select('name','phone')->get()->toArray();
						
						if (count($data) > 0) {	 
							//print_r($query->toArray());exit;
							$machineData['tc_name'] = $data[0]['name'];
							$machineData['tc_contact_number'] = $data[0]['phone'];
						}
							
					
					}
					array_push($ResponsemachineData,$machineData);
				}
				
				if (count($ResponsemachineData) > 0) {
					
					$response_data = array('code'=>200,'status'=>200,'data' => $ResponsemachineData,'message'=>"success");
					return response()->json($response_data,200); 
				} else {
					$response_data = array('code'=>400,'status'=>400, 'message'=>"No Machines Found..");
					return response()->json($response_data); 
				}
			} else {
				$response_data = array('code'=>400,'status' =>400,'message'=>"No Machines Found..");
				return response()->json($response_data); 
			}
		} else {
			$response_data = array('code'=>400,'status' =>400,'message'=>"Undefined Request..");
            return response()->json($response_data); 
		}
	}



 
	//change status
	public function statusChange(Request $request,$id,$code,$statuscodes,$type,$org_id='')
	{
		if ($org_id == '') {
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
	}		
	$this->logData($this->logInfoPah,$request->all(),'DB');
				
	if ($request) { 
		$user = $this->request->user();
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
		}  
		$status_code = statusCode::where(['statusCode'=>$statuscodes, 'type'=>$type])->first();
		 
	if ($status_code){
			
		if ($type == 'machine') { 
		
			$machine = Machine::where('_id',$id)->first();
			$MachineLog =  new MachineLog;
			$MachineLog['code'] = $code;
			$MachineLog['action_title'] = $status_code['status_name'];
			$MachineLog['action_code'] = $status_code['statusCode'];
			$MachineLog['machine_id'] = $id;
			$MachineLog['action_by'] = $user['_id'];
		  	
			if ($machine) {
				
				$machine['status'] = $status_code['status_name'];
				$machine['status_code'] = $status_code['statusCode'];
				 
				try {
					$updated = $machine->save();
					$MachineLog->save();					
					//if machine release from taluka then close mapping
					if ($statuscodes == '115') {
						//update previous mapping status to shifted
						$strObj = StructureMachineMapping::where(['status' => 'deployed',
																  'machine_id'=> $id])
																  ->first();

						//print_r($strObj->toArray());exit;																  
						if ($strObj) {
							$strObj->status = 'closed';
							$strObj->save();				
						}
						//notification
						$params['request_type'] =  self::NOTIFICATION_MACHINE_FREE;
						$params['update_status'] = 'Machine Free From Taluka';
						$roleArr = array('110','111','112','115');
						$params['org_id'] = $org_id;
						$params['project_id'] = $project_id;				
						$params['code'] = $machine->machine_code;

						$params['stateId'] = $machine->state_id;
						$params['districtId'] = $machine->district_id;
						$params['talukaId'] = $machine->taluka_id;
						$params['modelName'] = 'Machine';
						
						//$this->sendSSNotification($this->request,$params, $roleArr);
						
						$this->request['functionName'] = __FUNCTION__;
						$this->request['params'] =  $params;
						$this->request['roleArr'] = $roleArr;

						dispatch((new DataQueue($this->request)));	
						
					}	
					
					if($updated) {
						$response_data = array('statusCode'=>$status_code['statusCode'],'code'=>200,'status' =>200,'message'=>"Status Updated Successfully.");
						$this->logData($this->logInfoPah,$this->request->all(),'DB',$response_data);
			
						return response()->json($response_data,200);
					}
				} catch(Exception $e) {
					
					$response_data = array('code'=>300,'status' =>300,'message'=>$e);
					$this->logData($this->logInfoPah,$this->request->all(),'DB',$response_data);
			
					return response()->json($response_data,200);
				}
			} else {
				$response_data = array('code'=>300,'status' =>300,'message'=>"Machine Not Found.");
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$response_data);
		
				return response()->json($response_data,200); 
			}
		}
		
		if ($type == 'structure') {
			
			$structure = Structure::where('_id',$id)->first();
			$structureLog =  new StructureLog;
			$structureLog['code'] = $code;
			$structureLog['action_title'] = $status_code['status_name'];
			$structureLog['action_code'] = $status_code['statusCode'];
			$structureLog['structure_id'] = $id;
			$structureLog['action_by'] = $user['_id'];
		  	
			if ($structure) {
				$structure['status'] = $status_code['status_name'];
				$structure['statusCode'] = $status_code['statusCode'];
				try{
					$updated = $structure->save();
					$structureLog->save();
					if($updated)
					{
						$response_data = array('statusCode'=>$status_code['statusCode'],'code'=>300,'status' =>300,'message'=>"Status Updated Successfully.");
						$this->logData($this->logInfoPah,$this->request->all(),'DB',$response_data);
			
						return response()->json($response_data,200);
					}
				}catch(Exception $e) {
					$response_data = array('code'=>300,'status' =>300,'message'=>$e);
					$this->logData($this->logInfoPah,$this->request->all(),'DB',$response_data);
			
					return response()->json($response_data,200);
				}
			} else {
					$response_data = array('code'=>300,'status' =>300,'message'=>"Structure Not Found.");
					$this->logData($this->logInfoPah,$this->request->all(),'DB',$response_data);
			
					return response()->json($response_data,200); 
			}
		}
		}else{
			$response_data = array('code'=>300,'status' =>300,'message'=>"Invalid Status Code..");
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$response_data);
			
            return response()->json($response_data,200); 
		}
		
		}
		else
		{
			$response_data = array('code'=>300,'status' =>300,'message'=>"Undefined Request..");
            return response()->json($response_data,200); 
		}
	}

	/** 
	* get all prject Machine analytics  from DB
	* @param object $request
	* @return json data
	* 
	*/
	public function getMachineAnalytics(Request $request)
	{		
		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$orgId =  $header['orgId'];
			$projectId =  $header['projectId'];
			$roleId =  $header['roleId'];
		} else {
			
			$message = "Header info missing";
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$database = $this->connectTenantDatabase($request,$orgId);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$user = $this->request->user();		
		$orgDetails = $user['orgDetails'];
		
		$userLocation = [];
		foreach ($orgDetails as $data) {
			
			if ($data['org_id'] == $orgId && $data['project_id'] ==  $projectId && $data['role_id'] ==  $roleId ) {
				$userLocation = $data['location'];
				break;
			}		
		}	
		
		$approverRoleConfig = \App\RoleConfig::where(['role_id'=> $roleId,
		'projects' => $projectId])
		->select('jurisdiction_type_id','level','role_id')
		->get();
		//echo '<pre>';print_r($approverRoleConfig);exit;
		if (count($approverRoleConfig) == 0) {
			
			return response()->json([
				'code'=>400,	
				'status' => 400,
				'message' => 'Invalid role Id'
			],200);		
		}
			
		$levelDetail = \App\Jurisdiction::where('_id',$approverRoleConfig[0]['level'])->get();	
		
		//$district = $user->location['district'];
		
		$statusCode = \App\StatusCode::where(['type'=>'machine'])->get();		
				
		$resultData = [];
		
		$query =  Machine::where(['is_active'=>1,
					'project_id'=> $projectId
					]);		
		
		if (strtolower($levelDetail[0]['levelName']) == 'state') {
			if (isset($userLocation['state'])) {

				$query->whereIn('state_id',$userLocation['state']);
			}		
		}		
		
		if (strtolower($levelDetail[0]['levelName']) == 'district') {
			if (isset($userLocation['district'])) {
				
				$query->whereIn('district_id',$userLocation['district']);
			}
			
		}			
		
		if (strtolower($levelDetail[0]['levelName']) == 'taluka') {
			
			if (isset($userLocation['taluka'])) {
				
				$query->whereIn('taluka_id',$userLocation['taluka']);
			}			
		}
		
		if ($this->request->has('state_id')&& $this->request->state_id != "") {

			$state = explode(',',$this->request->state_id); 
				
			$query->whereIn('state_id',$state);
		}
		
		if ($this->request->has('district_id') && $this->request->district_id != "") {
			
			$district = explode(',',$this->request->district_id); 
			
			$query->whereIn('district_id',$district);
		}
		
		
		if ($this->request->has('taluka_id') && $this->request->taluka_id != "" ) {
			
			$taluka = explode(',',$this->request->taluka_id); 
			
			$query->whereIn('taluka_id',$taluka);
		}
				
			
		$mCnt = $query->count();
		
		if ($mCnt > 0 ) {	
			$resultData[0]['percentValue'] = $mCnt;
			$resultData[0]['status'] = 'Total Machine';
			$resultData[0]['statusCode'] = 0;
		}
		$cnt = 1;
		foreach ($statusCode as $data) {
			
			$query =  Machine::where(['status_code' =>$data['statusCode']]);
			
			
			if (strtolower($levelDetail[0]['levelName']) == 'state') {
				if (isset($userLocation['state'])) {

					$query->whereIn('state_id',$userLocation['state']);
				}
			
			}
		
			
			if (strtolower($levelDetail[0]['levelName']) == 'district') {
				if (isset($userLocation['district'])) {
					
					$query->whereIn('district_id',$userLocation['district']);
				}
			
			}
			if (strtolower($levelDetail[0]['levelName']) == 'taluka') {
			
				if (isset($userLocation['taluka'])) {
					//$query->where('taluka_id',$userLocation['taluka'][0]);
					$query->whereIn('taluka_id',$userLocation['taluka']);
				}
			
			}

			if ($this->request->has('state_id') && $this->request->state_id != "") {

				$query->where('state_id',$this->request->state_id);
			}
			
			if ($this->request->has('district_id') && $this->request->district_id != "") {

				$query->where('district_id',$this->request->district_id);
			}
			
			
			if ($this->request->has('taluka_id') && $this->request->taluka_id != "") {

				$query->where('taluka_id',$this->request->taluka_id);
			}
			
			
			$approvedCnt = $query->count();
			$statusName = $data['status_name'];
			
			if ($approvedCnt > 0) {
				$resultData[$cnt]['percentValue'] = $approvedCnt;
				$resultData[$cnt]['status'] = $statusName;
				$resultData[$cnt]['statusCode'] = $data['statusCode'];
				$cnt ++;
			}

		}
		if (count($resultData) == 0) {
			
			return response()->json([
				'code'=>400,	
				'status' => 'failed',				
				'message' => 'No data available'
			]);
			
		}	
		return response()->json([
			'code'=>200,	
            'status' => 'success',
            'data' => $resultData,
            'message' => 'Machine analytics data'
        ]);

	}

	/**
	* Machine MOU process 
	*
	*
	*/
	public function machineMou(Request $request)
	{
		$this->logData($this->logInfoPah,$request->all(),'DB');		
			
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
		
		
		if ($request) { 
			$user = $this->request->user();		
			$this->request->user_id = $user->_id;
		
			$this->logData($this->logInfoPah,$this->request->all(),'DB');		
			
			if (!$this->request->has('formData')) {
				$error = array('status' =>300,
								'message' => 'Formdata field is missing',							
								'code' => 300);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
								
				return response()->json($error);			
			}

			
		   
			$temp = $this->request['formData'];
			$requestJson = json_decode($temp);

			//As discussed , oprator number is optional filed
			
			//check duplicate phone number in user collection
			/*if (!isset($requestJson->machine->machine_mobile_number)) {
				
				$error = array('status' =>300,
								'message' => 'Operator number field is missing',							
								'code' => 300);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
								
				return response()->json($error);
			}*/
			/*$userCnt = 0;
			$muCnt = 0;
			
			if (isset($requestJson->machine->machine_mobile_number) &&  ($requestJson->machine->machine_mobile_number) != "" )
			{
				//get user count	
				$userCnt = User::where(['phone'=>$requestJson->machine->machine_mobile_number])
				->whereNotNull('org_id')
				->count();
			
			
				$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
		
			
				if ($userCnt > 0) {				
					
					//checked in mou
					$muCnt = MachineMou::where(['machine_mobile_number'=>$requestJson->machine->machine_mobile_number,
												'provider_information.machine_id' => $requestJson->provider_information->machine_id])
					//->whereNotNull('org_id')
					->count();
					
					if ($muCnt > 0 ) {
		
						$error = array('status' =>300,
										'message' => 'Machine attached  mobile number already linked with some other machine.',							
										'code' => 300);						
						$this->logData($this->errorPath,$this->request->all(),'Error',$error);
										
						return response()->json($error);
					}				
				}
			}*/
			
			//check mou done except expired MOU
			
			/*$accountImage = 0;
			$licenseImage = 0;
			
			$accountImageUrl = [];
			$licenseImageUrl = [];
			
			if ($this->request->has('imageArraySize')) {
			
			for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {
				
				
				$fileName = 'accountImage';
					
				if ($this->request->has($fileName)) {
					
					if ($this->request->file($fileName)->isValid()) {
				
						$fileInstance = $this->request->file($fileName);
					
						$name = $fileInstance->getClientOriginalName();
						$ext = $this->request->file($fileName)->getClientMimeType(); 
						$newName = uniqid().'_'.$name.'.jpg';
						$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
						
						$accountImageUrl[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
					}
					$accountImage++;	
				}
				
				$fileName = 'licenseImage';
					
				if ($this->request->has($fileName)) {				
					
						if ($this->request->file($fileName)->isValid()) {
					
							$fileInstance = $this->request->file($fileName);
						
							$name = $fileInstance->getClientOriginalName();
							//$ext = $this->request->file($fileName)->getClientMimeType(); 
							
							$newName = uniqid().'_'.$name.'.jpg';
							$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
							
							$licenseImageUrl[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
							
						}					
					$licenseImage++;			
				}			
				break;		
			}
			}*/
			
			$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
		
			$MachineMou = new MachineMou;
			$MachineMou->project_id = $project_id;
			if (isset($requestJson->machine->machine_mobile_number)) {
			
				$MachineMou->machine_mobile_number = $requestJson->machine->machine_mobile_number;
			}
			$MachineMou['provider_information.first_name'] = (isset($requestJson->provider_information->first_name) ? $requestJson->provider_information->first_name : '' ); 	
			$MachineMou['provider_information.last_name'] = (isset($requestJson->provider_information->last_name) ? $requestJson->provider_information->last_name : '' ); 	
			$MachineMou['provider_information.address'] = (isset($requestJson->provider_information->address) ? $requestJson->provider_information->address : '' ); 	
			$MachineMou['provider_information.contact_number'] = (isset($requestJson->provider_information->contact_number) ? $requestJson->provider_information->contact_number : '' ); 	
			$MachineMou['provider_information.machine_id'] = (isset($requestJson->provider_information->machine_id) ? $requestJson->provider_information->machine_id : '' ); 	
			$MachineMou['provider_information.machine_meter_working'] = (isset($requestJson->provider_information->machine_meter_working) ? $requestJson->provider_information->machine_meter_working : '' ); 	
			$MachineMou['provider_information.PAN_number'] = (isset($requestJson->provider_information->PAN_number) ? $requestJson->provider_information->PAN_number : '' ); 	
			$MachineMou['provider_information.trade_name'] = (isset($requestJson->provider_information->trade_name) ? $requestJson->provider_information->trade_name : '' ); 	
			$MachineMou['provider_information.is_turnover'] = (isset($requestJson->provider_information->is_turnover) ? $requestJson->provider_information->is_turnover : '' ); 	
			$MachineMou['provider_information.GST_number'] = (isset($requestJson->provider_information->GST_number) ? $requestJson->provider_information->GST_number : '' ); 	
			$MachineMou['provider_information.account_name'] = (isset($requestJson->provider_information->account_name) ? $requestJson->provider_information->account_name : '' ); 	
			$MachineMou['provider_information.account_no'] = (isset($requestJson->provider_information->account_no) ? $requestJson->provider_information->account_no : '' ); 	
			$MachineMou['provider_information.bank_address'] = (isset($requestJson->provider_information->bank_address) ? $requestJson->provider_information->bank_address : '' ); 	
			$MachineMou['provider_information.branch'] = (isset($requestJson->provider_information->branch) ? $requestJson->provider_information->branch : '' ); 	
			$MachineMou['provider_information.bank_name'] = (isset($requestJson->provider_information->bank_name) ? $requestJson->provider_information->bank_name : '' );
			$MachineMou['provider_information.account_type'] = (isset($requestJson->provider_information->account_type) ? $requestJson->provider_information->account_type : '' );
			$MachineMou['provider_information.IFSC'] = (isset($requestJson->provider_information->IFSC) ? $requestJson->provider_information->IFSC : '' );
			/*$MachineMou['operator_details.first_name'] = (isset($requestJson->operator_details->first_name) ? $requestJson->operator_details->first_name : '' );
			$MachineMou['operator_details.last_name'] = (isset($requestJson->operator_details->last_name) ? $requestJson->operator_details->last_name : '' );
			$MachineMou['operator_details.address'] = (isset($requestJson->operator_details->address) ? $requestJson->operator_details->address : '' );
			$MachineMou['operator_details.licence_number'] = (isset($requestJson->operator_details->licence_number) ? $requestJson->operator_details->licence_number : '' );
			$MachineMou['operator_details.contact_numnber'] = (isset($requestJson->operator_details->contact_numnber) ? $requestJson->operator_details->contact_numnber : '' );
			*/
			//$MachineMou['operator_details.operator_images'] = $licenseImageUrl;
			
			if (isset($requestJson->mou_details)) {	  
					$count = 0; 
					if (isset($requestJson->mou_details->rate_details)) {
						foreach ($requestJson->mou_details->rate_details as $rate){ 
							$MachineMou['mou_details.rate_details.'.$count.'.from_date'] = $rate->from_date;
							$MachineMou['mou_details.rate_details.'.$count.'.to_date'] = $rate->to_date;
							$MachineMou['mou_details.rate_details.'.$count.'.value'] = $rate->value;
							$count++;
						}
					}					
				}
				
		 	
		// $MachineMou['operator_licence_image'] = (array_key_exists('operator_licence_image',$requestJson) ? $requestJson['operator_licence_image'] : '' );	
		//$MachineMou['mou_details.MOU_images'] = $accountImageUrl;
		$MachineMou['mou_details.date_of_sign'] = new \MongoDB\BSON\UTCDateTime($requestJson->mou_details->date_of_signing);	
		$MachineMou['mou_details.date_of_signing'] = (isset($requestJson->mou_details->date_of_signing) ? $requestJson->mou_details->date_of_signing : '' );	
		
		$MachineMou['mou_details.mou_expiry_date'] = (isset($requestJson->mou_details->mou_expiry_datetime) ? new \MongoDB\BSON\UTCDateTime($requestJson->mou_details->mou_expiry_datetime) : '' );	
		$MachineMou['mou_details.mou_expiry_datetime'] = (isset($requestJson->mou_details->mou_expiry_datetime) ? ($requestJson->mou_details->mou_expiry_datetime) : '' );	
		
		//(array_key_exists('mou_expiry_date',$requestJson['mou_details']) ? new \MongoDB\BSON\UTCDateTime($requestJson['mou_details']['mou_expiry_date']): '' );
		//$MachineMou['mou_details.mou_expiry_date'] = (array_key_exists('mou_expiry_date',$requestJson['mou_details']) ? $requestJson['mou_details']['mou_expiry_date'] : '' );
		
		$MachineMou['is_MOU_cancelled'] = "No";	
		$MachineMou['status'] = "MOU Done";
		$MachineMou['status_code'] = "104";	
		$MachineMou['lat'] = isset($requestJson->lat) ? (int)$requestJson->lat : 0;
		$MachineMou['long'] = isset($requestJson->long) ? (int)$requestJson->long : 0;								
		// $MachineMou['signing_contract_date'] = (new \MongoDB\BSON\UTCDateTime(Carbon::now()) ?: '' ); 
		$machine_code = (isset($requestJson->machine_code) ? $requestJson->machine_code : '' ); 
		
		$id = (isset($requestJson->provider_information->machine_id) ? $requestJson->provider_information->machine_id : '' ); 
		//(array_key_exists('machine_id',$requestJson['provider_information']) ? $requestJson['provider_information']['machine_id'] : '' );  ; 
		$status_name = 'MOU Done';
		$status_code = '104'; 
		$type = 'machine'; 
			try{ 
				$success = $MachineMou->save();
				//echo $MachineMou->_id;exit;
				//create oprator user
				/*if ($muCnt == 0 ) {
					$this->createOpratorUser($request,$requestJson,  $org_id, $project_id);
				}*/
				$this->statusChange($request,$id,$machine_code,$status_code,$type, $org_id);
				
				//get machine details
				$machineData = Machine::find($requestJson->provider_information->machine_id);
				
				if ($machineData) {
					//echo $requestJson->provider_information->machine_id;exit;
					//send notification
					$roleArr = array('110','111','112','114','115');
					$params['org_id'] = $org_id;
					$params['projectId'] = $project_id;
					
					$params['request_type'] =  self::NOTIFICATION_MACHINE_MOU;
					$params['update_status'] = 'MOU Done';				
					$params['code'] = $machineData->machine_code;
					
					$params['stateId'] = $machineData->state_id;
					$params['districtId'] = $machineData->district_id;
					$params['talukaId'] = $machineData->taluka_id;
					$params['modelName'] = 'Machine';
					
					$this->request->action = 'Machine MOU';
					$this->request['functionName'] = __FUNCTION__;
					$this->request['params'] =  $params;
					$this->request['roleArr'] = $roleArr;					
					$this->request['mouId'] = $MachineMou->id;
					//$this->request['muCnt'] = $muCnt;

					$request = dispatch((new DataQueue($this->request)));			
					//var_dump($request);exit;
					//$this->sendSSNotification($this->request,$params, $roleArr);
				}				
					
				if ($success) {
					
					$response_data = array("statusCode"=>104,
											'statusName'=>$status_name,
											'code'=>200,
											'status' =>200,
											'message'=>"MOU done successfully."
											);
											
					return response()->json($response_data,200);
				}
				
			} catch(Exception $e){
					$response_data = array('code'=>400,
											'status' =>400,
											'message'=>$e
											);
					return response()->json($response_data);
			}			 
		} else {
			$response_data = array('code'=>300,
									'status' =>300,
									'message'=>"Undefined Request."
									);
            return response()->json($response_data,200); 
		}
		
	}
	
	//change MOU status terminated or deployed 
	public function MOUTerminateDeployed(Request $request)
	{
		$this->logData($this->logInfoPah,$this->request->all(),'DB');
		
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
		
		if($request)
		{
			$user = $this->request->user(); 
			$database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
		
			$requestJson = json_decode(file_get_contents('php://input'), true);
			$MachineMou = MachineMou::where('provider_information.machine_id',$requestJson['machine_id'])
			->where('is_MOU_cancelled','No')
			//->where('status_code','MOU Done')
			->where('status_code','104')
			//->orWhere('status_code','115')			
						
			->first();
			
			$Machine = Machine::where('_id',$requestJson['machine_id'])->first();
		
			$status_code = statusCode::where('statusCode',$requestJson['status'])->first(); 
			
			if ($MachineMou) {
				
				if (array_key_exists('reason',$requestJson)) {					
					$MachineMou['status'] = $status_code['status_name'] ; 
					$MachineMou['status_code'] = $status_code['statusCode'] ; 
					$MachineMou['is_MOU_cancelled'] = 'Yes' ; 
					$MachineMou['reason'] = $requestJson['reason'] ; 	
					$MachineMou['machine_id'] = $requestJson['machine_id'] ; 
					
					$params['request_type'] =  self::NOTIFICATION_MACHINE_MOU_TERMINATED;
					$params['update_status'] = 'MOU Terminated';
				} else {					
					if (array_key_exists('deploy_taluka',$requestJson)) {
						
						$Machine['taluka_id'] = $requestJson['deploy_taluka'] ; 
						$talukaDetails = \App\Taluka::find($requestJson['deploy_taluka']);
						$params['talukaName'] = $talukaDetails->name;
						$params['request_type'] =  self::NOTIFICATION_MACHINE_AVAILABLE;
						$params['update_status'] = 'Machine Available';
						
					} else {
						
						$MachineMou['status'] = $status_code['status_name'] ; 
						$MachineMou['status_code'] = $status_code['statusCode'] ;
						
						$params['request_type'] =  self::NOTIFICATION_MACHINE_FREE;
						$params['update_status'] = 'Machine Free';
					}
					
				}
				
				try {
					$type = 'machine';	 
					$this->statusChange($request,$requestJson['machine_id'],$requestJson['machine_code'],$requestJson['status'],$type,$org_id); 
					$success = $MachineMou->save();
					$Machine->save();
					
					//$machineData = Machine::find($requestJson->provider_information->machine_id);
				
					if ($Machine) {
						//echo $requestJson->provider_information->machine_id;exit;
						//send notification
						$roleArr = array('110','111','112','115');
						$params['org_id'] = $org_id;
						$params['project_id'] = $project_id;
										
						$params['code'] = $Machine->machine_code;
						
						$params['stateId'] = $Machine->state_id;
						$params['districtId'] = $Machine->district_id;
						$params['talukaId'] = $Machine->taluka_id;
						$params['modelName'] = 'Machine';
						
						$this->request['functionName'] = __FUNCTION__;
						$this->request['params'] =  $params;
						$this->request['roleArr'] = $roleArr;

						dispatch((new DataQueue($this->request)));	
						//$this->sendSSNotification($this->request,$params, $roleArr);
					}
					
					if ($success) {
						$response_data = array('code'=>200,'status' =>200,'message'=>"Status Changed to ".$status_code['status_name']);
						return response()->json($response_data,200); 
					}
				} catch(Exception $e) {
					$response_data = array('code'=>300,'status' =>300,'message'=>$e);
					return response()->json($response_data,200); 
				}
			} else {
				$response_data = array('code'=>300,'status' =>300,'message'=>'No Machine Found..');
				return response()->json($response_data,200); 
			}
			 
		}else{
			$response_data = array('code'=>300,'status' =>300,'message'=>"Undefined Request..");
            return response()->json($response_data,200); 
		}
	}

	//machine detialed view 
	public function machineDetails(Request $request,$machineId,$type)
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
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		if($request)
		{
			$user = $this->request->user(); 
			$database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			if($type == '101' || $type == '102'){
				$machineDetail = Machine::select('machine_mobile_number','provider_name','provider_contact_number','is_meter_working','state_id','chassis_no','excavation_capacity','RTO_numner','manufactured_year','owned_by','ownership_type_id','district_id','taluka_id','type_id','make_model','status','status_code','machine_code','excavation_capacity','disel_tank_capacity')
			->where('_id',$machineId) 
			->with('State')
			->with('District') 
			->with('masterData')
			->with('MasterDatatype')
			->with('MasterManufactureYr')
			->with('ownership')
			->with('machine_make_master')
			->get();
			
			if(count($machineDetail) > 0)
			{
				$machineData = array();
				$ResponsemachineData = array();
				$machineData = array();
				$ResponsemachineData = array();
				
				foreach($machineDetail as $row)
				{
					$machineData['machine']['_id'] = $row['_id'];
					$machineData['machine']['status_code'] = $row['status_code'];
					$machineData['machine']['status'] = $row['status'];
					$machineData['machine']['make_model'] = $row['machine_make_master']['value'];
					$machineData['machine']['ownership_type_id'] = $row['ownership']['value']; 
					$machineData['machine']['owned_by'] = $row['masterData']['value']; 
					$machineData['machine']['district'] = $row['district']['name'];
					$machineData['machine']['state'] = $row['state']['name'];
					$machineData['machine']['taluka'] = $row['taluka']['name']; 
					//$machineData['machine']['manufactured_year'] = $row['manufactured_year'];
					$machineData['machine']['machinetype'] = $row['MasterDatatype']['value'];
					$machineData['machine']['machine_code'] = $row['machine_code'];
					$machineData['machine']['disel_tank_capacity'] = $row['disel_tank_capacity'];
					$machineData['machine']['manufactured_year'] = $row['MasterManufactureYr']['value'];
					$machineData['machine']['RTO_numner'] = $row['RTO_numner'];
					$machineData['machine']['excavation_capacity'] = $row['excavation_capacity'];
					$machineData['machine']['chassis_no'] = $row['chassis_no'];
					$machineData['machine']['is_meter_working'] = $row['is_meter_working'];
					$machineData['machine']['provider_name'] = $row['provider_name'];					
					$machineData['machine']['provider_contact_number'] = $row['provider_contact_number'];
					$machineData['machine']['machine_mobile_number'] =  $row['machineMou']['machine_mobile_number'];
					$ResponsemachineData = $machineData;
					break;
				}
				$response_data = array('status' =>200,'message'=>'Success','data'=>$ResponsemachineData);
				return response()->json($response_data,200); 
			} else{
			$response_data = array('code'=>300,'status' =>300,'message'=>"No Data Found.");
            return response()->json($response_data,200); 
			}	
			
			}else{
			$machineDetail = Machine::select('machine_mobile_number','provider_name','provider_contact_number','is_meter_working','state_id','chassis_no','excavation_capacity','RTO_numner','owned_by','ownership_type_id','district_id','taluka_id','type_id','manufactured_year','make_model','status','status_code','chassis_no','machine_code','excavation_capacity','disel_tank_capacity')
			->where('_id',$machineId) 
			->with('State')
			->with('District') 
			->with('masterData')
			->with('MasterDatatype')
			->with('ownership')
			->with('MasterManufactureYr')
			->with('machine_make_master')
			->with('machineMou')->get();
			if(count($machineDetail) > 0)
			{
				$machineData = array();
				$ResponsemachineData = array();
				
				foreach($machineDetail as $row)
				{
					$machineData['machine']['_id'] = $row['_id'];
					$machineData['machine']['status_code'] = $row['status_code'];
					$machineData['machine']['status'] = $row['status'];
					$machineData['machine']['state'] = $row['state']['name'];
					$machineData['machine']['make_model'] = $row['machine_make_master']['value'];
					$machineData['machine']['owned_by'] = $row['masterData']['value']; 
					$machineData['machine']['ownership_type_id'] = $row['ownership']['value']; 
					$machineData['machine']['district'] = $row['district']['name'];
					$machineData['machine']['taluka'] = $row['taluka']['name']; 
					//$machineData['machine']['manufactured_year'] = $row['manufactured_year'];
					$machineData['machine']['machinetype'] = $row['MasterDatatype']['value'];
					$machineData['machine']['machine_code'] = $row['machine_code'];
					$machineData['machine']['disel_tank_capacity'] = $row['disel_tank_capacity'];
					$machineData['machine']['manufactured_year'] = $row['MasterManufactureYr']['value'];
					$machineData['machine']['RTO_numner'] = $row['RTO_numner'];
					$machineData['machine']['excavation_capacity'] = $row['excavation_capacity'];
					$machineData['machine']['chassis_no'] = $row['chassis_no'];
					$machineData['machine']['is_meter_working'] = $row['is_meter_working'];
					
					$machineData['provider_information'] = $row['machineMou']['provider_information'];
					$machineData['operator_details'] = $row['machineMou']['operator_details'];
					/*$machineData['mou_details']['rate_details'] = $row['machineMou']['mou_details']['rate_details'];
					$machineData['mou_details']['MOU_images'] = $row['machineMou']['mou_details']['MOU_images'];
					$machineData['mou_details']['date_of_signing'] = $row['machineMou']['mou_details']['date_of_signing'];
					$machineData['mou_details']['is_MOU_cancelled'] = $row['machineMou']['is_MOU_cancelled'];
					$machineData['mou_details']['status'] = $row['machineMou']['status'];
					*/
					$machineData['mou_details'] = $row['machineMou']['mou_details'];					
					$machineData['machine']['provider_name'] = $row['provider_name'];					
					$machineData['machine']['provider_name'] = $row['provider_name'];					
					$machineData['machine']['provider_contact_number'] = $row['provider_contact_number'];
					$machineData['machine']['machine_mobile_number'] =  $row['machineMou']['machine_mobile_number'];
			
  
					$ResponsemachineData = $machineData; 
					break;
				}
			
				$response_data = array('status' =>200,'message'=>'Success','data'=>$ResponsemachineData);
				return response()->json($response_data,200); 
			}
			else{
			$response_data = array('code'=>400,'status' =>400,'message'=>"No Data Found.");
            return response()->json($response_data); 
			}}	
		}
		else{
			$response_data = array('code'=>300,'status' =>300,'message'=>"Undefined Request..");
            return response()->json($response_data,200); 
		}
	}
	
	/** 
	* create new machine
	*
	*
	*/
	public function createMachine (Request $request) {
		
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
		$database = $this->connectTenantDatabase($request,$org_id);		
		$this->request->userId =  $user->id;
		$this->logData($this->logInfoPah,$this->request->all(),'DB');
		
		if ($database === null) {
			return response()->json(['status' =>403, 									 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}		
		//$tempJson = json_decode(file_get_contents('php://input'), true);
		
		$requestJson = $this->request['machine'];	
		
		//$this->request['machine']['chasisNumber']
		
		
		if (!$this->request->has('machine')) {
			$error = array('status' =>300,
							'message' => 'Formdata field is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}

		//$temp = $this->request['formData'];
		//$requestJson = json_decode($temp);		
		//validate chassis number 
		if (!isset($requestJson['chassis_no'])) {

			$error = array('status' =>300,
							'message' => 'Machine chassis number is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		//validate State id
		if (!isset($requestJson['state'])) {
			$error = array('status' =>300,
							'message' => 'State id is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		//validate District id
		if (!isset($requestJson['district'])) {

			$error = array('status' =>300,
							'message' => 'District id is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		if (!isset($requestJson['taluka'])) {

			$error = array('status' =>300,
							'message' => 'Taluka id is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		/*if (!isset($requestJson['model_type_id'])) {

			$error = array('status' =>400,
							'msg' => 'Model type id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}*/
		
		/*if (!isset($requestJson['ownership_type_id'])) {

			$error = array('status' =>400,
							'msg' => 'Machine owened by field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}*/
		
		if (!isset($requestJson['machinetype'])) {

			$error = array('status' =>300,
							'message' => 'Machine type field is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		if (!isset($requestJson['owned_by'])) {

			$error = array('status' =>300,
							'msg' => 'Owned by id field is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		/*if (!isset($requestJson['provider_name'])) {

			$error = array('status' =>400,
							'msg' => 'Provider name field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		if (!isset($requestJson['provider_address'])) {

			$error = array('status' =>400,
							'msg' => 'Provider address field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		if (!isset($requestJson['provider_contact_number'])) {

			$error = array('status' =>400,
							'msg' => 'Provider contact number field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}*/
		//check duplicate cassi number
		$machineCnt = Machine::where('chassis_no',$requestJson['chassis_no'])->count();
		
		if ($machineCnt > 0) {
			
			$error = array('status' =>300,
							'message' => 'Duplicate chassis number',							
							'code' => 300);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);
			
		}	
		
		$getLastMachine = Machine::all()->last();
		$lastMachineCode = '100000';

		if ($getLastMachine) {
			$lastMachineCode = (explode("-",  $getLastMachine['machine_code'])[1]);
		}
        $lastMachineCode = $lastMachineCode + 1;
		
		$created_at = (new \MongoDB\BSON\UTCDateTime(Carbon::now()) ?: '' ); 
		
		$resultData = [
						'owned_by' =>isset( $requestJson['owned_by']) ? $requestJson['owned_by'] : '',
						'machine_code' => 'MBJS-'.$lastMachineCode,
						'project_id' =>$project_id,
						'state_id' => isset($requestJson['state']) ? $requestJson['state'] : '',
						'district_id' => isset($requestJson['district']) ? $requestJson['district'] : '',
						'taluka_id' => isset($requestJson['taluka']) ? $requestJson['taluka'] : '',
						'manufactured_year' => isset($requestJson['manufactured_year'] ) ? $requestJson['manufactured_year'] : '',
						//'model_type_id' => isset($requestJson['model_type_id']) ? $requestJson['model_type_id'] : '',
						'make_model' => isset($requestJson['make_model']) ? $requestJson['make_model'] : '',
						'type_id' => isset($requestJson['machinetype']) ? $requestJson['machinetype'] : '',
						'RTO_numner' => isset($requestJson['RTO_numner']) ? $requestJson['RTO_numner'] : '',
						'chassis_no' => isset($requestJson['chassis_no']) ? $requestJson['chassis_no'] : '',
						'excavation_capacity' => isset($requestJson['excavation_capacity']) ? $requestJson['excavation_capacity'] : '',
						'disel_tank_capacity' => isset ($requestJson['disel_tank_capacity']) ? $requestJson['disel_tank_capacity'] : '',
						'provider_name' => isset($requestJson['provider_name']) ? $requestJson['provider_name'] : '',
						//'provider_address' => isset($requestJson['provider_address']) ? $requestJson['provider_address'] : '',
						'provider_contact_number' => isset($requestJson['provider_contact_number']) ? $requestJson['provider_contact_number'] : '',
						'ownership_type_id' => isset($requestJson['ownership_type_id']) ? $requestJson['ownership_type_id'] : '',
						
						'is_meter_working' => isset($requestJson['is_meter_working']) ? $requestJson['is_meter_working'] : '',
						'lat' => isset($requestJson['lat']) ? $requestJson['lat'] : '',
						'long' => isset($requestJson['long']) ? $requestJson['long'] : '',
						
						'created_by' =>  $user->id,
						'updated_by' =>  $user->id,
						'is_active' => 1,
						'status'=>'new',
						'status_code'=> '101',
						'created_date_time' => $created_at,
						'updated_date_time' => $created_at,
						'created_at' => $created_at,
						'updated_at' => $created_at
                        ];                            
        
		try {
				DB::table('machine')->insert($resultData);
				$responseData = array('code'=>200,
										'status' =>200,
										'message'=>"Machine created successfully.");									
				
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
			
				return response()->json($responseData,200);
		
			} catch(Exception $e) {				
				
				$error = array('code'=>400,
								'status' =>400,
								'message'=>'Some error has occured .Please try again'
								);
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
			
				return response()->json($error,200);	
			}
		
	}

	public function getWorkDetails(Request $request) {
		
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
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		

		$requsetData = json_decode(file_get_contents('php://input'), true);

		//validate machine_id
		if (!isset($requsetData['machine_id'])) {
			$error = array('status' =>300,
							'message' => 'Machine id field is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$requsetData,'Error',$error);
							
			return response()->json($error);			
		}	

		//validate log_date
		if (!isset($requsetData['log_date'])) {
			$error = array('status' =>300,
							'message' => 'Log date field is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$requsetData,'Error',$error);
							
			return response()->json($error);			
		}
		$database = $this->connectTenantDatabase($request,$org_id);
		
		$log_date = $requsetData['log_date']; 
		
		
		if ($database === null) {
			return response()->json(['status' => 300,									 
									'message' => 'User does not belong to any Organization.'],
									300);
		}
		$start_date = Carbon::createFromTimestamp($log_date/1000 );
		$startdate = new Carbon($start_date);
        $startdate->timezone = 'Asia/Kolkata';
        $start_date_str = new \MongoDB\BSON\UTCDateTime($startdate->startOfDay());
		$end_date_str = new \MongoDB\BSON\UTCDateTime($startdate->endOfDay());
		
		$data = MachineDailyWorkRecord::where(['machine_id'=>$requsetData['machine_id']])
			->where('workDate',">=",$start_date_str)
			->where('workDate',"<=",$end_date_str)
			->where('status_code','110')
			->get();
			
			//->last();
		//echo '<pre>';print_r($data);exit;
		$data = $data->toArray();
		$toPluck = 'total_hours';
		$totalHrs = array_reduce($data, function($sum, $item) use($toPluck) {
			return $sum + $item[$toPluck];
		}, 0);

				
	 	if ($data == NULL) {
					
			$responseData = array('code'=>400,
								'status' =>400,								
								'message'=>"No data available");
		} else {
			//$data = $data->toArray();
			$resultData['is_action_taken'] = false;
			$resultData['_id'] = $data[0]['_id'];
			$resultData['status'] = $data[0]['status'];
			$resultData['totalHours'] =  gmdate("H:i",$totalHrs);
			$resultData['mis_status'] = $data[0]['mis_status'];
			
			if (isset($data[0]['is_valid'])) {
				$resultData['is_action_taken'] = true;
			}
					
			$responseData = array('code'=>200,
								'status' =>200,
								'result'=>$resultData,
								'message'=>"Machine log data");
		}
								
		$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
						
		return response()->json($responseData);	
		
	}
	
	//cron for mou expired machie
	public function machineMOUExpire() {
		
		$startdate = new Carbon();
        $startdate->timezone = 'Asia/Kolkata';
        $start_date_time = new \MongoDB\BSON\UTCDateTime($startdate->startOfDay()->addHours(5)->addMinutes(30));

		
		//get data all mou done
		$machineData = MachineMou::where(['status_code' => '104'])
		->where('mou_details.mou_expiry_date' <= $start_date_tim)
		->get()->toArray();
		
		if (count($machineData) == 0 ) {
			
			$responseData = array('code'=>400,
								'status' =>400,								
								'message'=>"No MOU available");	
								
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
						
			return response()->json($responseData);	
		
		}
		$statuData = ['status_code' => '114', 'status'=> 'MOU  Expired'];
		foreach ($machineData as $data) {
			
			$machineData = MachineMou::where(['_id' => $data['_id']])->update($statuData);
			
			$responseData = array('code'=>200,
								'status' =>200,
								'result'=> $data['_id'] .'updated successfully',
								'message'=>"Status updated");	
			
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);	
		
		}	
		
	}
	
	public function createOperator(Request $request) {
		
		$this->logData($this->logInfoPah,$request->all(),'DB');		
			
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
		$userLocation = $user['location'];
	
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		if (!$this->request->has('formData')) {
			$error = array('status' =>300,
							'message' => 'Formdata field is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}

		
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		
		if (!isset($requestJson->mobile_number)) {
			$error = array('status' =>300,
							'message' => 'Mobile number is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		if (trim($requestJson->mobile_number) == "") {
			$error = array('status' =>300,
							'message' => 'Mobile number value is empty',							
							'code' => 300);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}


		$phoneNumber = $requestJson->mobile_number;
		DB::setDefaultConnection('mongodb');
		
		$bjUser = User::where(['phone'=>$phoneNumber])
			//->whereNull('org_id')
			->first();
			
		if (isset($bjUser->org_id)) {
			$error = array('status' =>300,
							'message' => 'Operator mobile number already exist',							
							'code' => 300);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}		
		
		$licenseImageUrl = '';
		if ($this->request->has('imageArraySize')) {
			
			for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {				
				$fileName = 'licenseImage';
					
				if ($this->request->has($fileName)) {				
					
						if ($this->request->file($fileName)->isValid()) {
					
							$fileInstance = $this->request->file($fileName);
						
							$name = $fileInstance->getClientOriginalName();
							$newName = uniqid().'_'.$name.'.jpg';
							$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
							
							$licenseImageUrl = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
							
						}							
				}			
				break;		
			}
		}
		
		$name = (isset($requestJson->first_name) ? $requestJson->first_name : '' );
		$lname = (isset($requestJson->last_name) ? $requestJson->last_name : '' );
		$phoneNumber = $requestJson->mobile_number;
		$address = (isset($requestJson->address) ? $requestJson->address : '' );
		$licence_number = (isset($requestJson->licence_number) ? $requestJson->licence_number : '' );
		
		$status['status']= 'approved';
        $status['action_by']= $user->_id;
        $status['reason']= "";
            
		$bjUser = User::where(['phone'=>$phoneNumber])
			->whereNull('org_id')
			->first();
		
		if ($bjUser) {


		} else  {	
			$bjUser = new User;
		}
		//$database = $this->connectTenantDatabase($request,$orgId);	
		
		$password = app('hash')->make($phoneNumber);
		$bjUser->name = $name.' '.$lname;
		$bjUser->email = 'test@gmail.com';
		$bjUser->password =  $password;
		$bjUser->phone = $phoneNumber ;		
		$bjUser->approve_status = 'approved';		
		$bjUser->org_id = $org_id;
		$bjUser->profile_pic = '';		
		$bjUser->address = $address;
		$bjUser->licence_number = $licence_number;
		$bjUser->license_image_url = $licenseImageUrl;		
		$bjUser->project_id = array($project_id);
		$bjUser->is_app_installed = (isset($requestJson->is_app_installed) ? $requestJson->is_app_installed : 'No' );
		$bjUser->is_training_done = (isset($requestJson->is_app_installed) ? $requestJson->is_app_installed : 'No' );

		//->is_app_installed;
		
		//get role id from role collection
		DB::setDefaultConnection('mongodb');
		
		$roleData = \App\Role::where(['role_code'=> '113', 
								'is_deleted' => 0,
								'org_id'=> $org_id])
								->select('_id','role_code')
								->get()
								->toArray();							
								
		if (count($roleData) >0) {			
			$bjUser->role_id = $roleData[0]['_id'];
		} else  {
			
		}
		
		$orgDetails = $user['orgDetails'];
		
		$userLocation = [];
		foreach ($orgDetails as $data) {
			
			if ($data['org_id'] == $org_id && $data['project_id'] ==  $project_id) {
				$userLocation = $data['location'];
				break;
			}		
		}	
		
		
		$location =  new \stdClass;
 
		$location->state = $userLocation['state'];
		
		if (isset($userLocation['district'])) {
			$location->district = $userLocation['district'];
		}
		
		if (isset($userLocation['taluka'])) {
			$location->taluka = $userLocation['taluka'];
		}

		$bjUser->location = $location;
		
		$orgArray[] = [
			'org_id'=>$org_id,
			'project_id'=>$project_id,
			'role_id'=>$roleData[0]['_id'],
			'address'=>'',
			'leave_type'=>'',
			'lat'=>'',
			'long'=>'',
			'status'=>$status,
			'approver_user_id'=>'',
			'location'=>$location,
			'approve_status'=>'approved'
			];
		$bjUser->orgDetails = $orgArray ;
		
		try {
			
			$bjUser->save();	
			
			$responseData = array('code'=>200,
								  'status' =>200,
								  'message'=>'Operator created successfully'
								  );
								  
			$this->logData($this->logInfoPah,$bjUser,'DB',$responseData);
			
			return response()->json($responseData);
			
		} catch (Exception $e) {
					
			$response_data = array('code'=>300,'status' =>300,'message'=>$e);
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$response_data);
	
			return response()->json($response_data,200);
		}
	}
	
	public function machineMouUpload(Request $request) {
		
		
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
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		

		if (!isset($this->request->machineId)) {
			$error = array('status' =>300,
							'message' => 'Machine id field is missing',							
							'code' => 300);						
			$this->logData($this->errorPath,$request->all(),'Error',$error);
							
			return response()->json($error);			
		}	
		$database = $this->connectTenantDatabase($request,$org_id);
		
		$machine = Machine::find($this->request->machineId);
		
		if (!$machine ) {
			
			$error = array('status' =>300,
							'message' => 'Invalid machine',							
							'code' => 300);						
			$this->logData($this->errorPath,$request->all(),'Error',$error);
							
			return response()->json($error);		
		}
		
		$machineMOU = MachineMou::where(['status_code'=>'104',
									'provider_information.machine_id' => $this->request->machineId
									])
						->first();
		if ($machineMOU) {
			$params['modelName'] = 'Machine';
			$this->request['functionName'] = __FUNCTION__;
			$this->request['machineMOU'] =  $machineMOU->_id;
			$this->request['org_id'] = $org_id;
			$this->request['params'] = $params;
			$params['project_id'] = $project_id;
						
			
			dispatch((new DataQueue($this->request)));

			$responseData = array('code'=>200,
								  'status' =>200,
								  'message'=>'MOU image uploaded successfully'
								  );
								  
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
			
			return response()->json($responseData);	
			
		}
		
		$error = array('status' =>300,
							'message' => 'Invalid machine',							
							'code' => 300);						
		$this->logData($this->errorPath,$this->request->all(),'Error',$error);
						
		return response()->json($error);
	
	}
	
	public  function machineSignOff(Request $request) {
		
		
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
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');

		$requestJson = json_decode(file_get_contents('php://input'), true);
		//echo $requestJson['structure_id'];exit;
		
		//validate structure id
		if (!isset($requestJson['machine_id'])) {
			$error = array('status' =>400,
							'message' => 'Machine id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}	
		
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 400,									  
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		//$machineData = MachineSignOff::find($requestJson['machine_id']);
		$dateData = time()* 1000;
		//echo $this->request['machine_id'];exit;
		$startDate = $dateData;
		$endDate = $dateData;
		
		$start_date = Carbon::createFromTimestamp($startDate/1000 );
		$startdate = new Carbon($start_date);
       // $startdate->timezone = 'Asia/Kolkata';
		
		$endDate = Carbon::createFromTimestamp($endDate/1000 );
		$endDate = new Carbon($endDate);
       // $endDate->timezone = 'Asia/Kolkata';
		
        $start_date_str = new \MongoDB\BSON\UTCDateTime($startdate->startOfDay());
		$end_date_str = new \MongoDB\BSON\UTCDateTime($endDate->endOfDay());
		//echo $end_date_str;exit;
		
		$machineData = MachineSignOff::where(['machine_id'=>$this->request['machine_id']])
			->where('created_at',">=",$start_date_str)
			->where('created_at',"<=",$end_date_str)
			//->orderBy('workDate','ASC')
			//->where('status_code','110')
			->get()->toArray();
		
		if (count($machineData) == 0) {
			
			$mData = new MachineSignOff;
			
			$mData->machine_id = $this->request['machine_id'];	
			$mData->created_by = $user->_id;			
			
			try {
			
				$mData->save();	
				
				$responseData = array('code'=>200,
										'status' =>200,
										'message'=>"Machine signoff successfully.");
										
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
								
				return response()->json($responseData);
				
			} catch(Exception $e) {
				
				$error = array('code'=>400,
										'status' =>400,
										'message'=>'Some error has occured .Please try again',
										);
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
				
				return response()->json($error);
			}
			
		
		} 
		$error = array('status' =>400,
							'message' => 'Machine already sign off',							
							'code' => 400);						
		$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
		return response()->json($error);		
	}
	
	/**
	*
	*
	*
	*/	
	public function editWorkLog(Request $request) {
		
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
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');

		$requestJson = json_decode(file_get_contents('php://input'), true);
		//echo $requestJson['structure_id'];exit;
		
		//validate structure id
		if (isset($requestJson['start_id'])) {
			
			if (!isset($requestJson['start_meter_reading'])) {
				$error = array('status' =>400,
								'msg' => 'Start meter reading field is missing',							
								'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
								
				return response()->json($error);			
			}
			/*$error = array('status' =>400,
							'msg' => 'Start id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);*/			
		}

		if (isset($requestJson['end_id'])) {
			/*$error = array('status' =>400,
							'msg' => 'End id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);*/
			if (!isset($requestJson['end_meter_reading'])) {
				$error = array('status' =>400,
								'msg' => 'End Meter reading field is missing',							
								'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
								
				return response()->json($error);			
			}	
			
		}			
		
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 400,									  
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		if (isset($requestJson['start_id'])) {
		
			$logData = MachineDailyWorkRecord::find($requestJson['start_id']);
			$meter_reading = $requestJson['start_meter_reading'];
		}

		if (isset($requestJson['end_id'])) {
			$logData = MachineDailyWorkRecord::find($requestJson['end_id']);
			$meter_reading = $requestJson['end_meter_reading'];				
		}	
		
		
		if ($logData ) {
				
			$logData->meter_reading = $meter_reading;
			try {
			
				$logData->save();

				//$logData = MachineDailyWorkRecord::find($requestJson['end_id']);
				//$logData->meter_reading = $requestJson['end_meter_reading'];
				//$logData->save();
				
				$responseData = array('code'=>200,
										'status' =>200,
										'message'=>"Meter reading edited successfully.");
										
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
								
				return response()->json($responseData);
				
			} catch(Exception $e) {
				
				$error = array('code'=>400,
										'status' =>400,
										'message'=>'Some error has occured .Please try again',
										);
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
				
				return response()->json($error);
			}		
			
		}
		$error = array('code'=>400,
						'status' =>400,
						'message'=>'Invalid log id',
						);
		$this->logData($this->errorPath,$this->request->all(),'Error',$error);
				
		return response()->json($error);	
		
	}

	/**
	*
	*
	*
	*/	
	public function machineAvailable(Request $request) {
		
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
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');

		$requestJson = json_decode(file_get_contents('php://input'), true);
		//echo $requestJson['structure_id'];exit;
		
		//validate structure id
		if (!isset($requestJson['machine_id'])) {
			
			$error = array('status' =>400,
							'message' => 'Machine id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}

		/*if (!isset($requestJson['structure_id'])) {
			
			$error = array('status' =>400,
							'message' => 'Structure id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
			
		}*/

		if (!isset($requestJson['status_code'])) {
			
			$error = array('status' =>400,
							'message' => 'Status code field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
			
		}	
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 400,									  
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$status_code = StatusCode::where(['statusCode'=>$requestJson['status_code'], 'type'=>'machine'])->first();
		
		//$this->logData($this->logInfoPah,$status_code,'DB');

		if (!$status_code) {
			
			$error = array('code'=>400,
							'status' =>400,
							'message'=>'Invalid status code',
										);
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
				
			return response()->json($error);
		}
		
		$machineData = Machine::find($requestJson['machine_id']);
		
		
		if ($machineData) {
				
			$machineData->status_code = $status_code->statusCode;
			$machineData->status =  $status_code->status_name;
			try {
			
				$machineData->save();
				
				$strObj = StructureMachineMapping::where(['machine_id'=> $this->request['machine_id'],
													  'status'=> 'deployed'])
													  ->first();		
				if ($strObj) {
					$strObj->status = 'closed';
					$strObj->save();				
				}
				//$logData = MachineDailyWorkRecord::find($requestJson['end_id']);
				//$logData->meter_reading = $requestJson['end_meter_reading'];
				//$logData->save();
				
				$responseData = array('code'=>200,
										'status' =>200,
										'message'=>"Machine available successfully.");
										
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
								
				return response()->json($responseData);
				
			} catch(Exception $e) {
				
				$error = array('code'=>400,
										'status' =>400,
										'message'=>'Some error has occured .Please try again',
										);
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
				
				return response()->json($error);
			}		
			
		}
		$error = array('code'=>400,
						'status' =>400,
						'message'=>'Invalid log id',
						);
		$this->logData($this->errorPath,$this->request->all(),'Error',$error);
				
		return response()->json($error);	
		
	}

	//get free operator list API 
	public function getOperatorList(Request $request) {

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
		//$userLocation = $user['location'];
		$orgDetails = $user['orgDetails'];
		
		$userLocation = [];
		foreach ($orgDetails as $data) {
			
			if ($data['org_id'] == $org_id && $data['project_id'] ==  $project_id && $data['role_id'] ==  $role_id ) {
				$userLocation = $data['location'];
				break;
			}		
		}	
		
	
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');

		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		$roleData = \App\Role::where(['role_code'=> '113', 
								'is_deleted' => 0,
								'org_id'=> $org_id])
								->select('_id','role_code')
								->get()
								->toArray();							
								
		if (count($roleData) >0) {			
			$role_id = $roleData[0]['_id'];
		} 
		//get operator list
		$query = User::where(['role_id'=>$role_id,
								'project_id'=>$project_id])
								->with('operatorMappingList');
												
						if (isset($userLocation['state'])) {
							$query->whereIn('orgDetails.location.state',$userLocation['state']);
						}
						if (isset($userLocation['district'])) {
							$query->whereIn('orgDetails.location.district',$userLocation['district']);
						}
						if (isset($userLocation['taluka'])) {
							$query->whereIn('orgDetails.location.taluka',$userLocation['taluka']);
						}
						
		$userList = $query->select('_id','phone','name','licence_number','license_image_url')
					->get();
						
		
		$database = $this->connectTenantDatabase($request,$org_id);
		$resultData =[];
		foreach ($userList as $key=>$data) {
			$cnt = \App\OperatorMachineMapping::where('operator_id', $data['_id'])->count();
			//echo '<pre>';print_r($userList->toArray());exit;
			//echo $cnt .'</br>';
			if ($cnt == 0) {
				$resultData[$key]['_id'] =  $data['_id'];
				$resultData[$key]['name'] =  $data['name'];
				$resultData[$key]['phone'] =  $data['phone'];
				$resultData[$key]['licence_number'] =  isset($data['licence_number']) ? $data['licence_number'] : "";
				$resultData[$key]['license_image_url'] =  isset($data['license_image_url']) ? $data['licence_number'] : "";
				
			}//$resultData[][] =  $data['_id'];
			//unset($data);

		}	
		
		if (count($userList ) > 0){				
			$responseData = array('code'=>200,
						'status' =>200,
						'message'=>"Operator List found",
						'data'=>array_values($resultData));
		} else {

			$responseData = array('code'=>300,
						'status' =>300,
						'message'=>"No data found",
						);
		}		
											
		$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
		
		return response()->json($responseData);				
		
	}
	
	// deploy oprator on machine
	public function assignOperator(Request $request) {
		
		$header = getallheaders();
		
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$orgId =  $header['orgId'];
			$projectId =  $header['projectId'];
			$roleId =  $header['roleId'];
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}		
		$user = $this->request->user();		
		$this->request->user_id = $user->_id;
		//$userLocation = $user['location'];
	
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		if (!isset($requestJson['machine_id'])) {
			
			$error = array('status' =>400,
							'message' => 'Machine id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}

		if (!isset($requestJson['operator_id'])) {
			
			$error = array('status' =>400,
							'message' => 'Operator id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
			
		}
		
		$database = $this->connectTenantDatabase($request,$orgId);
		
		if ($database === null) {
			return response()->json(['status' => 400,									  
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$moMappingCnt = \App\OperatorMachineMapping::where(['machine_id'=>$requestJson['machine_id'],
													'operator_id'=>$requestJson['operator_id']])
													->count();

		if ($moMappingCnt > 0) {
			$responseData = array('code'=>300,
						'status' =>300,
						'message'=>"Operator already mapped with the machine",
						);
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
								
				return response()->json($responseData);
				
			
		} else {
			$moMappingCnt = new  \App\OperatorMachineMapping;
			$moMappingCnt->machine_id = $requestJson['machine_id'];
			$moMappingCnt->operator_id = $requestJson['operator_id'];
			$moMappingCnt->project_id = $projectId;
			$moMappingCnt->created_by = $user->_id;
			
			try {
				
				$moMappingCnt->save();
				
				//save in machine log 				
				$MachineLog =  new MachineLog;
				$MachineLog['machine_id'] = $requestJson['machine_id'];
				$MachineLog['action_title'] = 'Operator assigned';
				$MachineLog['operator_id'] = $requestJson['operator_id'];
				//$MachineLog['machine_id'] = $id;
				$MachineLog['action_by'] = $user->_id;
				$MachineLog['status'] = 'assigned';
				$MachineLog->save();
				
				
				$params['request_type'] =  self::NOTIFICATION_OPERATOR_ASSIGNED;
				$params['update_status'] = 'Operator Assigned';
				$roleArr = array('110','111','112','115');
				$params['org_id'] =  $orgId;
				$params['projectId'] =  $projectId;
				
				$mData = Machine::find($requestJson['machine_id']);
				
				$params['code'] = $mData->machine_code;			
				$params['stateId'] = $mData->state_id;
				$params['districtId'] = $mData->district_id;
				$params['talukaId'] = $mData->taluka_id;

				$params['modelName'] = 'Machine';
				
				DB::setDefaultConnection('mongodb');			
				//get TC role 
				$userData = User::find($requestJson['operator_id']);
				
				$params['userName'] = $userData->name;	
							
				$this->request['params'] =  $params;
				$this->request['functionName'] = __FUNCTION__;	
			
				//$this->sendSSNotification($this->request,$params, $roleArr);
				$this->request['roleArr'] = $roleArr;

				dispatch((new DataQueue($this->request)));			
				
				
				//insert into machine log
				$responseData = array('code'=>200,
										'status' =>200,
										'message'=>"Machine assigned successfully.");
										
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
								
				return response()->json($responseData);
				
			} catch(Exception $e) {
				
				$error = array('code'=>400,
										'status' =>400,
										'message'=>'Some error has occured .Please try again',
										);
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
				
				return response()->json($error);
			}		
			
			
		}		
	}
	
	
	// deploy oprator on machine
	public function releaseOperator(Request $request) {
		
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
		//$userLocation = $user['location'];
	
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		/*if (!isset($requestJson['machine_id'])) {
			
			$error = array('status' =>400,
							'msg' => 'Machine id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}*/

		if (!isset($requestJson['operator_id'])) {
			
			$error = array('status' =>400,
							'message' => 'Operator id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
			
		}
		
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 400,									  
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$moMapping = \App\OperatorMachineMapping::where(['operator_id'=>$requestJson['operator_id']])
													->first();

		if (!$moMapping) {
			$responseData = array('code'=>300,
						'status' =>300,
						'message'=>"Invalid operator id",
						);

			$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
								
			return response()->json($responseData);
				
		} else {
			
			try {
				
				/*$moMapping = \App\OperatorMachineMapping::where(['operator_id'=>$requestJson['operator_id']])
													->first();*/

				$machinId = $moMapping ->machine_id;
				$operatorId = $moMapping->operator_id;
				$moMapping->delete();
				
				//save in machine log 				
				$MachineLog =  new MachineLog;
				$MachineLog['machine_id'] = $machinId;
				$MachineLog['action_title'] = 'Operator released';
				$MachineLog['operator_id'] = $requestJson['operator_id'];
				//$MachineLog['machine_id'] = $id;
				$MachineLog['action_by'] = $user->_id;
				$MachineLog['status'] = 'released';
				
				$MachineLog->save();
				
				
				$params['request_type'] =  self::NOTIFICATION_OPERATOR_RELEASE;
				$params['update_status'] = 'Operator Release';
				$roleArr = array('110','111','112','115');
				$params['org_id'] =  $org_id;
				$params['projectId'] =  $project_id;
				
				$mData = Machine::find($machinId);
				
				$params['code'] = $mData->machine_code;			
				$params['stateId'] = $mData->state_id;
				$params['districtId'] = $mData->district_id;
				$params['talukaId'] = $mData->taluka_id;

				$params['modelName'] = 'Machine';
				
				DB::setDefaultConnection('mongodb');			
				//get TC role 
				$userData = User::find($operatorId);
				
				$params['userName'] = $userData->name;	
							
				$this->request['params'] =  $params;
				$this->request['functionName'] = __FUNCTION__;	
			
				//$this->sendSSNotification($this->request,$params, $roleArr);
				$this->request['roleArr'] = $roleArr;

				dispatch((new DataQueue($this->request)));			
				
				//insert into machine log
				$responseData = array('code'=>200,
										'status' =>200,
										'message'=>"Operator release from machine.");
										
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
								
				return response()->json($responseData);
				
			} catch(Exception $e) {
				
				$error = array('code'=>400,
										'status' =>400,
										'message'=>'Some error has occured .Please try again',
										);
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
				
				return response()->json($error);
			}		
			
			
		}		
	}
	
	//insert mapping machine oprator mapping 
	public function insertMapping(Request $request) {
		
		$header = getallheaders();
		
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$orgId =  $header['orgId'];
			$projectId =  $header['projectId'];
			$roleId =  $header['roleId'];
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}		
		$user = $this->request->user();		
		$this->request->user_id = $user->_id;
		//$userLocation = $user['location'];
	
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		
		$database = $this->connectTenantDatabase($request,$orgId);
		
		if ($database === null) {
			return response()->json(['status' => 400,									  
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		
		//get all machinesmOU data 
		
		
		
		//getmachine id 
		
		
		
		/*$moMappingCnt = \App\OperatorMachineMapping::where(['machine_id'=>$requestJson['machine_id'],
													'operator_id'=>$requestJson['operator_id']])
													->count();*/

		/*Machine::where(['is_active'=> 1,
						'project_id'=>$projectId])
						->with('MachineMou')
						
		*/
		$machineData = MachineMou::where(['status_code'=> '104'])
						->select('machine_mobile_number', 'provider_information.machine_id')->get();
			
		//echo '<pre>'; print_r($machineData->toArray());exit;
		
		foreach ($machineData as $data) {
			
			$userData = User::where('phone',$data->machine_mobile_number)->first();
			
			echo "No data found ".$data->machine_mobile_number;
			if ($userData) {
			
			$moMappingCnt = \App\OperatorMachineMapping::where(['machine_id'=>$data['provider_information']['machine_id'],
													'operator_id'=>$userData->id])
													->count();

			//echo $moMappingCnt;exit;
			if ($moMappingCnt > 0) {
				//echo 'MAchine alredy mapped with operator'.'Machine_id==>'.$data['provider_information']['machine_id']
			//					.'Oprator Id==>'.$userData->id.'<br>';
								
				
			} else {

					$moMappingCnt = new  \App\OperatorMachineMapping;
					$moMappingCnt->machine_id = $data['provider_information']['machine_id'];
					$moMappingCnt->operator_id = $userData->id;
					$moMappingCnt->project_id = $projectId;
					$moMappingCnt->created_by = $user->_id;
					$moMappingCnt->save();
					
					echo 'Machine mapped with operator'.'Machine_id==>'.$data['provider_information']['machine_id']
								.'Oprator Id==>'.$userData->id.'<br>';
				

			}	


			} else {

				echo "No data found ".$data->machine_mobile_number;
			}		
		}	
		
	}

	
}
?>
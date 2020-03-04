<?php

namespace App\Http\Controllers;

use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

use App\Location;
use App\State;
use App\District;
use App\Taluka;
use App\Structure;
use App\StructureDepartment;
use App\StructureSubDepartment;
use App\StructureType;
use App\MasterData;
use App\StructurePreparation;
use App\StructureLog;
use App\Machine;
use App\MachineMou;
use App\MachineLog;
use App\StructureMachineMapping;
use App\MachineDailyWorkRecord;
use App\MachineSiltDetails;
use App\MachineDieselRecord;
use App\MachineShifting;
use App\StructureCommunityMobilisation;
use App\StructureVisit;
use Jcf\Geocode\Geocode;

class Structure1Controller extends Controller
{

    use Helpers;

    protected $request;
	private $DefaultOrgId = '5ddfbb6bd6e2ef4f78207513'; 

    public function __construct(Request $request)
    {
        $this->request = $request;
		$this->logInfoPah = "logs/Structure/DB/logs_".date('Y-m-d').'.log';
		$this->errorPath = "logs/Structure/Error/logs_".date('Y-m-d').'.log';

    }
	
	//get Feedlsit from DB
	public function getStructureList(Request $request) {	
		
		$user = $this->request->user();	
		$this->request->user_id = $user->_id;
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
			
		$userLocation = $user['location'];
		$database = $this->connectTenantDatabase($request,'5c1b940ad503a31f360e1252');		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		//$data = json_decode(file_get_contents('php://input'), true);
		
		$approverRoleConfig = \App\RoleConfig::where('approver_role', $user->role_id)
		->select('jurisdiction_type_id','level','role_id')
		->get();
		
		if (empty($approverRoleConfig)) {
			
			return response()->json(['status' => 'error', 
									'data' => '', 
									'message' => 'Role missing'], 
									403
									);
            
			$this->logData($this->logInfoPah,$this->request, 'DB');
		
		}		
		$levelDetail = \App\Jurisdiction::where('_id',$approverRoleConfig[0]['level'])->get();	
		/*if (strtolower($levelDetail[0]['levelName']) == 'taluka') {
			
		}
		
		if (strtolower($levelDetail[0]['levelName']) == 'village') {
			
		}*/

		/*echo '<pre>';
		print_r($levelDetail->toArray());
		exit;*/
		
				
		/*$levelDetail = \App\Jurisdiction::where('_id',$levelIds)->get(); 
		$levelname = $levelDetail[0]->levelName;
		$jurisdictions = \App\JurisdictionType::whereIn('_id',$jurisdictionIds)->pluck('jurisdictions')[0];

		*/
		$this->request['district_id'] = '5c669d72c7982d31cc6b86cf';
		$district = $user->location['district'];
		
		if (!$this->request->has('district_id')) {		
			if (!in_array($this->request['district_id'], $district)) {
				echo "not exist";			
			}			
		}
		
		if (strtolower($levelDetail[0]['levelName']) == 'taluka') {
			$taluka = $user->location['taluka'];
			//if ($this->request->has('taluka_id')) {		
				if (!in_array($this->request['taluka_id'], $taluka)) {
					echo "not exist";			
				}			
			//}
			//$query->where('taluka_id','');
			
		}
		//$this->request['village_id'] = 'd34534636';
	/*	if (strtolower($levelDetail[0]['levelName']) == 'village') {
			$village = $user->location['village'];
			
			if (!in_array($this->request['village_id'], $village)) {
				/*return response()->json([
					'code'=>403,	
					'status' => 'success',
					//'data' => $resultData,
					'message' => 'No data available'
				]);	*/			
		/*	}			
		}*/		
		$query =  Structure::where(['is_active'=>1])
							->with('departmentName')
							->with('subDepartmentName')
							->with('workType')
							->with('structureType');
							
		if (strtolower($levelDetail[0]['levelName']) == 'district') {
			
			$query->where('district_id',$this->request['district_id']);
			
		}

		if (strtolower($levelDetail[0]['levelName']) == 'taluka') {
			
			$query->where('taluka_id',$this->request['taluka_id']);
			
		}

		if (strtolower($levelDetail[0]['levelName']) == 'village') {
			
			$query->where('village_id',$this->request['village_id']);
			
		}

		$structureList = $query->orderBy('id', 'DESC')
						->get();
		
		$resultData = [];		
		//echo '<pre>';print_r($structureList->toArray());exit;
		$result = $structureList->toArray();
		
		foreach ($result as $data) {
			
			$resultData['structureId'] = $data['_id'];
			$resultData['structureName'] = $data['name'];
			$resultData['structureCode'] = $data['code'];
			$resultData['structureWorkType'] = $data['work_type']['value'];
			$resultData['structureStatus'] = $data['status'];
			$resultData['structureStatusCode'] = $data['status_code'];	
			
			if (isset($data['lat'])) {
				$resultData['lat'] = $data['lat'];

			}
			
			if (isset($data['long'])) {
				$resultData['long'] = $data['long'];

			}
			
			$resultData['structureDepartmentName'] = $data['department_name']['value'];
			$resultData['structureSubDepartmentName'] = $data['sub_department_name']['value'];
			$resultData['structureType'] = $data['structure_type']['value'];
			$resultData['structureMachineList'] = "MBJS-100000,MBJS-100023";
			
		
		}
		
		if (count($resultData) == 0) {		
			return response()->json([
				'code'=>403,	
				'status' => 403,
				//'data' => $resultData,
				'message' => 'No data available'
			]);			
		}
		return response()->json([
			'code'=>200,	
            'status' => 200,
            'data' => $resultData,
            'message' => 'Getting a list of all structures'
        ]);	
	}

	//get Feedlsit from DB
	public function getStructureAnalyst(Request $request) 
	{
	
		//$this->logData($this->logInfoPah,$request, 'DB');
			
		/*$user = $this->request->user();		
		$userLocation = $user['location'];
		$database = $this->connectTenantDatabase($request,'5c1b940ad503a31f360e1252');		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		$data = json_decode(file_get_contents('php://input'), true);
		
		$approverRoleConfig = \App\RoleConfig::where('approver_role', $user->role_id)
		->select('jurisdiction_type_id','level','role_id')
		->get();
		
		if (empty($approverRoleConfig)) {
			
			return response()->json(['status' => 'error', 
									'data' => '', 
									'message' => 'Role missing'], 
									403
									);            
		
		}		
		$levelDetail = \App\Jurisdiction::where('_id',$approverRoleConfig[0]['level'])->get();	
		$this->request['district_id'] = '5c669d72c7982d31cc6b86cf';
		$district = $user->location['district'];
		
		if (!$this->request->has('district_id')) {		
			if (!in_array($this->request['district_id'], $district)) {
				//echo "not exist";			
			}			
		}
		
		if (strtolower($levelDetail[0]['levelName']) == 'taluka') {
			$taluka = $user->location['taluka'];					
			if (!in_array($this->request['taluka_id'], $taluka)) {
				//echo "not exist";			
			}			
			
		}
		$this->request['village_id'] = 'd34534636';
		if (strtolower($levelDetail[0]['levelName']) == 'village') {
			$village = $user->location['village'];
			
			if (!in_array($this->request['village_id'], $village)) {
				/*return response()->json([
					'code'=>403,	
					'status' => 'success',
					//'data' => $resultData,
					'message' => 'No data available'
				]);			
			}			
		}	*/	
		/*$query =  Structure::where(['is_active'=>1])
							->with('departmentName')
							->with('subDepartmentName')
							->with('workType')
							->with('structureType');
							
		if (strtolower($levelDetail[0]['levelName']) == 'district') {
			
			$query->where('district_id','');
			
		}

		if (strtolower($levelDetail[0]['levelName']) == 'taluka') {
			
			$query->where('taluka_id','');
			
		}

		if (strtolower($levelDetail[0]['levelName']) == 'village') {
			
			$query->where('village_id','');
			
		}

		$structureList = $query->orderBy('id', 'DESC')
						->get(); */						
		$resultData = [];
		//please do not change below format : changes done as per andorid team requirement
		$resultData[0]['Title'] = 'Silt Excvated';
		$resultData[0]['value'] = 1 .' Lak';
		$resultData[0]['unit'] = '(Cubic mtr)';
		$resultData[1]['Title'] = 'Water Storage Capacity  Increased by';
		$resultData[1]['value'] = 10 .' Crore';
		$resultData[1]['unit'] = '(Ltr)';
		//$resultData[2]['Title'] = '';
		$resultData[2]['percentValue'] = 78;
		$resultData[2]['status'] = 'Approved';

		$resultData[3]['percentValue'] = 50;
		$resultData[3]['status'] = 'Work in progress';

		$resultData[4]['percentValue'] = 15;
		$resultData[4]['status'] = 'Halted';

		$resultData[5]['percentValue'] = 35;
		$resultData[5]['status'] = 'Completed';
		
		if (count($resultData) == 0) {
			
			return response()->json([
				'code'=>403,	
				'status' => 403,
				//'data' => $resultData,
				'message' => 'No data available'
			]);
			
		}	
		return response()->json([
			'code'=>200,	
            'status' => 200,
            'data' => $resultData,
            'message' => 'Structure analytics data'
        ]);
		
	}
	
	//structure prepared from DB
	public function saveStructurePreparedData(Request $request) 
	{	
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		if (!$this->request->has('structure_id')) {
			$error = array('status' =>400,
							'msg' => 'Structure id missing missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}

		if ($this->request['ff_identified'] == true) {
			
			if ($this->request->has('ff_name')) {
			
			
			}
			
			if ($this->request->has('ff_mb_number')) {
			
			
			}
			
			if ($this->request->has('ff_traning_done')) {
				
			
			}

		}

		if (!$this->request->has('is_structure_fit')) {
			$error = array('status' =>400,
							'msg' => 'Is structure fit field missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
			
		}
		
		$isStructureFit = false;
		
		if ($this->request['is_structure_fit']) {
			
			$isStructureFit = true;
		}	
			
		$user = $this->request->user();		
		$userLocation = $user['location'];
		$database = $this->connectTenantDatabase($request,'5c1b940ad503a31f360e1252');		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$strData =  Structure::where(['_id'=>$this->request['structure_id']])
							->first();
							
		if (!$strData) {
			$error = array('status' =>400,
							'msg' => 'Invalid structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}

		$strData =  StructurePreparation::where(['structure_id'=>$this->request['structure_id']])
							->first();
							
		if ($strData) {
			$error = array('status' =>400,
							'msg' => 'You  have already prepared structure',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}
		
		
		if ($this->request->file('images') === null) {
			return response()->json([
									'code'=>400,
									'status' => 400, 
									'data' => '', 
									'message' => 'Images not found']
									);
		}
		$this->request->type = "BJS/Images/forms";
		$urls = [];
		foreach ($this->request->file('images') as $image) {
			if ($image->isValid()) {
				$name = $image->getClientOriginalName();
				$s3Path = $image->storePubliclyAs($this->types[$this->request->type], $name, 's3');

				if ($s3Path == null || !$s3Path) {
					continue;
				}
				$urls[] = 'https://' . env('AWS_BUCKET') . '.' . env('AWS_URL') . '/' . $s3Path;
			}
		}	
		$strPreObj = new StructurePreparation;
		$strPreObj->structure_id = $this->request['structure_id'];
		$strPreObj->ff_identified = $this->request['ff_identified'];
		$strPreObj->preparaion_structure_images = $urls; 

		if ($this->request['ff_identified'] == true) {

			$strPreObj->ff_name  = $this->request['ff_name'];
			$strPreObj->ff_mobile_number = $this->request['ff_mobile_number'];
			$strPreObj->ff_traning_done = $this->request['ff_traning_done'] ? true: false;

		
		}
		$strPreObj->is_structure_fit = $isStructureFit;
		$this->request->status = 'prepared';
		$this->request->status_code = '116';
			
		if (!$isStructureFit) {
			$strPreObj->reason = $this->request['reason'];
			$this->request->status = 'non-compliant';			
		}
		//print_r($user['id']);exit;
		$strPreObj->created_by = $user['id'];
		
		try {
			
			$strPreObj->save();
			
			$strData->lat = $this->request['lat'];
			$strData->long = $this->request['long'];
			$strData->save();
			
			$this->request->action = 'Preparation Checklist';
			//$this->request->status = 'prepared';
			
			$this->structureUpdateStatus($this->request);
			
			$success = array('status' =>'success',
							'code' => 200,
							'msg' => 'Structure prepared successfully'							
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

	public function structureUpdateStatus($request)
	{
		
		$strData =  Structure::where(['_id'=>$this->request['structure_id']])
							->first();
						
		if (!$strData) {
			$error = array('status' =>400,
							'msg' => 'Invalid structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
			

		}
		
		$strData->status = $this->request->status;
		$strData->status_code = $this->request->status_code;
		$strData->updated_by = $this->request->user_id;
		
		try {
			
			$strData->save();
			
			$this->structureLog($this->request);
			$success = array('status' =>'success',
							'msg' => 'Structure status updated successfully',							
							'code' => 200
							);				
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
			
			return true;
		} catch (Exception $e){
			
			$error = array('status' =>400,
							'msg' => 'Some error has occured.Please try again',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
			
			return false;			
			//return response()->json($error);
		}
	}

	public function structureLog($request) 
	{		
		$strLogObj = new StructureLog;
		$strLogObj->structure_id = $this->request['structure_id'];
		$strLogObj->action_title = $this->request->action;
		$strLogObj->status_code = $this->request->status_code;
		$strLogObj->status = $this->request->status;
		
		$strLogObj->action_by = $this->request->user_id;
		
		try {
			
			$strLogObj->save();
			
			$success = array('status' =>200,
							'code' => 200,
							'msg' => 'Structure log created successfully',							
							);				
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
			
			return true;
		} catch (Exception $e){
			
			$error = array('status' =>400,
							'msg' => 'Some error has occured.Please try again',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
			
			return false;			
			//return response()->json($error);
		}
	}
	
	/**
	* get all available list for deploye 
	* @param object $request	
	* @return json array
	*/
	public function getAllMachineAvalList(Request $request) {		
		
		$user = $this->request->user();		
		$database = $this->connectTenantDatabase($request,$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' =>403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		if (!isset($requestJson['structure_id'])) {
			$error = array('status' =>400,
							'msg' => 'Structure id missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		$strData =  Structure::where(['_id'=>$this->request['structure_id']])
							->first();
							
		if (!$strData) {
			$error = array('status' =>400,
							'msg' => 'Invalid structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}
	
		$machineDetails = Machine::select('make_model',
								'status','type_id',
								'district_id','taluka_id',
								'manufactured_year','owned_by',
								'provider_name',
								'provider_address')
		->where('district_id',$strData['district_id']) 
		->with('District')
		->with('masterData')
		->with('MasterDatatype')
		->with('machine_make_master')
		->where(['status'=>'available'])
		->where('taluka_id',$strData['taluka_id'])
		
		->with('Taluka')
		->orderBy('created_at','DESC')
		->get(); 

		//echo '<pre>';print_r($machineDetails->toArray());exit;
		if (count($machineDetails) > 0) {
			
			$machineData = array();
			$ResponsemachineData = array();
			
			foreach($machineDetails as $row) {
				
				$machineData['_id'] = $row['_id'];
				$machineData['make_model'] = $row['machine_make_master']['value'];
				$machineData['owned_by'] = $row['masterData']['value'];
				$machineData['district'] = $row['district']['name'];
				$machineData['taluka'] = $row['taluka']['name'];
				$machineData['provider_name'] = $row['provider_name'];
				$machineData['provider_address'] = $row['provider_address'];
				$machineData['manufactured_year'] = $row['manufactured_year'];
				$machineData['machinetype'] = $row['MasterDatatype']['value'];
				$machineData['status'] = $row['status'];
				$machineData['statusCode'] = $row['status_code'];
				
				array_push($ResponsemachineData,$machineData);
			}
			if (count($ResponsemachineData) > 0) {
				
				$responseData = array('code'=>200,
										'status'=>200,
										'data' => $ResponsemachineData,
										'message'=>"success"
										);
										
				return response()->json($responseData); 
			} else {
				$responseData = array(
										'code'=>400,
										'status'=>400, 
										'message'=>"No Machines Found.",
										);
				return response()->json($responseData); 
			}
		}
		$responseData = array(
						'code'=>400,
						'status'=>400, 
						'message'=>"No Machines Found.",
						);
		return response()->json($responseData);
	}

	/**
	* machine deployed to structure 
	* @param object $request
	* @return json array
	*/
	public function machineDeployed(Request $request) {
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate structure id
		if (!$this->request->has('structure_id')) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		if (!$this->request->has('machine_id')) {
			$error = array('status' =>400,
							'msg' => 'Machine id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($request,$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		//check structure  id exist or not
		$strData =  Structure::where(['_id'=>$requestJson['structure_id']])
							->first();
							
		if (!$strData) {
			$error = array('status' =>400,
							'msg' => 'Invalid structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}	
		
		//check machine id exist or not
		$machineData = Machine::where('_id',$requestJson['machine_id'])->first();
		
		if (!$machineData) {
			$error = array('status' =>400,
							'msg' => 'Invalid machine id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}
		
		$mappingCnt = StructureMachineMapping::where(['structure_id'=> $requestJson['structure_id'],
													'machine_id'=>$requestJson['machine_id']])
													->count();
		
		if ($mappingCnt > 0) {
			$error = array('status' =>400,
							'msg' => 'Machine already deployed on structure',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

			
		}	
		//insert structure and machine mapping in collection
		$strMachMappingObj = new StructureMachineMapping;
		
		$strMachMappingObj->structure_id = $requestJson['structure_id'];
		$strMachMappingObj->machine_id = $requestJson['machine_id'];
		$strMachMappingObj->deployed_date = $currentDateTime = Carbon::now()->timestamp;;
		$strMachMappingObj->status = 'deployed';
		$strMachMappingObj->created_by = $this->request->user_id;
		
		try {
			
			$strMachMappingObj->save();
			
			//update and insert status in log
			$this->request->status = 'in_progress';
			$this->request->status_code = '117';
			
			$this->request->action = 'Machine Deployed';
			
			$this->structureUpdateStatus($this->request);
			
			//update achine status
			$machineData->status = "deployed";
			$machineData->status_code = '107';
			$machineData->save();
			
			$this->machineStatusLog($this->request,$machineData->code);
			//update machine status
					
			$success = array('status' =>'success',
							'code' => 200,
							'msg' => 'Machine deployed successfully'						
							);				
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
			return response()->json($success);
			//return true;
		} catch (Exception $e){
			
			$error = array('status' =>400,
							'msg' => 'Some error has occured.Please try again',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
			
			return response()->json($error);
			//return false;
		}

	}


	/**
	* machine deployed to structure 
	* @param object $request
	* @param string $machineCode
	* @return json array
	*/
	public function machineStatusLog($request,$machineCode) {	
		
		$machineLog =  new MachineLog;
		
		$machineLog['code'] = $machineCode;
		$machineLog['action_title'] = $this->request->action;
		$machineLog['machine_id'] = $this->request['machine_id'];
		
		if ($this->request->status == 'deployed') {
			
			$machineLog['structure_id'] = $this->request['structure_id'];			
		}
		$machineLog['action_by'] = $this->request->user_id;
		$machineLog['status'] = $this->request->status;
		
		try{
			$machineLog->save();
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Status Updated Successfully.");
			return true;
			
		} catch(Exception $e) {
			$responseData = array('code'=>400,
									'status' =>400,
									'message'=>'Some error has occured .Please try again',
									);
			return response()->json($responseData);
		}
	}

	public function closeStructure(Request $request) {
	
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate structure id
		if (!$this->request->has('structure_id')) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($request,$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		//check structure  id exist or not
		$strData =  Structure::where(['_id'=>$requestJson['structure_id']])
							->first();
							
		if (!$strData) {
			$error = array('status' =>400,
							'msg' => 'Invalid structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}

		if ($this->request->file('images') === null) {
			return response()->json([
									'code'=>400,
									'status' => 400,									 
									'message' => 'Images not found']
									);
		}
		
		$this->request->type = "BJS/Images/forms";
		$urls = [];
		foreach ($this->request->file('images') as $image) {
			if ($image->isValid()) {
				$name = $image->getClientOriginalName();
				$s3Path = $image->storePubliclyAs($this->types[$this->request->type], $name, 's3');

				if ($s3Path == null || !$s3Path) {
					continue;
				}
				$urls[] = 'https://' . env('AWS_BUCKET') . '.' . env('AWS_URL') . '/' . $s3Path;
			}
		}

		//update status and uplaod certificate
		$strData->certificates = $urls;
		$strData->status = 'closed';
		$strData->status_code = '121';
		
		$strData->updated_by = $this->request->user_id;
		//$strData->save();

		try {
			$strData->save();
			
			$this->request->status = 'closed';
			$this->status_code = '121';
			$this->request->action = 'Structure closed';			
		
			$this->structureLog($this->request);
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Status closed Successfully.");
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
							
			return response()->json(responseData);
			
		} catch(Exception $e) {
			
			$error = array('code'=>400,
									'status' =>400,
									'message'=>'Some error has occured .Please try again',
									);
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
			
			return response()->json($error);
		}	
		
	}

	public function machineDailyWorkDetails(Request $request) {

		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate structure id
		if (!$this->request->has('machine_id')) {
			$error = array('status' =>400,
							'msg' => 'Machine id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($request,$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		//check machine id exist or not
		$machineData =  Machine::where(['_id'=>$requestJson['machine_id']])
							->first();
							
		if (!$machineData) {
			$error = array('status' =>400,
							'msg' => 'Invalid machine id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}
	}

	
	public function machineDieselRecord(Request $request) {

		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate structure id
		if (!$this->request->has('structure_id')) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate machine id
		if (!$this->request->has('machine_id')) {
			$error = array('status' =>400,
							'msg' => 'Machine id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		if (!$this->request->has('diesel_quantity_ltr')) {
			$error = array('status' =>400,
							'msg' => 'Diesel quantity is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		if (!$this->request->has('filling_date')) {
			$error = array('status' =>400,
							'msg' => 'Recevied date is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}		
		
		$database = $this->connectTenantDatabase($this->request,$user->org_id);		
		
		if ($database === null) {
			
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		$dieselReceiptImage = [];
		$dieselRegisterImage = '';

		/*
		//Diesel Receipt Photo 1*
		//Diesel Register Photo*
		
		
		
		
		
		
		
		
		
		
		
		*/		
		$machineDieselRecordObj = new MachineDieselRecord;
		$machineDieselRecordObj->structure_id = $this->request['structure_id'];
		$machineDieselRecordObj->machine_id = $this->request['machine_id'];
		$machineDieselRecordObj->diesel_quantity_ltr = (int) $this->request['diesel_quantity_ltr'];
		$machineDieselRecordObj->filling_date = (int) $this->request['filling_date'];
		$machineDieselRecordObj->diesel_receipt_image = $dieselReceiptImage;
		$machineDieselRecordObj->diesel_register_image = $dieselRegisterImage;
		$machineDieselRecordObj->created_by = $this->request->user_id;

		try {
			$machineDieselRecordObj->save();
			
			$responseData = array( 'code'=>200,
									'status' =>200,
									'message'=>"Machine diesel details saved successfully."
									);
			$this->logData($this->logInfoPah,$this->request->all(),'Error',$responseData);
							
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

	
	public function siltDetails (Request $request) {

		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate structure id
		if (!$this->request->has('structure_id')) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate structure id
		if (!$this->request->has('machine_id')) {
			$error = array('status' =>400,
							'msg' => 'Machine id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}

		
		$database = $this->connectTenantDatabase($this->request,$user->org_id);		
		
		if ($database === null) {
			
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		$dieselReceiptImage = [];
		//$dieselRegisterImage = '';

		/*
		//silt_register_image		
		
		
		
		
		
		
		
		
		
		
		*/
		
		$this->request['beneficiaries_count']=  $this->request->has('beneficiaries_count') ? $this->request['beneficiaries_count'] :0;
		$this->request['former_count'] = $this->request->has('former_count') ? $this->request['former_count'] : 0;
		$this->request['tractor_trips'] = ($this->request->has('tractor_trips')) ? $this->request['tractor_trips'] : 0;
		$this->request['tipper_trips'] = $this->request->has('tipper_trips') ? $this->request['tipper_trips'] :0;
		
		$machineSiltObj = new MachineSiltDetails;
		$machineSiltObj->machine_id = $this->request['machine_id'];
		$machineSiltObj->structure_id = $this->request['structure_id'];
		$machineSiltObj->tractor_trip_number = (int) $this->request['tractor_trips'];
		$machineSiltObj->tipper_trip_number = (int) $this->request['tipper_trips'];
		$machineSiltObj->former_count = (int) $this->request['former_count'];
		$machineSiltObj->beneficiaries_count = (int)$this->request['beneficiaries_count'];
		$machineSiltObj->silt_register_image = $dieselReceiptImage;
		$machineSiltObj->created_by = $this->request->user_id;

		try {
			$machineSiltObj->save();
			
			$responseData = array( 'code'=>200,
									'status' =>200,
									'message'=>"Silt details saved successfully."
									);
			$this->logData($this->logInfoPah,
							$this->request->all(),
							'Error',
							$responseData
							);
							
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

	/** get all prepared and in-progress structure for deplyed/shifting machine
	*
	*
	*
	*/
	public function  machineShifting(Request $request) {
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate current structure id
		if (!$this->request->has('current_structure_id')) {
			$error = array('status' =>400,
							'msg' => 'Current Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate New structure id
		if (!$this->request->has('new_structure_id')) {
			$error = array('status' =>400,
							'msg' => 'New Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate structure id
		if (!$this->request->has('machine_id')) {
			$error = array('status' =>400,
							'msg' => 'Machine id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		
		//validate structure id
		if (!$this->request->has('meter_reading')) {
			$error = array('status' =>400,
							'msg' => 'Meter reading field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate travel distance id
		if (!$this->request->has('travel_distance')) {
			$error = array('status' =>400,
							'msg' => 'Travel distance field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate travel time id
		if (!$this->request->has('travel_time')) {
			$error = array('status' =>400,
							'msg' => 'Travel time field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($this->request,$user->org_id);		
		
		if ($database === null) {
			
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}	
		
		//already machine deployed in new structure from current structure
		$mshiftingCnt =  MachineShifting::where(['machine_id'=> $this->request['machine_id'],
											'current_structure_id'=> $this->request['current_structure_id'],
											'new_structure_id'=> $this->request['new_structure_id']])
											->count();											
		if ($mshiftingCnt > 0) {
			
			$error = array('status' =>400,
							'msg' => 'Machine already shifted',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}
		
		//insert in shifting data
		$mshifting = new MachineShifting;
		$mshifting->machine_id = $this->request['machine_id'];
		$mshifting->current_structure_id = $this->request['current_structure_id'];
		$mshifting->new_structure_id = $this->request['new_structure_id'];
		$mshifting->travel_distance = $this->request['travel_distance'];
		$mshifting->travel_time = $this->request['travel_time'];
		$mshifting->meter_reading = $this->request['meter_reading'];
		$mshifting->diesel_provided_by = $this->request->has('diesel_provided_by') ? $this->request['diesel_provided_by'] : '';
		$mshifting->is_diesel_filled = $this->request->has('is_diesel_filled') ? $this->request['is_diesel_filled']  : false;
		$mshifting->created_by = $this->request->user_id;	
		
		try {
			$mshifting->save();	

			//update previous mapping status to shifted
			$strObj = StructureMachineMapping::where(['structure_id' => $this->request['current_structure_id'],
													  'machine_id'=> $this->request['machine_id']])
													  ->first();			
			if ($strObj) {
				$strObj->status = 'shifted';
				$strObj->save();				
			}
			
			//insert structure and machine mapping in collection
			$strMachMappingObj = new StructureMachineMapping;
			
			$strMachMappingObj->structure_id = $this->request['new_structure_id'];
			$strMachMappingObj->machine_id = $this->request['machine_id'];
			$strMachMappingObj->deployed_date = $currentDateTime = Carbon::now()->timestamp;;
			$strMachMappingObj->status = 'deployed';
			$strMachMappingObj->created_by = $this->request->user_id;
			
			$strMachMappingObj->save();
				
			//update structure status if available
			$this->request->status = 'in_progress';
			$this->request->status_code = '117';
			
			$this->request->action = 'Machine Deployed';
			$this->request['structure_id'] = $this->request['new_structure_id'];	
			$this->structureUpdateStatus($this->request);
			
			//update achine status		
			$machineData = Machine::where('_id',$this->request['machine_id'])->first();
			
			$machineData->status = "deployed";
			$machineData->save();
			
			//insert into machine log
			$this->machineStatusLog($this->request,$machineData->code);
		
			//$strObj = Structure::where("_id",$this->request['current_structure_id'])->first();
				
			$responseData = array( 'code'=>200,
									'status' =>200,
									'message'=>"Machine deployed successfully."
									);
			$this->logData($this->logInfoPah,
							$this->request->all(),
							'Error',
							$responseData
							);
							
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
	
	public function getMasterData(Request $request) {		
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		$database = $this->connectTenantDatabase($this->request,$user->org_id);		
		
		if ($database === null) {
			
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}	
		
		$masterData = MasterData::where('is_active',1)->get();
		
		$responseData = array( 'code'=>200,
								'status' =>200,
								'data'=>$masterData,
								'message'=>"Machine deployed successfully."
							);
							
		return response()->json($responseData);			
	}
	
	
	
	public function changeStructureStatus(Request $request) {
	
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate structure id
		if (!$this->request->has('structure_id')) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($request,$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		//check structure  id exist or not
		$strData =  Structure::where(['_id'=>$requestJson['structure_id']])
							->first();
							
		if (!$strData) {
			$error = array('status' =>400,
							'msg' => 'Invalid structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}

		$strData->status = strtolower($this->request['status']);
		$strData->status_code = $this->request['status_code'];
		
		$strData->updated_by = $this->request->user_id;
		//$strData->save();

		try {
			$strData->save();
			
			$this->request->status = $this->request['status'];
			$this->status_code = $this->request['status_code'];
			$this->request->action = 'Structure Status';			
		
			$this->structureLog($this->request);
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Status changed successfully.");
			$this->logData($this->errorPath,$this->request->all(),'Error',$responseData);
							
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
	
	//community mobilisation
	public function communityMobilisation (Request $request) {
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate structure id
		if (!$this->request->has('structure_id')) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($request,$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		$cmMobilisationObj = StructureCommunityMobilisation::where('structure_id',$requestJson['structure_id'])
										->first();
		
		
		if ($cmMobilisationObj) {
			
			
		} else {			
			$cmMobilisationObj = new StructureCommunityMobilisation;
			$cmMobilisationObj->structure_id = $requestJson['structure_id'];
			$cmMobilisationObj->created_by = $user->_id;
			
		}		
		$data= [];
		//entry level activity 
		if ($requestJson['type'] == "activity") {
			
			$cmMobilisationObj->entry_level_activity =  $requestJson['entry_level_activity'];
			
			$data['meeting_date'] = $requestJson['meeting_date'];
			$data['village_id'] = $requestJson['village_id'];			
			$data['grampanchayat_name'] = $requestJson['grampanchayat_name'];			
			$data['no_participant'] = $requestJson['no_participant'];			
			
			//$data['images'] = $requestJson['images'];

			$data['sarpanch_name'] = $requestJson['sarpanch_name'];		
			$data['sarpanch_phone_no'] = $requestJson['sarpanch_phone_no'];			
			$data['oopsarpanch_name'] = $requestJson['oopsarpanch_name'];		
			$data['oopsarpanch_phone_no'] = $requestJson['oopsarpanch_phone_no'];	
			
			$cmMobilisationObj->activity_data = $data;
			
			//$cmMobilisationObj->save();
			
		}

		if ($requestJson['type'] == "sensitisation") {
			
			$cmMobilisationObj->sensitisation =  $requestJson['sensitisation_id'];
			
			$data['village_id'] = $requestJson['village_id'];			
			$data['grampanchayat_name'] = $requestJson['grampanchayat_name'];			
			$data['no_participant'] = $requestJson['no_participant'];			
			$data['sarpanch_name'] = $requestJson['sarpanch_name'];		
			//$data['images'] = $requestJson['images'];

			
			$cmMobilisationObj->community_sensitisation = $data;
			
			//$cmMobilisationObj->save();
			
		}
		
		if ($requestJson['type'] == "task_force") {
			
			$cmMobilisationObj->formation_meeting_id =  $requestJson['formation_meeting_id'];
			
			$data['leader_name'] = $requestJson['leader_name'];			
			$data['member_name'] = $requestJson['member_name'];			
			$data['leader_phone_no'] = $requestJson['leader_phone_no'];			
			$data['education'] = $requestJson['education'];
			$data['occupation'] = $requestJson['occupation'];		
			$data['formation_date'] = $requestJson['formation_date'];		
						
			$cmMobilisationObj->task_force = $data;
			
			//$cmMobilisationObj->save();
			
		}
		
		if ($requestJson['type'] == "task_force_traning") {
			
			$cmMobilisationObj->task_force_traning_id =  $requestJson['task_force_traning_id'];
			
			$data['leader_name'] = $requestJson['topic_name'];			
			$data['topic_date'] = $requestJson['topic_date'];			
			$data['participant_name'] = $requestJson['participant_name'];			
			$data['duration'] = $requestJson['duration'];
			
			$data['occupation'] = $requestJson['image'];		
						
			$cmMobilisationObj->task_force_traning = $data;
			
			//$cmMobilisationObj->save();
			
		}
		
		
		if ($requestJson['type'] == "orientation") {
			
			$cmMobilisationObj->task_force_traning_id =  $requestJson['task_force_traning_id'];
			
			$data['leader_name'] = $requestJson['village_id'];			
			$data['department_name'] = $requestJson['department_name'];			
			$data['org_date'] = $requestJson['date'];			
			$data['former_name'] = $requestJson['former_name'];
			$data['former_phone_no'] = $requestJson['former_phone_no'];		
			$data['former_land_holding'] = $requestJson['former_land_holding'];		
			
			$data['program_image'] = $requestJson['program_image'];		
			
			
			$cmMobilisationObj->task_force_traning = $data;
			
			//$cmMobilisationObj->save();
			
		}
		
		try {
			$cmMobilisationObj->save();	
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Structure data saved successfully.");
									
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
							
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

	public function structureVisit(Request $request) {
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate structure id
		if (!$this->request->has('structure_id')) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($request,$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		$structureData = new StructureVisit;
		//::where('_id',$requestJson['structure_id'])->first();
		$structureData->structure_id = 	$requestJson['structure_id'];							
		$structureData->is_safety_signage = $requestJson['is_safety_signage'];							
		$structureData->is_guidelines_followed = $requestJson['is_guidelines_followed'];
		
		$structureData->structure_photos = $requestJson['images'];
		$structureData->status_record_id = $requestJson['status_record_id'];
		$structureData->issue_related_id = $requestJson['issue_related_id'];
		$structureData->issue_description = $requestJson['issue_description'];
		
		try {
			$structureData->save();	
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Structure visit data saved successfully.");
									
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
							
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
	
	
	//api for google map only
	public function getstate(Request $request,$type,$id)
	{  
		$database = $this->connectTenantDatabase($request,$this->DefaultOrgId);	
		$data = [];	
		$mainData = [];
	     
		if($type == 'state'){
			$Structure = Structure::get();
			foreach($Structure as $struct)
			{
				$state = State::find($struct['state_id']);
				$address = Geocode::make()->address($state['name']);
				 
				$data['id'] = $state['_id'];  
				$data['type'] = 'state';
				$data['lat'] = $address->response->geometry->location->lat; 
				$data['long'] =  $address->response->geometry->location->lng; 
				array_push($mainData,$data);
			}		
		}
		
		if($type == 'district'){
			$Structure = Structure::where('state_id',$id)->get();	
			foreach($Structure as $struct)
			{
				$district = District::find($struct['district_id']);
				$address = Geocode::make()->address($district['name']);
				 
				$data['id'] = $district['_id'];  
				$data['type'] = 'district';
				$data['lat'] = $address->response->geometry->location->lat; 
				$data['long'] =  $address->response->geometry->location->lng; 
				array_push($mainData,$data); 
			}		
		}
		if($type == 'taluka'){
			$Structure = Structure::where('district_id',$id)->get();	
			foreach($Structure as $struct)
			{
				$taluka = Taluka::find($struct['taluka_id']);
				$address = Geocode::make()->address($taluka['name']);
				 
				$data['id'] = $taluka['_id'];  
				$data['type'] = 'taluka';
				$data['lat'] = $address->response->geometry->location->lat; 
				$data['long'] =  $address->response->geometry->location->lng; 
				array_push($mainData,$data); 
			}		
		}

		return($mainData); 	
	}
	
	//api for google map only
   public function getStructures(Request $request,$talukaId)
   {
	  $database = $this->connectTenantDatabase($request,$this->DefaultOrgId);		
	  $Structure = Structure::select('lat','long','title','code','structure_boundary')
				  ->where('taluka_id',$talukaId)
				  ->get();
	  
	  $data = [];	
	  $mainData = [];
	  
	  foreach($Structure as $struct)
	  {
		$StructureMachineMapping = StructureMachineMapping::where('structure_id',$struct['_id'])->get();  
		$count = array();
		foreach($StructureMachineMapping as $machineC)
		{
			array_push($count,$machineC->machine_id); 	
		}  
		 
		$MachineCount = count(array_unique($count));
		
		if(isset($struct['lat']) && isset($struct['long']) && $struct['lat']!=0 && $struct['long']!=0){   
				$response = Geocode::make()->latLng((double)$struct['lat'],(double)$struct['long']);	
				$data['address'] = $response->formattedAddress();  
				} 
		
		$data['title'] = $struct['title'];  
		$data['code'] = $struct['code'];  
		$data['id'] = $struct['_id'];  
		$data['type'] = 'structure';
		$data['lat'] =  $struct['lat']; 
		$data['long'] =  $struct['long']; 
		// if($struct['structure_boundary'] != null)
		$data['structure_boundary'] =  $struct['structure_boundary']; 
		$data['MachineCount'] =  $MachineCount; 
		array_push($mainData,$data); 
	  }
	   
	  return($mainData);
   }  

	//api for google map only
   public function getmachines(Request $request,$id) 
   {
	    $data = [];	
	    $mainData = [];
	   $database = $this->connectTenantDatabase($request,$this->DefaultOrgId);		
	   $StructureMachineMapping = StructureMachineMapping::where('structure_id',$id)->get(); 
	  
	   if($StructureMachineMapping)
	   {
		   foreach($StructureMachineMapping as $StructureMMap)
		   {
			   $Machine = Machine::where('_id',$StructureMMap['machine_id'])->get();
			     
			     if($Machine)
			   {  
					if($Machine[0]['lat'] != '' ){
				$data['type'] = 'machine';
				$data['id'] = $Machine[0]['_id'];
				$data['machinecode'] = $Machine[0]['machine_code'];
				$data['status'] = $Machine[0]['status'];
				if(isset($Machine[0]['lat'])){
				$data['lat'] =  (double)$Machine[0]['lat']; 
				}
				if(isset($Machine[0]['lat'])){
				$data['long'] = (double)$Machine[0]['long'];  
				} 
				if(isset($Machine[0]['lat']) && isset($Machine[0]['lat'])){  
				$response = Geocode::make()->latLng((double)$Machine[0]['lat'],(double)$Machine[0]['long']);
				
				$data['address'] = $response->formattedAddress();  
				}  
				unset($Machine); 	
				
				array_push($mainData,$data);   
					}
			   }  
		   }		   
	   } 
	  return($mainData);
   }   
 	
	
	//api for google map only
	function getallstate(Request $request)
	{
		$database = $this->connectTenantDatabase($request,$this->DefaultOrgId);	
		$locations = Location::select('state_id')
							   ->groupBy('state_id')
							   ->with('state')
							   ->get();
		return($locations);
	}
	
	
	//api for google map only
	function getalldistrict(Request $request,$stateid)
	{
		$database = $this->connectTenantDatabase($request,$this->DefaultOrgId);	
		$locations = Location::where('state_id',$stateid) 
							   ->select('district_id')
							   ->groupBy('district_id')
							   ->with('district')
							   ->get();
		return($locations);
	}
	
	//api for google map only
	function getalltaluka(Request $request,$districtid)
	{
		$database = $this->connectTenantDatabase($request,$this->DefaultOrgId);	
		$locations = Location::where('district_id',$districtid) 
							   ->select('taluka_id')
							   ->groupBy('taluka_id')
							   ->with('taluka')
							   ->get();
		return($locations);
	}
	
	function curlCall(Request $request) {
		
		$data = \App\User::where('phone','9028724868')->get()->toArray();
		
		
		define('API_ACCESS_KEY','AAAAxAoRWyc:APA91bHVYeWNeHFqwO74C-W-uAJPeydy1XQSShbgq1dO___UW1g8kheoOP6EBi38L-aqMsV7RYw72KiGQL7qZv7IL301DxTUuwFp1Rh3XDfTZCshr217P0EnOQnFZOm4J73vvO7ACAjo');
		$fcmUrl = 'https://fcm.googleapis.com/fcm/send';
		
		//print_r($data[0]['firebase_id']);exit;
		
		$notification = [
            'title' =>'test daa',
            'body' => 'msggggg',
            'icon' =>'myIcon', 
            'sound' => 'mySound'
        ]; 
		 
        
		
		$extraNotificationData = ["message" => $notification,"moredata" =>'dd'];

		$fcmNotification = [
            //'registration_ids' => $tokenList, //multple token array
            'to'        =>$data[0]['firebase_id'], //single token
            'notification' => $notification,
			'title'=>'fvgdfgdfg',
			'message'=>'vcvcvbvc',
			'toOpen' => 'structure',			
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
		
		
	}
	
	
	
}
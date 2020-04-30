<?php

namespace App\Http\Controllers;

use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

use App\Structure;
use App\StructureDepartment;
use App\StructureSubDepartment;
use App\StructureType;
use App\MasterData;
use App\StructurePreparation;
use App\StructureLog;
use App\Machine;
use App\MachineLog;
use App\StructureMachineMapping;
use App\MachineDailyWorkRecord;
use App\MachineSiltDetails;
use App\MachineDieselRecord;
use App\MachineShifting;
use App\MachineManufactureYear;
use App\StructureCommunityMobilisation;
use App\StructureVisit;

class DumpMasterController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
		$this->logInfoPah = "logs/Structure/DB/logs_".date('Y-m-d').'.log';
		$this->errorPath = "logs/Structure/Error/logs_".date('Y-m-d').'.log';

		$this->logInfoPah = "logs/dumpMaster/Data/logs_".date('Y-m-d').'.json';

    }

    //get structure master data

    //get Feedlsit from DB

    //
    public function dumpData(Request $request)
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
    	$database = $this->connectTenantDatabase($request,$org_id);	
    	
    	$machineMakeDate = \App\MachineMakeMaster::get();
    	echo json_encode($machineMakeDate);
    	//echo json_encode($machineMakeDate);
    	$this->logData($this->logInfoPah ,$machineMakeDate,'Data');

    	die();
    }
	public function getStructureMasterData(Request $request) {	
		
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
		$this->logData($this->logInfoPah,$this->request->all(),'DB');

		$database = $this->connectTenantDatabase($request,$org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}

		$resultData= array();
		$finalData = array();

		$machineMakeDate = \App\MachineMakeMaster::where('is_active', 1)
							->get();
			if($machineMakeDate)
			{
				$machineMakeData['form'] = 'machine_create';
				$machineMakeData['field'] = 'machineMake';
				$machineMakeData['data'] = $machineMakeDate;
				array_push($resultData,$machineMakeData);
				unset($machineMakeData);
				$machineMakeData['form'] = 'machine_mou';
				$machineMakeData['field'] = 'machineMake';
				$machineMakeData['data'] = $machineMakeDate;
				array_push($resultData,$machineMakeData);
			}					
		
		/*$workTypeData = MasterData::where('type','work_type')
								->where('is_active',1)->get();					
			if($workTypeData)
			{
				$resultData['workType'] = $workTypeData;
			}*/

		$machineTypeData = MasterData::where('type','machine_type')
								->where('is_active',1)->get();

			//echo json_encode($machineTypeData);
			//die();					
			if($machineTypeData)
			{
				$machineType['form'] = 'machine_create';
				$machineType['field'] = 'machineType';
				$machineType['data'] = $machineTypeData;
				
				array_push($resultData,$machineType);
				unset($machineType);

				$machineType['form'] = 'machine_mou';
				$machineType['field'] = 'machineType';
				$machineType['data'] = $machineTypeData;

				array_push($resultData,$machineType);
				unset($machineType);

			}					

		$ownerTypeData = MasterData::where('type','owner_type')
								->where('is_active',1)->get();
			if($ownerTypeData)
			{
				$ownerType['form'] = 'machine_create';
				$ownerType['field'] = 'ownerType';
				$ownerType['data'] = $ownerTypeData;
				
				array_push($resultData,$ownerType);
				unset($ownerType);

				$ownerType['form'] = 'machine_mou';
				$ownerType['field'] = 'ownerType';
				$ownerType['data'] = $ownerTypeData;

				array_push($resultData,$ownerType);
				unset($accountType);


			}						
								
		$accountTypeData = MasterData::where('type','account_type')
								->where('is_active',1)->get();
			if($accountTypeData)
			{
				$accountType['form'] = 'machine_create';
				$accountType['field'] = 'accountType';
				$accountType['data'] = $accountTypeData;

				array_push($resultData,$accountType);
				unset($accountType);

			}					

		$ownedByData = MasterData::where('type','owned_by')
								->where('is_active',1)->get();
			if($ownedByData)
			{
				$ownedBy['form'] = 'machine_create';
				$ownedBy['field'] = 'ownedBy';
				$ownedBy['data'] = $ownedByData;
				
				array_push($resultData,$ownedBy);
				unset($ownedBy);

				$ownedBy['form'] = 'machine_mou';
				$ownedBy['field'] = 'ownedBy';
				$ownedBy['data'] = $ownedByData;

				array_push($resultData,$ownedBy);
				unset($ownedBy);

			}						
								
		$dieselProvidedByData = MasterData::where('type','diesel_provided_by')
								->where('is_active',1)->get();
								
		if ($dieselProvidedByData) {
			$dieselProvidedBy['form'] = 'shifting';
			$dieselProvidedBy['field'] = 'dieselProvidedBy';
			$dieselProvidedBy['data'] = $dieselProvidedByData;

			array_push($resultData,$dieselProvidedBy);
			unset($dieselProvidedBy);

		}							

		$structureTypeData = StructureType::where('is_active',1)->get();

		if ($structureTypeData) {
			$structureType['form'] = 'structure_create';
			$structureType['field'] = 'structureType';
			$structureType['data'] = $structureTypeData;

			array_push($resultData,$structureType);
			unset($structureType);

		}		
		
		$structureDeptData = StructureDepartment::where('is_active',1)->get();

		if ($structureDeptData) {
			$structureDept['form'] = 'structure_create';
			$structureDept['field'] = 'structureDept';
			$structureDept['data'] = $structureDeptData;

			array_push($resultData,$structureDept);
			unset($structureDept);

		}							
		
		$structureSubDeptData = StructureSubDepartment::where('is_active',1)->get();

		if ($structureSubDeptData) {
			$structureSubDept['form'] = 'structure_create';
			$structureSubDept['field'] = 'structureSubDept';
			$structureSubDept['data'] = $structureSubDeptData;

			array_push($resultData,$structureSubDept);
			unset($structureSubDept);

		}

		$provideByData = MasterData::where('type','diesel_provided_by')
								->where('is_active',1)->get();
		if ($provideByData) {
			$provideBy['form'] = 'machine_shifting';
			$provideBy['field'] = 'providedBy';
			$provideBy['data'] = $provideByData;

			array_push($resultData,$provideBy);
			unset($provideBy);

		}
		
		$machineUtilisation = MasterData::where('type','machine_nonutilisation')
								->where('is_active',1)->get();
		if ($machineUtilisation) {
			$machineUtil['form'] = 'machine_nonutilisation';
			$machineUtil['field'] = 'machineNonUtilisation';
			$machineUtil['data'] = $machineUtilisation;

			array_push($resultData,$machineUtil);
			unset($machineUtil);

		}
		$ManufactureYearData  = MachineManufactureYear::where('is_active','1')->get();   
		//range( date("Y") , 2000 );
			if ($ManufactureYearData) {
			$yearData['form'] = 'machine_mou';
			$yearData['field'] = 'manufactured_year';
			$yearData['data'] = $ManufactureYearData;
			
			array_push($resultData,$yearData);
			unset($yearData);
		}	

	 

		// if (count($resultData) == 0) {		
		// 	return response()->json([
		// 		'code'=>403,	
		// 		'status' => 403,
		// 		//'data' => $resultData,
		// 		'message' => 'No data available'
		// 	]);			
		// }
		return response()->json([
			'code'=>200,	
            'status' => 200,
            'data' => $resultData,
            'message' => 'Getting all master data for structure'
        ]);
	}	
	
	//get Feedlsit from DB
	public function getStructureList(Request $request) {	
		
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
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
			
		$userLocation = $user['location'];
		$database = $this->connectTenantDatabase($request,$org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 403,
									'code'=>403,			
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.']
									 );
		}
		//$data = json_decode(file_get_contents('php://input'), true);

		$request = json_decode(file_get_contents('php://input'), true);
		
		$approverRoleConfig = \App\RoleConfig::where('approver_role', $user->role_id)
		->select('jurisdiction_type_id','level','role_id')
		->get();
		
		if (empty($approverRoleConfig)) {
			
			return response()->json(['status' => 403,
									'code'=>403,			
									'data' => '', 
									'message' => 'Role missing']);
            
			$this->logData($this->logInfoPah,$this->request, 'DB');
		
		}	
		//$levelDetail = \App\Jurisdiction::where('_id',$approverRoleConfig[0]['level'])->get();	
			
		//print_r($user->location);exit;
		//$district = $user->location['district'];			
		$query =  Structure::where(['is_active'=>1])
							->with('departmentName')
							->with('subDepartmentName')
							->with('workType')
							->with('structureType')
							->with('structureMachine')
							->with('State')
							->with('District')
							->with('Taluka');		

		if (isset($request['type']) &&  $request['type'] == 'prepared') {
			$query->where(['status_code'=> '116']);			
		}

		if (isset($request['type']) && $request['type'] == 'machineDeployableStructures') {
			$query->whereIn('status_code',['117','116']);			
		}

		if (isset($request['type']) && $request['type'] == 'machineShiftStructures') {
			
			$query->whereNotIn('_id', [$request['structure_id']]);

		}
		//if (strtolower($levelDetail[0]['levelName']) == 'district') {
			
			$query->where('district_id',$request['district_id']);
			
		//}
		if (isset($request['taluka_id'])) {	
			$query->where('taluka_id',$request['taluka_id']);		
		}
				
		//$query->where('taluka_id',$request['taluka_id']);
		
		if (isset($request['village_id'])) {	
			$query->where('village_id',$request['village_id']);		
		}
		$structureList = $query->orderBy('name', 'ASC')
						->get();	
		
		$result = $structureList->toArray();
		$finalData = [];
		foreach ($result as $data) {
			$resultData = [];
			$resultData['structureId'] = $data['_id'];
			$resultData['structureName'] = $data['title']??'';
			$resultData['structureCode'] = $data['code'];
			$resultData['structureWorkType'] = $data['work_type']['value'];
			$resultData['structureStatus'] = $data['status'];
			$resultData['structureStatusCode'] = (int)$data['status_code']??'';
			
			$resultData['updatedDate'] = date('d M Y g:i a', strtotime($data['updated_at']));					
			$machinIds = [];
			
			foreach ($data['structure_machine'] as $machineData) {				
				$machinIds[] = $machineData['machine_id'];			
			}
			$str = '';
			if (!empty($machinIds)) {
				$machineData = Machine::whereIn('_id',$machinIds)->get()->toArray();
				
				foreach ($machineData as $machineData) {
					if($str == '') {
						$str = $machineData['machine_code'];
					} else {
						$str = $str .','.$machineData['machine_code'];
					}		
				}	
	
			}
			if (isset($data['lat'])) {
				$resultData['lat'] = $data['lat'];
			}
			
			if (isset($data['long'])) {
				$resultData['long'] = $data['long'];
			}
			
			$resultData['structureDepartmentName'] = $data['department_name']['value'];
			$resultData['structureSubDepartmentName'] = $data['sub_department_name']['value'];
			$resultData['structureType'] = $data['structure_type']['value'];
			$resultData['state'] = $data['state']['name'];
			$resultData['stateId'] = $data['state']['_id'];
			$resultData['district'] = $data['district']['name'];
			$resultData['districtId'] = $data['district']['_id'];
			$resultData['taluka'] = $data['taluka']['name'];
			$resultData['talukaId'] = $data['taluka']['_id'];		
			$resultData['structureMachineList'] = $str;			
			$finalData[] = $resultData;
		
		}
		
		if (count($finalData) == 0) {		
			return response()->json([
				'code'=>403,	
				'status' => 403,
				//'data' => $resultData,
				'message' => 'No data available'
			],200);			
		}
		
		$responsData = array('code'=>200,	
            'status' => 200,
            'data' => $finalData,
            'message' => 'Getting a list of all structures');
			
		return response()->json($responsData,200);	
	}

	//get Feedlsit from DB
	public function getStructureAnalyst(Request $request) 
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
		
		//$this->logData($this->logInfoPah,$request, 'DB');
			
		$user = $this->request->user();		
		$userLocation = $user['location'];
		$database = $this->connectTenantDatabase($request,$org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		//$data = json_decode(file_get_contents('php://input'), true);
		
		/*$approverRoleConfig = \App\RoleConfig::where('approver_role', $role_id )
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
		$district = $user->location['district'];*/
		
		
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
						->get();*/	
		$approvedCnt =  Structure::where(['is_active'=>1, 'status_code' =>'115'])->count();
		$wipCnt =  Structure::where(['is_active'=>1, 'status_code' =>'117'])->count();
		$haltedCnt =  Structure::where(['is_active'=>1, 'status_code' =>'118'])->count();
		$completeCnt =  Structure::where(['is_active'=>1, 'status_code' =>'120'])->count();
		
		$strData =  Structure::where(['is_active'=>1])->select('_id')->get()->toArray();
		//print_r($strData);exit;	
		
		$result = array_map(function($arr) {
				return $arr['_id'];
		}, $strData);
		//print_r($result);exit;

		$siltCount = MachineSiltDetails::whereIn('structure_id',$result)->sum('tractor_trip_number');
		$siltCount1 = MachineSiltDetails::whereIn('structure_id',$result)->sum('tipper_trip_number');
		
		$finalSiltCnt = $siltCount+$siltCount1;
		$finalSiltCnt = $this->thousandsCurrencyFormat($finalSiltCnt);
		
		$waterStorageCnt =  Structure::where(['is_active'=>1])->sum('sow.water_storage');
		$waterStorageCnt = $this->thousandsCurrencyFormat($waterStorageCnt);
		
		$resultData = [];
		//please do not change below format : changes done as per andorid team requirement
		$resultData[0]['Title'] = 'Silt Excvated';
		$resultData[0]['value'] = $finalSiltCnt;
		$resultData[0]['unit'] = '(Cubic mtr)';
		
		$resultData[1]['Title'] = 'Water Storage Capacity  Increased by';
		$resultData[1]['value'] = $waterStorageCnt;
		$resultData[1]['unit'] = '(Ltr)';
		
		$resultData[2]['percentValue'] = $approvedCnt;
		$resultData[2]['status'] = 'Approved';

		$resultData[3]['percentValue'] = $wipCnt;
		$resultData[3]['status'] = 'Work in progress';

		$resultData[4]['percentValue'] = $haltedCnt;
		$resultData[4]['status'] = 'Halted';

		$resultData[5]['percentValue'] = $completeCnt;
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
	
	function thousandsCurrencyFormat($num) {

		if ($num > 1000) {

			$x = round($num);
			$x_number_format = number_format($x);
			$x_array = explode(',', $x_number_format);
			$x_parts = array('k', 'Lak', 'Crore', 't');
			$x_count_parts = count($x_array) - 1;
			$x_display = $x;
			$x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
			$x_display .= $x_parts[$x_count_parts - 1];

			return $x_display;

		}
		return $num;
	}
	
	//structure prepared from DB
	public function saveStructurePreparedData(Request $request) 
	{	
		/*
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
		*/
		//$user = $this->request->user();
		//$this->request->user_id = $user->_id;
		
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		//echo '<pre>';print_r($this->request->all());exit;
		
		if (!$this->request->has('formData')) {
			$error = array('status' =>400,
							'message' => 'Formdata field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}

		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		
		//echo '<pre>';print_r($requestJson);exit;
		//var_dump($requestJson);exit;
		
		if (!isset($requestJson->structure_id)) {
			$error = array('status' =>400,
							'message' => 'Structure id missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}

		/*if ($requestJson->ff_identified == true) {
			
			if ($this->request->has('ff_name')) {
			
			
			}
			
			if ($this->request->has('ff_mb_number')) {
			
			
			}
			
			if ($this->request->has('ff_traning_done')) {
				
			
			}

		}*/

		if (!isset($requestJson->is_structure_fit)) {
			$error = array('status' =>400,
							'message' => 'Is structure fit field missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
			
		}
		
		$isStructureFit = false;
		
		if ($requestJson->is_structure_fit) {
			
			$isStructureFit = true;
		}	
			
		//$user = $this->request->user();		
		//$userLocation = $user['location'];
		$database = $this->connectTenantDatabase($request,'5c1b940ad503a31f360e1252');		
		
		if ($database === null) {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.']);
		}
		
		$strData =  Structure::where(['_id'=>$requestJson->structure_id])
							->first();
							
		if (!$strData) {
			$error = array('status' =>400,
							'message' => 'Invalid structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}

		$strData =  StructurePreparation::where(['structure_id'=>$requestJson->structure_id])
							->first();
							
		if ($strData) {
			$error = array('status' =>400,
							'message' => 'You  have already prepared structure',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}
		//echo "er werwr";exit;
		$urls = [];
		if ($this->request->has('imageArraySize')) {
			for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {
					
				$fileName = 'Structure'.$cnt; 		
				//echo$this->request['imageArraySize']. "--erwerw r";exit;
				if ($this->request->file($fileName)->isValid()) {
				//echo "we  qweqeeqwe";exit;
					$fileInstance = $this->request->file($fileName);
					$name = $fileInstance->getClientOriginalName();
					$ext = $this->request->file($fileName)->getClientMimeType(); 
					
					$newName = uniqid().'_'.$name.'.jpg';
					$s3Path = $this->request->file($fileName)->storePubliclyAs('staging/structure/forms', $newName, 'octopusS3');
					
					$urls[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/staging/structure/forms/' . $newName;
				}
			}
		}
		//print_r($urls);exit;
		$strPreObj = new StructurePreparation;
		$strPreObj->structure_id = $requestJson->structure_id;
		$strPreObj->ff_identified = $requestJson->ff_identified;
		$strPreObj->preparaion_structure_images = $urls; 

		if ($requestJson->ff_identified == true) {

			$strPreObj->ff_name  = $requestJson->ff_name;
			$strPreObj->ff_mobile_number =$requestJson->ff_mobile_number;
			$strPreObj->ff_traning_done = $requestJson->ff_traning_done ? true: false;
		
		}
		$strPreObj->is_structure_fit = $isStructureFit;
		$this->request->status = 'prepared';
		$this->request->status_code = '116';
		$this->request['structure_id'] = 	$requestJson->structure_id;
		
		if (!$isStructureFit) {
			$strPreObj->reason = $requestJson->reason;
			$this->request->status = 'non-compliant';
			$this->request->status_code = '122';			
		}
		//$strPreObj->created_by = $user['id'];
		try {
			
			//$strPreObj->save();
			
			$strPreObj->lat = $requestJson->lat;
			$strPreObj->long = $requestJson->long;
			$strPreObj->save();
			
			$this->request->action = 'Preparation Checklist';
			$this->request->user_id = "213123121";
			
			$this->structureUpdateStatus($this->request);
			
			//send notification
			/*	$roleArr = array('110','111','115');
				$params['org_id'] = $user->org_id;
				$params['request_type'] =  self::NOTIFICATION_STRUCTURE_APPROVED;
				$params['update_status'] = 'Structure Prepared';				
				$params['code'] = $strData->code;
				
				$params['stateId'] = $strData->state_id;
				$params['districtId'] = $strData->district_id;
				$params['talukaId'] = $strData->taluka_id;		
				$this->sendSSNotification($this->request,$params, $roleArr);			
				
			*/
			$success = array('status' =>200,
							'code' => 200,
							'message' => 'Structure prepared successfully'							
							);				
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
				
			return response()->json($success);

		} catch (Exception $e){
			
			$error = array('status' =>400,
							'message' => 'Some error has occured.Please try again',							
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
							'message' => 'Invalid structure id',							
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
			$success = array('status' =>200,
							'message' => 'Structure status updated successfully',							
							'code' => 200
							);				
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
			
			return true;
		} catch (Exception $e){
			
			$error = array('status' =>400,
							'message' => 'Some error has occured.Please try again',							
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
							'message' => 'Structure log created successfully',							
							);				
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
			
			return true;
		} catch (Exception $e){
			
			$error = array('status' =>400,
							'message' => 'Some error has occured.Please try again',							
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
		
		if ($database === null) {
			return response()->json(['status' =>403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		if (!isset($requestJson['structure_id'])) {
			$error = array('status' =>400,
							'message' => 'Structure id missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		$strData =  Structure::where(['_id'=>$this->request['structure_id']])
							->first();
							
		if (!$strData) {
			$error = array('status' =>400,
							'message' => 'Invalid structure id',							
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
		
		//validate structure id
		if (!$this->request->has('structure_id')) {
			$error = array('status' =>400,
							'message' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		if (!$this->request->has('machine_id')) {
			$error = array('status' =>400,
							'message' => 'Machine id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($request,$org_id);		
		
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
							'message' => 'Invalid structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}	
		
		//check machine id exist or not
		$machineData = Machine::where('_id',$requestJson['machine_id'])->first();
		
		if (!$machineData) {
			$error = array('status' =>400,
							'message' => 'Invalid machine id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);

		}
		
		$mappingCnt = StructureMachineMapping::where(['structure_id'=> $requestJson['structure_id'],
													'machine_id'=>$requestJson['machine_id'],
													'status'=>'deployed'])
													->count();
		
		if ($mappingCnt > 0) {
			$error = array('status' =>400,
							'message' => 'Machine already deployed on structure',							
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
			
			$this->machineStatusLog($this->request,$machineData->code, '107');
			
			
			//send notification
			/*	$roleArr = array('110','111','114');
				$params['org_id'] = $org_id;
				$params['request_type'] =  self::NOTIFICATION_MACHINE_DEPLOYED;
				$params['update_status'] = 'Machine Deployed';				
				$params['struture_code'] = $strData->code;
				$params['machine_code'] = $machineData->code;
				
				$params['stateId'] = $machineData->state_id;
				$params['districtId'] = $machineData->district_id;
				$params['talukaId'] = $machineData->taluka_id;		
				$this->sendSSNotification($this->request,$params, $roleArr);			
				
			*/
			//update machine status
					
			$success = array('status' =>200,
							'code' => 200,
							'message' => 'Machine deployed successfully'						
							);				
			$this->logData($this->logInfoPah,$this->request->all(),'DB',$success);
			return response()->json($success);
			//return true;
		} catch (Exception $e){
			
			$error = array('status' =>400,
							'message' => 'Some error has occured.Please try again',							
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
	public function machineStatusLog($request,$machineCode, $statusCode = '') {	
		
		$machineLog =  new MachineLog;
		
		$machineLog['code'] = $machineCode;
		$machineLog['action_title'] = $this->request->action;
		$machineLog['machine_id'] = $this->request['machine_id'];
		
		if ($this->request->status == 'deployed') {
			
			$machineLog['structure_id'] = $this->request['structure_id'];			
		}
		$machineLog['action_by'] = $this->request->user_id;
		$machineLog['status'] = $this->request->status;
		$machineLog['status_code'] = $statusCode;
		
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
	
	/*
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
		*/
		//$user = $this->request->user();
		//$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		
		//validate structure id
		if (!isset($requestJson->structure_id)) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($request,'5c1b940ad503a31f360e1252');
		
		//$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
				
		//check structure  id exist or not
		$strData =  Structure::where(['_id'=>$requestJson->structure_id])
							->first();
							
		if (!$strData) {
			$error = array('status' =>400,
							'msg' => 'Invalid structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}

		$urls = [];
		if ($this->request->has('imageArraySize')) {
			for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {
					
				$fileName = 'Structure'.$cnt; 		
				//echo$this->request['imageArraySize']. "--erwerw r";exit;
				if ($this->request->file($fileName)->isValid()) {
				//echo "we  qweqeeqwe";exit;
					$fileInstance = $this->request->file($fileName);
					$name = $fileInstance->getClientOriginalName();
					$ext = $this->request->file($fileName)->getClientMimeType(); 
					
					$newName = uniqid().'_'.$name.'.jpg';
					$s3Path = $this->request->file($fileName)->storePubliclyAs('staging/structure/forms', $newName, 'octopusS3');
					
					$urls[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/staging/structure/forms/' . $newName;
				}
			}
		}
		$strData->status = 'partially-completed';
		$strData->status_code = '119';
		
		//update status and uplaod certificate
		if ($strData->is_completed == 'true') {
			$strData->status = 'closed';
			$strData->status_code = '121';		
		}	
		$strData->certificates = $urls;		
		//$strData->updated_by = $this->request->user_id;
		//$strData->save();

		try {
			$strData->save();
			
			$this->request->status = 'closed';
			$this->request->status_code = '121';
			$this->request->action = 'Structure closed';			
			$this->request['structure_id'] = $requestJson->structure_id;
			
			$this->request->user_id = 'adasd';
			$this->structureLog($this->request);
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Status closed Successfully.");
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

	public function machineDailyWorkDetails(Request $request) {

		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate structure id
		if (!$this->request->has('machine_id')) {
			$error = array('status' =>400,
							'message' => 'Machine id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($request,$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.']);
		}
		
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		//check machine id exist or not
		$machineData =  Machine::where(['_id'=>$requestJson['machine_id']])
							->first();
							
		if (!$machineData) {
			$error = array('status' =>400,
							'message' => 'Invalid machine id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}
	}

	
	public function machineDieselRecord(Request $request) {



/*
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
		*/
		//$user = $this->request->user();
		//$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		$requestDataJson = json_decode(file_get_contents('php://input'), true);
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		
		/*$database = $this->connectTenantDatabase($request,'5c1b940ad503a31f360e1252');
		
		if ($database === null) {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}*/
		
		$dieselImage = 0;
		$registerImage = 0;
		
		$dieselImageUrl = [];
		$registerImageUrl = [];
		//echo "test";exit;
		for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {			
			
			$fileName = 'diesel'.$dieselImage;
				
			if ($this->request->has($fileName)) {
				
				if ($this->request->file($fileName)->isValid()) {
			
					$fileInstance = $this->request->file($fileName);
				
					$name = $fileInstance->getClientOriginalName();
					$ext = $this->request->file($fileName)->getClientMimeType(); 
					$newName = uniqid().'_'.$name.'.jpg';
					$s3Path = $this->request->file($fileName)->storePubliclyAs('staging/machine/forms', $newName, 'octopusS3');
					
					$dieselImageUrl[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/staging/machine/forms/' . $newName;
				}
				$dieselImage++;	
			}
			
			$fileName = 'register'.$registerImage;
				
			if ($this->request->has($fileName)) {				
				
					if ($this->request->file($fileName)->isValid()) {
				
						$fileInstance = $this->request->file($fileName);
					
						$name = $fileInstance->getClientOriginalName();
						//$ext = $this->request->file($fileName)->getClientMimeType(); 
						
						$newName = uniqid().'_'.$name.'.jpg';
						$s3Path = $this->request->file($fileName)->storePubliclyAs('staging/machine/forms', $newName, 'octopusS3');
						
						$registerImageUrl[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/staging/machine/forms/' . $newName;
						
					}					
				$registerImage++;			
			}			
					
		}
		/*
		print_r($registerImageUrl);
		print_r($dieselImageUrl);
		exit;*/
		
		$database = $this->connectTenantDatabase($this->request,'5c1b940ad503a31f360e1252');
		
		
		//$user->org_id);		
		
		if ($database === null) {
			
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		foreach ($requestJson as  $data) {
		
		//validate structure id
			if (!$data->structure_id) {
				$error = array('status' =>400,
								'msg' => 'Structure id is missing',							
								'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
								
				return response()->json($error);			
			}
			
			//validate machine id
			if (!$data->machine_id) {
				$error = array('status' =>400,
								'msg' => 'Machine id is missing',							
								'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
								
				return response()->json($error);			
			}
			
			if (!$data->diesel_quantity_ltr) {
				$error = array('status' =>400,
								'msg' => 'Diesel quantity is missing',							
								'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
								
				return response()->json($error);			
			}
			
			/*if (!$data->date) {
				$error = array('status' =>400,
								'msg' => 'Recevied date is missing',							
								'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
								
				return response()->json($error);			
			}*/

					
			$machineDieselRecordObj = new MachineDieselRecord;
			//var_dump($machineDieselRecordObj);exit;
			$machineDieselRecordObj->structure_id = $data->structure_id;
			$machineDieselRecordObj->machine_id = $data->machine_id;
			$machineDieselRecordObj->diesel_quantity_ltr =  $data->diesel_quantity_ltr;
			$machineDieselRecordObj->filling_date =  $data->date;
			$machineDieselRecordObj->diesel_receipt_image = $dieselImageUrl;
			$machineDieselRecordObj->diesel_register_image = $registerImageUrl;
			//$machineDieselRecordObj->created_by = $this->request->user_id;

			try { 
			
				$machineDieselRecordObj->save();
				
				$responseData = array( 'code'=>200,
										'status' =>200,
										'message'=>"Machine diesel details saved successfully."
										);
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);
				
			} catch(Exception $e) {
				echo $e->getMessage();exit;
				$error = array('code'=>400,
								'status' =>400,
								'message'=>'Some error has occured .Please try again',
										);
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
				
			}	
				
		}		
		$responseData = array( 'code'=>200,
									'status' =>200,
									'message'=>"Machine diesel details saved successfully."
									);
		//	$this->logData($this->logInfoPah,$this->request->all(),'Error',$responseData);
							
		return response()->json($responseData,200);							
		
	}
	
	/** get all prepared and in-progress structure for deplyed/shifting machine
	*
	*
	*
	*/
	public function  machineShifting(Request $request) {
		
		
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
		
		//validate current structure id
		if (!$this->request->has('current_structure_id')) {
			$error = array('status' =>400,
							'message' => 'Current Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate New structure id
		if (!$this->request->has('new_structure_id')) {
			$error = array('status' =>400,
							'message' => 'New Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate structure id
		if (!$this->request->has('machine_id')) {
			$error = array('status' =>400,
							'message' => 'Machine id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		
		//validate structure id
		if (!$this->request->has('meter_reading')) {
			$error = array('status' =>400,
							'message' => 'Meter reading field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate travel distance id
		if (!$this->request->has('travel_distance')) {
			$error = array('status' =>400,
							'message' => 'Travel distance field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate travel time id
		if (!$this->request->has('travel_time')) {
			$error = array('status' =>400,
							'message' => 'Travel time field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($this->request,$org_id);		
		
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
							'message' => 'Machine already shifted',							
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
		$mshifting->is_diesel_filled = $this->request->has('is_diesel_filled') ? $this->request['is_diesel_filled']  : 'No';
		$mshifting->diesel_filled_quantity = $this->request->has('diesel_filled_quantity')? $this->request['diesel_filled_quantity'] : '';
		$mshifting->created_by = $this->request->user_id;	
		
		try {
			$mshifting->save();	

			//update previous mapping status to shifted
			$strObj = StructureMachineMapping::where(['structure_id' => $this->request['current_structure_id'],
													  'machine_id'=> $this->request['machine_id']])
													  ->first();			
			if ($strObj) {
				$strObj->status = 'closed';
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
			$machineData->status_code = "107";
			$machineData->save();
			
			//insert into machine log
			$this->machineStatusLog($this->request,$machineData->code, '107');
			
			
			/*	$roleArr = array('110','111');
					$params['org_id'] = $org_id;
					$params['request_type'] =  self::NOTIFICATION_MACHINE_SHIFTED;
					$params['update_status'] = 'Machine shifted';				
					$params['machine_code'] = $machineData->code;
					
					$currStrObj = Structure::where("_id",$this->request['current_structure_id'])->first();
					$newStrObj = Structure::where("_id",$this->request['new_structure_id'])->first();
				
					$params['current_structure_code'] = $currStrObj->code;
					$params['new_structure_code'] = $newStrObj->code;
					
					$params['stateId'] = $machineData->state_id;
					$params['districtId'] = $machineData->district_id;
					$params['talukaId'] = $machineData->taluka_id;
					
					$this->sendSSNotification($this->request,$params, $roleArr);			
					
				*/
		
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
	
		
	public function siltDetails (Request $request) {

		/*
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
		*/
		//$user = $this->request->user();
		//$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');
		$requestDataJson = json_decode(file_get_contents('php://input'), true);
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
			
		
		//validate structure id
		if (!$requestJson->structure_id) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate structure id
		if (!$requestJson->machine_id) {
			$error = array('status' =>400,
							'msg' => 'Machine id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($this->request,'5c1b940ad503a31f360e1252');
		
		//$user->org_id);		
		
		if ($database === null) {
			
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}

		$siltRegisterImage = [];
		
		for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {
				
			$fileName = 'register'.$cnt;
			$imageData = $this->imageUpload($fileName , env('AWS_MACHINE_FORM_IMAGE'), $this->request);			
			//echo $imageData."-----";exit;
			if ($imageData != '') {
			
				/*$fileInstance = $this->request->file($fileName);
				$name = $fileInstance->getClientOriginalName();
				$ext = $this->request->file($fileName)->getClientMimeType(); 
				//echo $ext;exit;
				$newName = uniqid().'_'.$name.'.jpg';
				$s3Path = $this->request->file($fileName)->storePubliclyAs('staging/machine/forms', $newName, 'octopusS3');
				*/
				$siltRegisterImage[] = $imageData;
				//'https://' . env('OCT_AWS_CDN_PATH') . '/staging/machine/forms/' . $newName;
			}
		}
		
		$this->request['beneficiaries_count']=  $this->request->has('beneficiaries_count') ? $this->request['beneficiaries_count'] :0;
		$this->request['farmer_count'] = $this->request->has('farmer_count') ? $this->request['farmer_count'] : 0;
		$this->request['tractor_trips'] = ($this->request->has('tractor_trips')) ? $this->request['tractor_trips'] : 0;
		$this->request['tipper_trips'] = $this->request->has('tipper_trips') ? $this->request['tipper_trips'] :0;
		
		$machineSiltObj = new MachineSiltDetails;
		$machineSiltObj->machine_id = $requestJson->machine_id;
		$machineSiltObj->structure_id = $requestJson->structure_id;
		$machineSiltObj->transport_date = (int)$requestJson->transport_date;
		$machineSiltObj->tractor_trip_number = (int) $requestJson->tractor_trips;
		$machineSiltObj->tipper_trip_number = (int) $requestJson->tipper_trips;
		$machineSiltObj->farmer_count = (int) $requestJson->farmer_count;
		$machineSiltObj->beneficiaries_count = (int)$requestJson->beneficiaries_count;
		$machineSiltObj->silt_register_image = $siltRegisterImage;
		//$machineSiltObj->created_by = $this->request->user_id;

		try {
			$machineSiltObj->save();
			
			$responseData = array( 'code'=>200,
									'status' =>200,
									'message'=>"Silt details saved successfully."
									);
			$this->logData($this->logInfoPah,
							$this->request->all(),
							'DB',
							$responseData
							);
							
			return response()->json($responseData,200);
			
		} catch(Exception $e) {
			
			$error = array('code'=>400,
							'status' =>400,
							'message'=>'Some error has occured .Please try again',
									);
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
			
			return response()->json($error);
		}		
		
	}
	
	/**
	* Validate machine daily work data
	* @params object $request
	* return json responseData
	*/	
	public function machineVisit(Request $request) {
		
		/*
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
		*/
		//$user = $this->request->user();
		//$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		$requestDataJson = json_decode(file_get_contents('php://input'), true);
		//$temp = '{"machine_id":"5d84a3a9a2680b2358c69c64","date":231897132,"is_validate":true}';


		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		//var_dump($requestJson);exit;
		/*echo '<pre>';
		print_r($requestJson);
		exit;*/
		//echo $requestJson->machine_id;exit;
		//validate structure id
		$database = $this->connectTenantDatabase($request,'5c1b940ad503a31f360e1252');
		
		if ($database === null) {
			return response()->json(['status' => 403, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		$url = [];
		for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {
				
			$fileName = 'image'.$cnt; 		
			
			if ($this->request->file($fileName)->isValid()) {
			
				$fileInstance = $this->request->file($fileName);
				$name = $fileInstance->getClientOriginalName();
				$ext = $this->request->file($fileName)->getClientMimeType(); 
				//echo $ext;exit;
				$newName = uniqid().'_'.$name.'.jpg';
				$s3Path = $this->request->file($fileName)->storePubliclyAs('staging/machine/forms', $newName, 'octopusS3');
				
				//if ($s3Path == null || !$s3Path) {
					//return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
				//}
				$url[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/staging/machine/forms/' . $newName;
				//return response()->json(['status' => 'success', 'data' => ['url' => $result], 'message' => 'Image successfully uploaded in S3']);
			}
		}
		
		foreach ($requestJson as  $data) { 
		
			if (!$data->machine_id) {
				$error = array('status' =>400,
								'msg' => 'Machine id is missing',							
								'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
								
				return response()->json($error);			
			}
			
			$validate = false;
			if ($data->is_validate  == 'true') {
				$validate = true;
			}
			$values=array('is_validate'=>$validate,'register_image'=>$url);

			try {
				$machieData = MachineDailyWorkRecord::where(['machine_id'=>$data->machine_id])
											->update($values);
											//->first();
			
				
				$responseData = array('code'=>200,
										'status' =>200,
										'message'=>"Machine  visit saved successfully.");
										
				$this->logData($this->logInfoPah,$this->request->all(),'DB',$responseData);										
				
			} catch(Exception $e) {				
				
				$error = array('code'=>400,
										'status' =>400,
										'message'=>'Some error has occured .Please try again',
										);
				$this->logData($this->logInfoPah,$this->request->all(),'Error',$error);	
					
				return response()->json($error);								
			}
		}
		$responseData = array('code'=>200,
							'status' =>200,
							'message'=>"Machine  visit saved successfully.");
						
		return response()->json($responseData,200);
	}
	
	/**
	*
	*
	*
	*/
	public function createStructure(Request $request) {
		
		
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
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$requestJson = json_decode(file_get_contents('php://input'), true);
		
		//validate structure title 
		if (!isset($requestJson['name'])) {

			$error = array('status' =>400,
							'msg' => 'Structure name is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		//validate State id
		if (!isset($requestJson['state_id'])) {
			$error = array('status' =>400,
							'msg' => 'State id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		//validate District id
		if (!isset($requestJson['district_id'])) {

			$error = array('status' =>400,
							'msg' => 'District id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		if (!isset($requestJson['taluka_id'])) {

			$error = array('status' =>400,
							'msg' => 'Taluka id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		if (!isset($requestJson['department_id'])) {

			$error = array('status' =>400,
							'msg' => 'Department id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		if (!isset($requestJson['sub_department_id'])) {

			$error = array('status' =>400,
							'msg' => 'Sub Department id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		if (!isset($requestJson['village'])) {

			$error = array('status' =>400,
							'msg' => 'Host village field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		if (!isset($requestJson['village_population'])) {

			$error = array('status' =>400,
							'msg' => 'Host village population field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		
		/*if (!isset($requestJson['catchment_village'])) {

			$error = array('status' =>400,
							'msg' => 'Catchment village field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		if (!isset($requestJson['catchment_village_popullation'])) {

			$error = array('status' =>400,
							'msg' => 'Catchment village field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}
		
		
		if (!isset($requestJson['catchment_village_popullation'])) {

			$error = array('status' =>400,
							'msg' => 'Catchment village field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);						
		}*/
		
		$created_at = (new \MongoDB\BSON\UTCDateTime(Carbon::now()) ? : '' ); 
      
		$getlastStructure = Structure::all()->last();
		$lastStructureCode = (explode("-",  $getlastStructure['code'])[1]);
       	
		//host village 
		$host_village['host_village'] =  $requestJson['village'] ? $requestJson['village']  : '';
		$host_village['village_id'] =  $requestJson['village_id'] ? $requestJson['village_id']  : '';
		$host_village['population'] = $requestJson['village_population'] ? $requestJson['village_population']  : '';
		
		//sow 
		$sow['water_storage'] = isset($requestJson['water_storage']) ? $requestJson['water_storage']  : '';
		$sow['technical_section_number'] =isset( $requestJson['technical_section_number']) ? $requestJson['technical_section_number']  : '';
		$sow['technical_section_date'] = isset($requestJson['technical_section_date']) ? $requestJson['technical_section_date']  : '';
		$sow['administrative_estimate_amount'] = isset($requestJson['administrative_estimate_amount']) ? $requestJson['administrative_estimate_amount']  : '';
		$sow['apprx_working_hrs'] = isset($requestJson['apprx_working_hrs']) ? $requestJson['apprx_working_hrs']  : '';
		$sow['apprx_diesel_consumption_lt'] = isset($requestJson['apprx_diesel_consumption_lt']) ? $requestJson['apprx_diesel_consumption_lt']  : '';		
		$sow['apprx_diesel_consumption_rs'] = isset($requestJson['apprx_diesel_consumption_rs']) ? $requestJson['apprx_diesel_consumption_rs']  : '';
		$sow['apprx_estimate_qunty'] = isset($requestJson['apprx_estimate_qunty']) ? $requestJson['apprx_estimate_qunty']  : '';
		$sow['remark'] = isset($requestJson['remark']) ? $requestJson['remark']  : '';
		$sow['administrative_approval_no'] = isset($requestJson['administrativeApprovalNo']) ? $requestJson['administrativeApprovalNo']  : '';
		$sow['administrative_approval_date'] = isset($requestJson['administrativeApprovalDate']) ? $requestJson['administrativeApprovalDate']  : '';
		
		//$resultData['host_village']= $host_village;                        
        //$resultData['sow']= $sow;
		$catchmentVillages = isset($requestJson['catchment_villages']) ? $requestJson['catchment_villages']  : '';
		$cVillage = explode(',',$catchmentVillages);
		
		$catchmentVillagesIds = isset($requestJson['catchment_villages_ids']) ? $requestJson['catchment_villages_ids']  : '';
		$cVillageIds = explode(',',$catchmentVillagesIds);
		
		$catArr = [];
		
		foreach ($cVillage as $key=>$data) {
			
			$catArr[$key]['id'] = $data;		
		}	
		
		foreach ($cVillageIds as $key=>$data) {
			
			$catArr[$key]['name'] = $data;		
		}
			
		//print_r($catArr);exit;
		/*$strCnt = Structure::where('name' ,$requestJson['name'])->count();
		echo $strCnt;exit;
		if ($strCnt > 0) {
			$error = array('status' =>400,
							'msg' => 'Structure name is duplicate',							
							'code' => 400);						
			$this->logData($this->errorPath,$requestJson,'Error',$error);
			
			return response()->json($error);
			
		}*/
		//echo "<pre>";print_r($getlastStructure);exit;
		 $resultData = [
						'title' => $requestJson['name'],
						'code' => 'SBJS-'.$lastStructureCode,
						'project_id' =>$project_id,
						'state_id' => $requestJson['state_id'],
						'district_id' => $requestJson['district_id'],
						'taluka_id' => $requestJson['taluka_id'],
						'village_id' =>isset( $requestJson['village_id']) ? $requestJson['village_id'] : '',
						'department_id' =>isset(  $requestJson['department_id']) ? $requestJson['department_id'] : '',
						'sub_department_id' => isset($requestJson['sub_department_id']) ? $requestJson['sub_department_id'] : '', 
						'host_village' => $host_village,
						'type_id' => isset( $requestJson['structure_type']) ? $requestJson['structure_type'] : '',
						'catchment_villages'=>$catArr,
						'work_type' => isset($requestJson['work_type']) ?  $requestJson['work_type'] : '',
						'total_population' =>isset( $requestJson['total_population'] )? $requestJson['total_population'] : '',
						'sow' => $sow,
						'lat' => isset($requestJson['lat']) ? $requestJson['lat']  : '',
						'long' => isset($requestJson['log']) ? $requestJson['log']  : '',
						'ff_id' => isset($requestJson['ff_id']) ? $requestJson['ff_id']  : '',
						
						'nota_detail' => isset($requestJson['notaDetail']) ? $requestJson['notaDetail']  : '',
						'water_shed_no' => isset($requestJson['waterShedNo']) ? $requestJson['waterShedNo']  : '',
						'gat_no' => isset($requestJson['gatNo']) ? $requestJson['gatNo']  : '',
						'area' => isset($requestJson['area']) ? $requestJson['area']  : '',			 	
						
						'created_by' => $user->id,
						'updated_by' => $user->id,
						'is_active' => 1,
						'status'=>'approved',
						'status_code'=> '115',
						'created_at' => $created_at,
						'updated_at' => $created_at
                    ];
		try {
				DB::table('structure')->insert($resultData);
				
				//send notification
				$roleArr = array('110','111','115');
				$params['org_id'] = $user->org_id;
				$params['request_type'] =  self::NOTIFICATION_STRUCTURE_APPROVED;
				$params['update_status'] = 'Structure Prepared';				
				$params['code'] = 'SBJS-'.$lastStructureCode;
				
				//get Taluka name
				$talukaName = \App\Taluka::find($requestJson['taluka_id']);
				$this->request->talukaName = $talukaName->name;
				
				//get village name
				$villageName = \App\Village::find($requestJson['village_id']);
				$this->request->villageName = $villageName->name;				
				
				$params['stateId'] = $requestJson['state_id'];
				$params['districtId'] = $requestJson['district_id'];
				$params['talukaId'] = $requestJson['taluka_id'];		
				$this->sendSSNotification($this->request,$params, $roleArr);			
					
				$responseData = array('code'=>200,
										'status' =>200,
										'message'=>"Structure created successfully.");									
				
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
	
	//community mobilisation
	public function communityMobilisation (Request $request) {
		
		/*
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
		*/
		//$user = $this->request->user();
		//$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');

		//$requestJson = json_decode(file_get_contents('php://input'), true);
		
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
				
		
		//validate structure id
		if (!isset($requestJson->structure_id)) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate structure id
		//if (!$this->request->has('activity_code')) {
		if (!isset($requestJson->activity_code)) {	
			$error = array('status' =>400,
							'msg' => 'Activity code field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate structure id
		if (!isset($requestJson->activity_name)) {
		//if (!$this->request->has('activity_name')) {
			$error = array('status' =>400,
							'msg' => 'Activity name field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		//validate structure id
		//if (!$this->request->has('task')) {
		if (!isset($requestJson->task)) {	
			$error = array('status' =>400,
							'msg' => 'Task field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}		
		$database = $this->connectTenantDatabase($request,"5c1b940ad503a31f360e1252");
		
		//$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		
		$cmMobilisationObj = StructureCommunityMobilisation::where('structure_id', $requestJson->structure_id)
										->first();
		
		
		if ($cmMobilisationObj) {
			//$cmMobilisationObj->updated_by = $user->_id;		
		} else {			
			$cmMobilisationObj = new StructureCommunityMobilisation;
			$cmMobilisationObj->structure_id = $requestJson->structure_id;
			$cmMobilisationObj->activity_code = $requestJson->activity_code;
			$cmMobilisationObj->task = isset($requestJson->task) ? $requestJson->task : '';
			$cmMobilisationObj->activity_name = $requestJson->activity_name;
			//$cmMobilisationObj->created_by = $user->_id;			
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
					$s3Path = $this->request->file($fileName)->storePubliclyAs('staging/structure/forms', $newName, 'octopusS3');
					
					//if ($s3Path == null || !$s3Path) {
						//return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
					//}
					$url[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/staging/structure/forms/' . $newName;
					//return response()->json(['status' => 'success', 'data' => ['url' => $result], 'message' => 'Image successfully uploaded in S3']);
				}
			}
		}
			
		$data= [];
		//entry level activity 
		if ((int)$requestJson->activity_code == 1) {
			
			$data['meeting_date'] = isset($requestJson->date) ? $requestJson->date : '';
			$data['village_id'] = isset($requestJson->village_id) ? $requestJson->village_id : '';			
			$data['grampanchayat_name'] = isset($requestJson->grampanchayat_name) ? $requestJson->grampanchayat_name : '' ;			
			$data['no_participant'] = isset($requestJson->no_participant) ? $requestJson->no_participant : '';			
			$data['sarpanch_name'] = isset($requestJson->sarpanch_name) ? $requestJson->sarpanch_name : '';			
			$data['sarpanch_phone_no'] = isset($requestJson->sarpanch_phone_no) ? $requestJson->sarpanch_phone_no : '';
			$data['oopsarpanch_name'] = isset($requestJson->oopsarpanch_name) ? $requestJson->oopsarpanch_name : '';
			$data['oopsarpanch_phone_no'] = isset($requestJson->oopsarpanch_phone_no) ? $requestJson->oopsarpanch_phone_no : '';
			$cmMobilisationObj->entry_level_activity = $data;
			
		} else if ((int)$requestJson->activity_code == 2) {
			
			$data['village_id'] = isset($requestJson->village_id) ? $requestJson->date : '';								
			$data['grampanchayat_name'] = isset($requestJson->grampanchayat_name) ? $requestJson->grampanchayat_name : '';
			$data['no_participant'] = isset($requestJson->no_participant) ? $requestJson->no_participant : '';
			$data['sarpanch_name'] = isset($requestJson->sarpanch_name) ? $requestJson->sarpanch_name : '';
			$data['date'] = isset($requestJson->date) ? $requestJson->date : '';
			
			$cmMobilisationObj->community_sensitisation = $data;
						
		} else if ((int)$requestJson->activity_code == 3) {
			
			$data['leader_name'] = isset($requestJson->leader_name) ? $requestJson->leader_name : '';
			$data['member_name'] = isset($requestJson->member_name) ? $requestJson->member_name : '';
			$data['leader_phone_no'] = isset($requestJson->leader_phone_no) ? $requestJson->leader_phone_no : '';
			$data['education'] = isset($requestJson->education) ? $requestJson->education : '';
			$data['occupation'] = isset($requestJson->occupation) ? $requestJson->occupation : '';
			$data['formation_date'] = isset($requestJson->formation_date) ? $requestJson->formation_date : '';
			$data['media_image'] = $url;			
			$cmMobilisationObj->formation_meeting = $data;
			
		} else if ((int)$requestJson->activity_code == 4) {	
			
			$data['topic_name'] = isset($requestJson->topic_name) ? $requestJson->topic_name : '';
			$data['topic_date'] = isset($requestJson->topic_date) ? $requestJson->topic_date : '';
			$data['participant_name'] = isset($requestJson->participant_name) ? $requestJson->participant_name : '';
			$data['duration'] = isset($requestJson->duration) ? $requestJson->duration : '';			
			$data['media_image'] = $url;		
						
			$cmMobilisationObj->task_force_traning = $data;
			
		} else if ($requestJson->activity_code == 5) {
			
			$data['village_id'] = isset($requestJson->village_id) ? $requestJson->village_id : '';
			$data['department_name'] = isset($requestJson->department_name) ? $requestJson->department_name : '';
			$data['org_date'] = isset($requestJson->date) ? $requestJson->date : '';
			$data['former_name'] = isset($requestJson->former_name) ? $requestJson->former_name : '';
			$data['former_phone_no'] = isset($requestJson->former_phone_no) ? $requestJson->former_phone_no : '';
			$data['former_land_holding'] = isset($requestJson->former_land_holding) ? $requestJson->former_land_holding : '';
			
			$data['program_image'] = $requestJson['program_image'];			
			$cmMobilisationObj->orientation = $data;
		}
		
		try {
			$cmMobilisationObj->save();	
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Structure data saved successfully.");
									
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
	
	public function structureVisit(Request $request) {
		
		
		/*$header = getallheaders();
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
		*/
		//$user = $this->request->user();
		//$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPah,$this->request->all(),'DB');		
		
		//validate structure id
		
		$database = $this->connectTenantDatabase($request,"5c1b940ad503a31f360e1252");
		
		//$user->org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 400, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		//$requestJson = json_decode(file_get_contents('php://input'), true);
		if (!$this->request->has('imageArraySize')) {
			$error = array('status' =>400,
							'msg' => 'Form data field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		
		}
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		
		if (!isset($requestJson->structure_id)) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
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
					$s3Path = $this->request->file($fileName)->storePubliclyAs('staging/structure/forms', $newName, 'octopusS3');
					
					//if ($s3Path == null || !$s3Path) {
						//return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
					//}
					$url[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/staging/structure/forms/' . $newName;
					//return response()->json(['status' => 'success', 'data' => ['url' => $result], 'message' => 'Image successfully uploaded in S3']);
				}
			}
		}
//echo "data test";exit;		
		$structureData = new StructureVisit;
		//::where('_id',$requestJson['structure_id'])->first();
		$structureData->structure_id = $requestJson->structure_id;							
		$structureData->is_safety_signage = isset($requestJson->is_safety_signage) ? $requestJson->is_safety_signage : '';							
		$structureData->is_guidelines_followed = isset($requestJson->is_guidelines_followed) ? $requestJson->is_guidelines_followed : "";
		
		//$structureData->structure_photos = $requestJson['images'];
		$structureData->status_record_id = isset($requestJson->status_record_id) ? $requestJson->status_record_id : '';
		$structureData->issue_related_id = isset($requestJson->issue_related_id) ? $requestJson->issue_related : '';
		$structureData->issue_description = isset($requestJson->issue_description) ? $requestJson->issue_description: '';
		$structureData->image_url =  $url;
		
		try {
			$structureData->save();	
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Structure visit data saved successfully.");
									
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

	public function roleAccess(Request $request) {
		
		
		$user = $this->request->user();
		
		/*echo '<pre>';
		print_r($user->role_id);
		exit;*/
		
		//$database = $this->connectTenantDatabase($request,$user->org_id);		
		$this->request->userId =  $user->id;
		
		$rolename = \App\Role::select('display_name')->where("_id",$user->role_id)->first();
		$resultData = [];
		
		if ($user->phone == '7387825838') {
			$resultData['role_code'] = 111;
			$resultData['role_name'] = $rolename->display_name;
			$role_access[0] = array ('action_code'=> 100, 'action_name'=> 'Add Structure');
			$role_access[1] = array ('action_code'=> 101, 'action_name'=> 'View Structure');
			$role_access[2] = array ('action_code'=> 102, 'action_name'=> 'Save Offine Structure');
			$role_access[3] = array ('action_code'=> 103, 'action_name'=> 'Prepared Structure');
			$role_access[4] = array ('action_code'=> 104, 'action_name'=> 'Machine Deployed');
			$role_access[5] = array ('action_code'=> 105, 'action_name'=> 'Communication Mobilisation');
			$role_access[6] = array ('action_code'=> 106, 'action_name'=> 'Struture visit & monitoring record');
			$role_access[7] = array ('action_code'=> 107, 'action_name'=> 'Structure Complete');
			$role_access[8] = array ('action_code'=> 114, 'action_name'=> 'Machine Deploy');
			$role_access[9] = array ('action_code'=> 115, 'action_name'=> 'Machine Visit & Validation of Working Hours Record');			
			$role_access[10] = array ('action_code'=> 116, 'action_name'=> 'Silt Transportation Record');	
			//$role_access[10] = array ('action_code'=> 116, 'action_name'=> 'Machine Non-Utilization');	
			$role_access[10] = array ('action_code'=> 117, 'action_name'=> 'Record of Diesel Received');
			$role_access[11] = array ('action_code'=> 118, 'action_name'=> 'Machine Shifting');			
			$role_access[12] = array ('action_code'=> 119, 'action_name'=> 'Machine free From Taluka');			
			$role_access[13] = array ('action_code'=> 123, 'action_name'=> 'Changes Village');


			$resultData['role_access'] = $role_access;
		
		return response()->json([
			'code'=>200,	
            'status' => 200,
            'data' => $resultData,
            'message' => 'Role access data'
        ]);
	
		
			
			
			
		}
		//echo $rolename->display_name;exit;
		
		if (strtolower($rolename->display_name)  == 'district manager') {

			$resultData['role_code'] = 110;
			$resultData['role_name'] = $rolename->display_name;
			//$role_access[0] = array ('action_code'=> 100, 'action_name'=> 'Add Structure');
			$role_access[0] = array ('action_code'=> 101, 'action_name'=> 'View Structure');
			/*$role_access[2] = array ('action_code'=> 102, 'action_name'=> 'Save Offine Structure');
			$role_access[3] = array ('action_code'=> 103, 'action_name'=> 'Prepared Structure');
			$role_access[4] = array ('action_code'=> 104, 'action_name'=> 'Machine Deployed');
			$role_access[5] = array ('action_code'=> 105, 'action_name'=> 'Communication Mobilisation');
			$role_access[6] = array ('action_code'=> 106, 'action_name'=> 'Struture visit & monitoring record');
			$role_access[7] = array ('action_code'=> 107, 'action_name'=> 'Structure Complete');*/
			$role_access[1] = array ('action_code'=> 108, 'action_name'=> 'Add Machine');
			$role_access[2] = array ('action_code'=> 109, 'action_name'=> 'View Machine');
			$role_access[3] = array ('action_code'=> 110, 'action_name'=> 'MOU Machine');
			$role_access[4] = array ('action_code'=> 111, 'action_name'=> 'Machine Eligible');
			$role_access[5] = array ('action_code'=> 112, 'action_name'=> 'Machine MOU Termnated');
			$role_access[6] = array ('action_code'=> 113, 'action_name'=> 'Machine Available');
			$role_access[7] = array ('action_code'=> 122, 'action_name'=> 'Change Taluka');		
			$role_access[8] = array ('action_code'=> 123, 'action_name'=> 'Change Village');
			
			
			
		} else if (strtolower($rolename->display_name)  == 'taluka coordinator' || strtolower($rolename->display_name)  == 'district manager') {
			
			$resultData['role_code'] = 111;
			$resultData['role_name'] = $rolename->display_name;
			$role_access[0] = array ('action_code'=> 100, 'action_name'=> 'Add Structure');
			$role_access[1] = array ('action_code'=> 101, 'action_name'=> 'View Structure');
			$role_access[2] = array ('action_code'=> 102, 'action_name'=> 'Save Offine Structure');
			$role_access[3] = array ('action_code'=> 103, 'action_name'=> 'Prepared Structure');
			$role_access[4] = array ('action_code'=> 104, 'action_name'=> 'Machine Deployed');
			$role_access[5] = array ('action_code'=> 105, 'action_name'=> 'Communication Mobilisation');
			$role_access[6] = array ('action_code'=> 106, 'action_name'=> 'Struture visit & monitoring record');
			$role_access[7] = array ('action_code'=> 107, 'action_name'=> 'Structure Complete');
			$role_access[8] = array ('action_code'=> 114, 'action_name'=> 'Machine Deploy');
			$role_access[9] = array ('action_code'=> 115, 'action_name'=> 'Machine Visit & Validation of Working Hours Record');			
			$role_access[10] = array ('action_code'=> 116, 'action_name'=> 'Silt Transportation Record');	
			//$role_access[10] = array ('action_code'=> 116, 'action_name'=> 'Machine Non-Utilization');	
			$role_access[10] = array ('action_code'=> 117, 'action_name'=> 'Record of Diesel Received');
			$role_access[11] = array ('action_code'=> 118, 'action_name'=> 'Machine Shifting');			
			$role_access[12] = array ('action_code'=> 119, 'action_name'=> 'Machine free From Taluka');			
			$role_access[13] = array ('action_code'=> 123, 'action_name'=> 'Changes Village');			
			$role_access[14] = array ('action_code'=> 109, 'action_name'=> 'View Machine');
		
		} else if (strtolower($rolename->display_name)  == 'ho ops') {
			
			$resultData['role_code'] = 112;
			$resultData['role_name'] = $rolename->display_name;
			$role_access[0] = array ('action_code'=> 100, 'action_name'=> 'Add Structure');
			$role_access[1] = array ('action_code'=> 101, 'action_name'=> 'View Structure');
			$role_access[2] = array ('action_code'=> 102, 'action_name'=> 'Save Offine Structure');
			$role_access[3] = array ('action_code'=> 103, 'action_name'=> 'Prepared Structure');
			$role_access[4] = array ('action_code'=> 104, 'action_name'=> 'Machine Deployed');
			$role_access[5] = array ('action_code'=> 105, 'action_name'=> 'Communication Mobilisation');
			$role_access[6] = array ('action_code'=> 106, 'action_name'=> 'Struture visit & monitoring record');
			$role_access[7] = array ('action_code'=> 107, 'action_name'=> 'Structure Complete');
			$role_access[8] = array ('action_code'=> 108, 'action_name'=> 'Add Machine');
			$role_access[9] = array ('action_code'=> 109, 'action_name'=> 'View Machine');
			$role_access[10] = array ('action_code'=> 110, 'action_name'=> 'MOU Machine');
			$role_access[11] = array ('action_code'=> 111, 'action_name'=> 'Machine Eligible');
			$role_access[12] = array ('action_code'=> 112, 'action_name'=> 'Machine MOU Termnated');
			$role_access[13] = array ('action_code'=> 113, 'action_name'=> 'Machine Available');
			$role_access[14] = array ('action_code'=> 114, 'action_name'=> 'Machine Deploy');
			$role_access[15] = array ('action_code'=> 115, 'action_name'=> 'Machine Visit & Validation of Working Hours Record');			
			$role_access[16] = array ('action_code'=> 116, 'action_name'=> 'Silt Transportation Record');	
			$role_access[17] = array ('action_code'=> 117, 'action_name'=> 'Record of Diesel Received');	
			$role_access[18] = array ('action_code'=> 118, 'action_name'=> 'Machine Shifting');		
			$role_access[19] = array ('action_code'=> 119, 'action_name'=> 'Machine free From Taluka');			
			$role_access[20] = array ('action_code'=> 120, 'action_name'=> 'Change State');		
			$role_access[21] = array ('action_code'=> 121, 'action_name'=> 'Change District');
			$role_access[22] = array ('action_code'=> 122, 'action_name'=> 'Change Taluka');		
			$role_access[23] = array ('action_code'=> 123, 'action_name'=> 'Change Village');			
					
				
		
		
		}
		$resultData['role_access'] = $role_access;
		
		return response()->json([
			'code'=>200,	
            'status' => 200,
            'data' => $resultData,
            'message' => 'Role access data'
        ]);
	}

	public function sendSSNotification($request,$params, $roleArr) {
		
		$logInfoPath = "logs/Structure/DB/Notification/logs_".date('Y-m-d').'.log';
		$errorPath = "logs/Structure/Error/Notification/logs_".date('Y-m-d').'.log';

		//$stateId = $params['stateId'];
		$districtId = $params['districtId'];
		$talukaId = $params['talukaId'];
		$villageId = isset($params['villageId']) ? $params['villageId'] : '';
		
		//loop for role
		foreach ($roleArr as  $roleCode) {
		
			DB::setDefaultConnection('mongodb');
		
			$roleData = \App\Role::where('role_code', $roleCode)->first();
			
			if (!$roleData) {
				$responseData = array( 'code'=>400,
									   'status' =>'error',
									   'roleCode'=>$roleCode,									  
									   'structureCode'=>$params['code'],
									   'message'=> 'Role missing in role collection');									
				
				$this->logData($errorPath,$params,'Error',$responseData);
				
				return true;
					
			}	
			$query = \App\User::where(['role_id' => $roleData->_id,
									   'location.district' => $districtId]);
			if ($roleCode == '111') {
				$query->where(['location.taluka' => $talukaId]);
			}

			if ($roleCode == '114') {
				$query->where(['location.village' => $villageId]);
			}			
			$userDetails = $query->select('firebase_id','name','phone')
										->get()->toArray();

			foreach($userDetails as $userData) {
				//echo "rwerewr".$userData['firebase_id'];
				if (!isset($userData['firebase_id'])) {
					
					$responseData = array( 'code'=>400,
									   'status' =>'error',
									   'roleCode'=>$roleCode,									  
									   'structureCode'=>$params['code'],
									   'message' => 'firebase_id missing in User collection');									
				
				$this->logData($errorPath,$params,'Error',$responseData);
					
					return true;				
				}
				$dataD = $this->sendPushNotification(
					$request,
					$params['request_type'],
					$userData['firebase_id'],
					[ 
						'phone'=> $userData['phone'],
						'update_status' => $params['update_status'],						
						'rolename' => $roleData->display_name,
						'code'=> $params['code']
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

	public  function getCathmentVillages(Request $request) {
		
		
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
		if (!isset($requestJson['structure_id'])) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}	
		
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 400, 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$strData = Structure::find($requestJson['structure_id']);
		if (empty($strData)) {
			
			$error = array('status' =>400,
							'msg' => 'Invalid Structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);	
		}	
		
		return response()->json([
			'code'=>200,	
            'status' => 200,
            'data' => $strData->catchment_villages,
            'message' => 'Village list '
        ]);

		
	}
 	
	
}
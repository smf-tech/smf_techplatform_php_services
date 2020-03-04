<?php
/**
 * Structure Master Class
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
use App\Jobs\DataQueue;
date_default_timezone_set('Asia/Kolkata'); 

class StructureController extends Controller
{

    use Helpers;

    protected $request;
	

    public function __construct(Request $request) {
		
        $this->request = $request;
		$this->logInfoPath = "logs/Structure/DB/logs_".date('Y-m-d').'.log';
		$this->errorPath = "logs/Structure/Error/logs_".date('Y-m-d').'.log';

    }

    //get structure master data
    public function getStructureMasterData(Request $request) {	
		
		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$orgId =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		} else {
			
			$message = "Header info missing";
			$this->logData($this->logInfoPath ,$message,'Error');
			
			$response_data = array('status' =>'404',
									'message'=>$message
									);
			
			return response()->json($response_data,200);			
		}
		$user = $this->request->user();	
		$this->request->user_id = $user->_id;
		$this->logData($this->logInfoPath,$this->request->all(),'DB');

		$database = $this->connectTenantDatabase($request,$orgId);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}

		$resultData= array();
		$finalData = array();

		$machineMakeDate = \App\MachineMakeMaster::where('is_active', 1)
							->orderBy('value', 'ASC')
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
		
		
		$machineTypeData = MasterData::where('type','machine_type')
								->where('is_active',1)
								->orderBy('value', 'ASC')
								->get();

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
								->where('is_active',1)
								->orderBy('value', 'ASC')
								->get();
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
								->where('is_active',1)
								->orderBy('value', 'ASC')
								->get();
			if($accountTypeData)
			{
				$accountType['form'] = 'machine_create';
				$accountType['field'] = 'accountType';
				$accountType['data'] = $accountTypeData;

				array_push($resultData,$accountType);
				unset($accountType);

			}					

		$ownedByData = MasterData::where('type','owned_by')
								->where('is_active',1)
								->orderBy('value', 'ASC')
								->get();
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
								->where('is_active',1)
								->orderBy('value', 'ASC')
								->get();
								
		if ($dieselProvidedByData) {
			$dieselProvidedBy['form'] = 'shifting';
			$dieselProvidedBy['field'] = 'dieselProvidedBy';
			$dieselProvidedBy['data'] = $dieselProvidedByData;

			array_push($resultData,$dieselProvidedBy);
			unset($dieselProvidedBy);

		}							

		$structureTypeData = StructureType::where('is_active',1)
		->orderBy('value', 'ASC')
		->get();

		if ($structureTypeData) {
			$structureType['form'] = 'structure_create';
			$structureType['field'] = 'structureType';
			$structureType['data'] = $structureTypeData;

			array_push($resultData,$structureType);
			unset($structureType);

		}		
		
		$structureDeptData = StructureDepartment::where('is_active',1)
		->orderBy('value', 'ASC')->get();

		if ($structureDeptData) {
			$structureDept['form'] = 'structure_create';
			$structureDept['field'] = 'structureDept';
			$structureDept['data'] = $structureDeptData;

			array_push($resultData,$structureDept);
			unset($structureDept);

		}							
		
		$structureSubDeptData = StructureSubDepartment::where('is_active',1)
		->orderBy('value', 'ASC')		
		->get();

		if ($structureSubDeptData) {
			$structureSubDept['form'] = 'structure_create';
			$structureSubDept['field'] = 'structureSubDept';
			$structureSubDept['data'] = $structureSubDeptData;

			array_push($resultData,$structureSubDept);
			unset($structureSubDept);

		}

		$provideByData = MasterData::where('type','diesel_provided_by')
								->orderBy('value', 'ASC')
								->where('is_active',1)->get();
		if ($provideByData) {
			$provideBy['form'] = 'machine_shifting';
			$provideBy['field'] = 'providedBy';
			$provideBy['data'] = $provideByData;

			array_push($resultData,$provideBy);
			unset($provideBy);

		}
		
		$machineUtilisation = MasterData::where('type','machine_nonutilisation')
								->where('is_active',1)
								->orderBy('value', 'ASC')
								->get();
		if ($machineUtilisation) {
			$machineUtil['form'] = 'machine_nonutilisation';
			$machineUtil['field'] = 'machineNonUtilisation';
			$machineUtil['data'] = $machineUtilisation;

			array_push($resultData,$machineUtil);
			unset($machineUtil);

		}
		$ManufactureYearData  = MachineManufactureYear::where('is_active','1')
		->orderBy('value', 'ASC')
		->get();   
		//range( date("Y") , 2000 );
		if ($ManufactureYearData) {
			$yearData['form'] = 'machine_mou';
			$yearData['field'] = 'manufactured_year';
			$yearData['data'] = $ManufactureYearData;
			
			array_push($resultData,$yearData);
			unset($yearData);
		}

		$workTypeData = MasterData::where('type','work_type')
								->select('_id','value')
								->where('is_active',1)
								->orderBy('value', 'ASC')
								->get();					
		if ($workTypeData) {
			//$resultData['workType'] = $workTypeData;
			$workType['form'] = 'structure_create';
			$workType['field'] = 'work_type';
			$workType['data'] = $workTypeData;

			array_push($resultData,$workType);
			unset($workType);
		}
		
		$interventionData = MasterData::where('type','intervention')
								->select('_id','value','type_code')
								->where('is_active',1)
								//->orderBy('value', 'ASC')
								->get();					
		if ($interventionData) {
			//$resultData['workType'] = $workTypeData;
			$intervention['form'] = 'structure_create';
			$intervention['field'] = 'intervention';
			$intervention['data'] = $interventionData;

			array_push($resultData,$intervention);
			unset($workType);
		}
		
		$structureTypeData = StructureType::where(['is_active'=>1,
											'type_code'=>1])
		->select('_id','value')
		->orderBy('value', 'ASC')
		->get();
		
		if ($structureTypeData) {
			$structureType['form'] = 'structure_create';
			$structureType['field'] = 'structureType';
			$structureType['structureTypeCode'] = 1;
			$structureType['data'] = $structureTypeData;
			
			array_push($resultData,$structureType);
			unset($structureType);

		}
		
		$structureTypeData = StructureType::where(['is_active'=>1,
											'type_code'=>2])
		->select('_id','value')
		->orderBy('value', 'ASC')
		->get();
		
		if ($structureTypeData) {
			$structureType['form'] = 'structure_create';
			$structureType['field'] = 'structureType';
			$structureType['structureTypeCode'] = 2;
			$structureType['data'] = $structureTypeData;
			array_push($resultData,$structureType);
			unset($structureType);

		}
		
		
		$structureTypeData = StructureType::where(['is_active'=>1,
											'type_code'=>3])
		->select('_id','value')
		->orderBy('value', 'ASC')
		->get();
		
		if ($structureTypeData) {
			$structureType['form'] = 'structure_create';
			$structureType['field'] = 'structureType';
			$structureType['structureTypeCode'] = 3;
			$structureType['data'] = $structureTypeData;
			
			array_push($resultData,$structureType);
			unset($structureType);

		}
		
		
		$structureTypeData = StructureType::where(['is_active'=>1,
											'type_code'=>4])
		->select('_id','value')
		->orderBy('value', 'ASC')
		->get();
		
		if ($structureTypeData) {
			$structureType['form'] = 'structure_create';
			$structureType['field'] = 'structureType';
			$structureType['structureTypeCode'] = 4;
			$structureType['data'] = $structureTypeData;		

			array_push($resultData,$structureType);
			unset($structureType);

		}
		
		
		$structureBeneficiary = MasterData::where('type','beneficiary')
								->where('is_active',1)
								->orderBy('value', 'ASC')
								->get();
		if ($structureBeneficiary) {
			$structureBeneficiaryData['form'] = 'structure_preparation';
			$structureBeneficiaryData['field'] = 'structureBeneficiary';
			$structureBeneficiaryData['data'] = $structureBeneficiary;

			array_push($resultData,$structureBeneficiaryData);
			unset($structureBeneficiaryData);

		}
		
		return response()->json([
			'code'=>200,	
            'status' => 200,
            'data' => $resultData,
            'message' => 'Getting all master data for structure'
        ]);
	}	
	
	/** 
	* get all prject structure list  from DB
	* @param object $request
	* @return json data
	* 
	*/
	public function getStructureList(Request $request) {	
		
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
		
		$user = $this->request->user();	
		$this->request->user_id = $user->_id;
		$this->logData($this->logInfoPath,$this->request->all(),'DB');		
			
		//$userLocation = $user['location'];
		$database = $this->connectTenantDatabase($request,$orgId);		
		
		if ($database === null) {
			return response()->json(['status' => 403,
									'code'=>403,			
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.']
									 );
		}
		
		$request = json_decode(file_get_contents('php://input'), true);		
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
		
		if (count($approverRoleConfig) == 0) {
			
			return response()->json([
				'code'=>400,	
				'status' => 400,
				'message' => 'Invalid role Id'
			],200);						
		
		}		
		$levelDetail = \App\Jurisdiction::where('_id',$approverRoleConfig[0]['level'])->get();	
		
		$query =  Structure::where(['is_active'=>1])
							->with('departmentName')
							->with('subDepartmentName')
							->with('workType')
							->with('structureType')
							->with('structureMachine')
							->with('State')
							->with('District')
							->with('Taluka')
							->with('Village')
							->where(['project_id'=> $projectId]);	

		if (isset($request['type']) &&  $request['type'] == 'prepared') {
			$query->where(['status_code'=> '116']);			
		}

		if (isset($request['type']) && $request['type'] == 'machineDeployableStructures') {
			$query->whereIn('status_code',['117','116']);			
		}

		if (isset($request['type']) && $request['type'] == 'machineShiftStructures') {
			$query->whereIn('status_code',['117','116']);
			$query->whereNotIn('_id', [$request['structure_id']]);

		}
		
		if (isset($request['state_id'])) {
			$state = explode(',',$request['state_id']); 
				
			$query->whereIn('state_id',$state);
			
		}
		
		if (isset($request['district_id'])) {
			$district = explode(',',$request['district_id']); 
			
			$query->whereIn('district_id',$district);
			
		}
		
		if (isset($request['taluka_id'])) {
			$taluka = explode(',',$request['taluka_id']); 
			
			$query->whereIn('taluka_id',$taluka);
			
		}
		
		if (strtolower($levelDetail[0]['levelName']) == 'state') {
			
			$query->whereIn('state_id',$userLocation['state']);
		}
		
		if (strtolower($levelDetail[0]['levelName']) == 'taluka') {
			
			$query->whereIn('taluka_id',$userLocation['taluka']);
		}

		if (strtolower($levelDetail[0]['levelName']) == 'district') {
			
			$query->whereIn('district_id',$userLocation['district']);			
		}			
		
		if (isset($request['village_id'])) {	
			$query->where('village_id',$request['village_id']);		
		}
		$structureList = $query->orderBy('created_at', 'DESC')
						->get();	
		
		$result = $structureList->toArray();
		$finalData = [];
		
		foreach ($result as $data) {
			$resultData = [];
			$resultData['structureId'] = $data['_id'];
			$resultData['structureName'] = $data['title']??'';
			$resultData['structureCode'] = $data['code'];
			$resultData['structureWorkType'] = $data['work_type']['value'];
			$resultData['structureStatus'] = ucfirst ($data['status']);
			$resultData['structureStatusCode'] = (int)$data['status_code']??'';
			
			$resultData['updatedDate'] = date('d M Y g:i a', strtotime($data['updated_at']));					
			$machinIds = [];
			$deployedCnt = 0;
			$workStartDate = '';
			
			if ($data['status_code'] == '119' || $data['status_code'] == '120') {
				$mapData = StructureMachineMapping::where(['structure_id'=> $data['_id']])
													->select('_id','created_at')->first();
					
					$workStartDate = $mapData['created_at'];
				
			}	
			foreach ($data['structure_machine'] as $key=>$machineData) {				
				$machinIds[] = $machineData['machine_id'];
				
				//structure start date
				if ($key == 0) {
					$workStartDate = $machineData['created_at'];
				}
				
				if ($machineData['status'] == 'deployed') {
					$deployedCnt++;
				}	
			}
			$machineDetails = [];
			$str = '';
			if (!empty($machinIds)) {
				$machineData = Machine::whereIn('_id',$machinIds)->get()->toArray();
				
				
				foreach ($machineData as $key=>$machineData) {
					$machineDetails[$key]['code'] = $machineData['machine_code'];
					$machineDetails[$key]['status'] = $machineData['status'];
					$machineDetails[$key]['machineUpdatedDate'] =  date('d M Y g:i a', strtotime($machineData['updated_at']));;
										
					if($str == '') {
						$str = $machineData['machine_code'];
					} else {
						$str = $str .','.$machineData['machine_code'];
					}		
				}	
	
			}
			//print_r($machineDetails);exit;
			if (isset($data['lat'])) {
				$resultData['lat'] = $data['lat'];
			}
			
			if (isset($data['long'])) {
				$resultData['long'] = $data['long'];
			}
			
			$resultData['structureBoundary'] = false;
			
			if (isset($data['structure_boundary'])) {
				$resultData['structureBoundary'] = true;
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
			$resultData['village'] = $data['village']['name'];			
			$resultData['structureMachineList'] = $str;
			$resultData['deployedMachineDetails']  = $machineDetails;
			$resultData['isStructureComplete'] = true;
			//$resultData['workStartDate'] = date('d M Y g:i a', strtotime($data['updated_at']));
			
			if ($workStartDate != '') {
				$resultData['workStartDate'] = date('d M Y g:i a', strtotime($workStartDate));
			}
			
			if (isset($data['closed_date'])) {
				
				$resultData['workCompletedDate'] = date('d M Y g:i a', strtotime($data['closed_date']));
			}
			
			
			if ($deployedCnt > 0 ) {
				
				$resultData['isStructureComplete'] = false;

			}	
			$finalData[] = $resultData;
		
		}
		
		if (count($finalData) == 0) {		
			return response()->json([
				'code'=>400,	
				'status' => 400,				
				'message' => 'No data available'
			],200);			
		}
		
		$responsData = array('code'=>200,	
            'status' => 200,
            'data' => $finalData,
            'message' => 'Getting a list of all structures');
			
		return response()->json($responsData,200);	
	}

	/** 
	* get all prject structure analytics  from DB
	* @param object $request
	* @return json data
	* 
	*/
	public function getStructureAnalyst(Request $request) {
		
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
			
		$user = $this->request->user();		
		//$userLocation = $user['location'];
		$database = $this->connectTenantDatabase($request,$orgId);		
		
		if ($database === null) {
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
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
		
		$statusCode = \App\StatusCode::where(['type'=>'structure'])->get();
		
		$resultData = [];
		$query =  Structure::where(['is_active'=>1, 
							'project_id'=> $projectId]
							);
	if ($this->request->state_id == "" && $this->request->district_id == "" && $this->request->taluka_id == "")	
		
	{
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
			
		//echo $levelDetail[0]['levelName'];exit;
		if (strtolower($levelDetail[0]['levelName']) == 'taluka') {
		
			if (isset($userLocation['taluka'])) {
				$query->whereIn('taluka_id',$userLocation['taluka']);
			}
		
		}
		
		if (strtolower($levelDetail[0]['levelName']) == 'village') {
			
			if (isset($userLocation['village'])) {

				$query->where('village_id',$userLocation['village'][0]);
			}
		
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
		
		$sCount = $query->count();
		
		if ($sCount > 0) {
			$resultData[0]['percentValue'] = $sCount;
			$resultData[0]['status'] = 'Total Structure';
			$resultData[0]['statusCode'] = 0;
		}
		
		$cnt = 1;
		foreach ($statusCode as $data) {		
			
			$query =  Structure::where(['is_active'=>1, 'status_code' =>$data['status_code'], 'project_id'=> $projectId]);
					
		if ($this->request->state_id == "" && $this->request->district_id == "" && $this->request->taluka_id == "")	
		{
		
			
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
			
			if (strtolower($levelDetail[0]['levelName']) == 'village') {
				
				if (isset($userLocation['village'])) {

					$query->where('village_id',$userLocation['village'][0]);
				}
			
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
		
		
		
		
		
		
		
		
			$approvedCnt = $query->count();
			$statusName = $data['status_name'];
			
			if ($approvedCnt > 0) {				
				$resultData[$cnt]['percentValue'] = $approvedCnt;
				$resultData[$cnt]['status'] = $statusName;
				$resultData[$cnt]['statusCode'] = $data['status_code'];
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
            'status' => 200,
            'data' => array_values($resultData),
            'message' => 'Structure analytics data'
        ]);
		
	}
	
	/**
	* Function for convert type
	* @prams integer $num 
	* @return integer $num
	*/
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
	
	/**
	* Structure prepared with notification
	* @prams object $request 
	* @return integer $num
	*/
	public function saveStructurePreparedData(Request $request) 
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
		
		$user = $this->request->user();
		$this->request['user_id'] = $user->_id;
		
		$this->logData($this->logInfoPath,$this->request->all(),'DB');		
		
		if (!$this->request->has('formData')) {
			$error = array('status' =>400,
							'message' => 'Formdata field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		//echo '<pre>';print_r($this->request->all());exit;
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		
		
		if (!isset($requestJson->structure_id)) {
			$error = array('status' =>400,
							'message' => 'Structure id missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
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
		$database = $this->connectTenantDatabase($request,$orgId);		
		
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
		
		if ($strData->status_code != '122') {
			$strDataPr =  StructurePreparation::where(['structure_id'=>$requestJson->structure_id])
							->first();
							
			if ($strDataPr) {
				$error = array('status' =>400,
								'message' => 'You  have already prepared structure',							
								'code' => 400);						
				$this->logData($this->errorPath,$this->request->all(),'Error',$error);
								
				return response()->json($error);

			}
		}
		
		/*$urls = [];
		if ($this->request->has('imageArraySize')) {
			for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {
					
				$fileName = 'Structure'.$cnt; 		
				
				if ($this->request->file($fileName)->isValid()) {
				
					$fileInstance = $this->request->file($fileName);
					$name = $fileInstance->getClientOriginalName();
					$ext = $this->request->file($fileName)->getClientMimeType(); 
					
					$newName = uniqid().'_'.$name.'.jpg';
					$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_STRUCTURE'), $newName, 'octopusS3');
					
					$urls[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_STRUCTURE').'/' . $newName;
				}
			}
		}*/
		
		$strPreObj = StructurePreparation::where('structure_id',$requestJson->structure_id)->first();
		
		if ($strPreObj) {
			
		} else {
			//print_r($urls);exit;
			$strPreObj = new StructurePreparation;
			$strPreObj->project_id = $projectId;
			$strPreObj->structure_id = $requestJson->structure_id;
		}
		$strPreObj->ff_identified = $requestJson->ff_identified;
		
		if ($requestJson->ff_identified == true) {

			$strPreObj->ff_name  = $requestJson->ff_name;
			$strPreObj->ff_mobile_number =$requestJson->ff_mobile_number;
			$strPreObj->ff_traning_done = $requestJson->ff_traning_done ? true: false;
		
		}
		$statusCode = \App\StatusCode::where(['status_code'=>'116', 'type'=>'structure'])->first();
		$this->request->status = $statusCode['status_name'];
		$this->request->status_code = $statusCode['status_code'];
							
		$strPreObj->is_structure_fit = $isStructureFit;	
		$this->request['structure_id'] = 	$requestJson->structure_id;
		
		$params['request_type'] =  self::NOTIFICATION_STRUCTURE_PREPARED;
		$params['update_status'] = 'Structure Prepared';
		
		if (!$isStructureFit) {
			$strPreObj->reason = $requestJson->reason;
			
			$statusCode = \App\StatusCode::where(['status_code'=>'122', 'type'=>'structure'])->first();
			$this->request->status = $statusCode['status_name'];
			$this->request->status_code = $statusCode['status_code'];
					
			$params['request_type'] =  self::NOTIFICATION_STRUCTURE_NONCOMPLAINT;
			$params['update_status'] = 'Non Compliant';							
		}
		$strPreObj->created_by = $user->id;
		$strPreObj->project_id = $projectId;
		try {
			$strPreObj->lat = (float) $requestJson->lat;
			$strPreObj->long = (float) $requestJson->long;
			$strPreObj->beneficiary_id = isset($requestJson->beneficiary_id) ? $requestJson->beneficiary_id : "";
			$strPreObj->save();
			
			$this->request['structurePreparationId']= $strPreObj->id;
			
			
			$this->request->action = 'Preparation Checklist';						
			$this->structureUpdateStatus($this->request);
			//org_id
			$this->request['functionName'] = __FUNCTION__;		
			
			//send notification
			$roleArr = array('110','111','112','115');
			$params['org_id'] =  $orgId;
			$params['projectId'] =  $projectId;
			
			$params['code'] = $strData->code;			
			$params['stateId'] = $strData->state_id;
			$params['districtId'] = $strData->district_id;
			$params['talukaId'] = $strData->taluka_id;
			$params['reason'] = isset($requestJson->reason) ? $requestJson->reason :'';
			
			//update long and lat
			$strData->lat = (float) $requestJson->lat;
			$strData->long = (float) $requestJson->long;
			$strData->save();
			
			$params['modelName'] = 'Structure';	
			$this->request['params'] =  $params;
			
			//$this->sendSSNotification($this->request,$params, $roleArr);
			$this->request['roleArr'] = $roleArr;
			
			dispatch((new DataQueue($this->request)));			
		
			$success = array('status' =>200,
							'code' => 200,
							'message' => 'Structure prepared successfully'							
							);				
			$this->logData($this->logInfoPath,$this->request->all(),'DB',$success);
				
			return response()->json($success);

		} catch (Exception $e){
			
			$error = array('status' =>400,
							'message' => 'Some error has occured.Please try again',							
							'code' => 400);	
							
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}					
	}

	/**
	*
	*
	*/
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
			$this->logData($this->logInfoPath,$this->request->all(),'DB',$success);
			
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
			$this->logData($this->logInfoPath,$this->request->all(),'DB',$success);
			
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
			$orgId =  $header['orgId'];
			$projectId =  $header['projectId'];
			$role_id =  $header['roleId'];
		} else {
			
			$message = "insufficent header info";
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();		
		$database = $this->connectTenantDatabase($request,$orgId);		
		
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
		->where(['status'=>'available', 
				'project_id'=>$projectId]
				)
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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');		
		
		//validate structure id
		if (!$this->request->has('structure_id')) {
			$error = array('status' =>400,
							'message' => 'Structure id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		if (!$this->request->has('machine_id')) {
			$error = array('status' =>400,
							'message' => 'Machine id field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		$database = $this->connectTenantDatabase($request,$org_id);		
		
		if ($database === null) {
			return response()->json(['status' => 'error',									  
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
		$strMachMappingObj->project_id = $project_id;
		$strMachMappingObj->deployed_date = $currentDateTime = Carbon::now()->timestamp;;
		$strMachMappingObj->status = 'deployed';
		$strMachMappingObj->created_by = $this->request->user_id;
		
		try {
			
			$strMachMappingObj->save();
			
			$statusCode = \App\StatusCode::where(['status_code'=>'117', 'type'=>'structure'])->first();
			$this->request->status = $statusCode['status_name'];
			$this->request->status_code = $statusCode['status_code'];
		
			//update and insert status in log
			//$this->request->status = 'In Progress';
			//$this->request->status_code = '117';
			
			$this->request->action = 'Machine Deployed';			
			$this->structureUpdateStatus($this->request);
			
			//update achine status
			$statusCode = \App\StatusCode::where(['statusCode'=>'107', 'type'=>'machine'])->first();
			$machineData->status = $statusCode['status_name'];
			$machineData->status_code = $statusCode['statusCode'];
		
			//$machineData->status = "Deployed";
			//$machineData->status_code = '107';
			
			$machineData->save();
			
			$this->machineStatusLog($this->request,$machineData->code, '107');
			
			
			//send notification
			$roleArr = array('110','111','112','114');
			$params['org_id'] = $org_id;
			$params['projectId'] = $project_id;
			
			$params['request_type'] =  self::NOTIFICATION_MACHINE_DEPLOYED;
			$params['update_status'] = 'Machine Deployed';				
			$params['struture_code'] = $strData->code;
			$params['code'] = $machineData->machine_code;
			
			$params['stateId'] = $machineData->state_id;
			$params['districtId'] = $machineData->district_id;
			$params['talukaId'] = $machineData->taluka_id;
			$params['modelName'] = 'Machine';
			
			//$this->sendSSNotification($this->request,$params, $roleArr);	
			$this->request['functionName'] = __FUNCTION__;
			$this->request['params'] =  $params;
			$this->request['roleArr'] = $roleArr;
			
			dispatch((new DataQueue($this->request)));
		
			$success = array('status' =>200,
							'code' => 200,
							'message' => 'Machine deployed successfully'						
							);				
			$this->logData($this->logInfoPath,$this->request->all(),'DB',$success);
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
	
	/**
	*
	*
	*/
	public function closeStructure(Request $request) {
	
	
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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');		
		
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
		
		$database = $this->connectTenantDatabase($request,$org_id);
		if ($database === null) {
			return response()->json(['status' => 'error', 									  
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}
		
		$strMappingCnt =  StructureMachineMapping::where(['structure_id'=>$requestJson->structure_id,
													'status'=>'deployed'])->count();
													
		if ($strMappingCnt > 0) {
			
			$error = array('status' =>400,
							'message' => 'Please release all the machine from Structure',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
			
		}
				
		//check structure  id exist or not
		$strData =  Structure::where(['_id'=>$requestJson->structure_id])
							->first();
							
		if (!$strData) {
			$error = array('status' =>400,
							'message' => 'Invalid structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);
		}

		/*$urls = [];
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
					$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_STRUCTURE'), $newName, 'octopusS3');
					
					$urls[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_STRUCTURE').'/' . $newName;
				}
			}
		}*/
		$status = 'Completed';
		if ($this->request['structureStatus'] == '120') {
			
			$params['request_type'] =  self::NOTIFICATION_STRUCTURE_PARTIALLY_COMPLETED;
			$params['update_status'] = 'Structure Partially Completed';
			$status = 'Partially-completed';
			$statusCode = \App\StatusCode::where(['status_code'=>'119', 'type'=>'structure'])->first();
			$strData->status = $statusCode['status_name'];
			$strData->status_code = $statusCode['status_code'];
			$params['reason'] = isset($requestJson->etReason) ? $requestJson->etReason : '';
			//$strData->status = 'Partially-completed';
			//$strData->status_code = '119';
			
			//update status and uplaod certificate
			if ($requestJson->is_completed == 'true') {
				
				$statusCode = \App\StatusCode::where(['status_code'=>'120', 'type'=>'structure'])->first();
				$strData->status = $statusCode['status_name'];
				$strData->status_code = $statusCode['status_code'];
		
				$status = 'Completed';
				//$strData->status_code = '120';
				
				$params['request_type'] =  self::NOTIFICATION_STRUCTURE_COMPLETED;
				$params['update_status'] = 'Structure Completed';
				
				//machine free from structure
				//109/Available	
			}
			$dateData = time();
			//$strData->closed_date = new \MongoDB\BSON\UTCDateTime($dateData);
			$strData->closed_date = new \MongoDB\BSON\UTCDateTime($dateData * 1000 );
			//$strData->partially_reason = isset($requestJson->etReason) ? $requestJson->etReason : '';			
						
			//$strData->structurecompleted_images = $urls;
			
		} else if ($this->request['structureStatus'] == '121') {
			
			$statusCode = \App\StatusCode::where(['status_code'=>'121', 'type'=>'structure'])->first();
			$strData->status = $statusCode['status_name'];
			$strData->status_code = $statusCode['status_code'];	
			
			//$strData->status = 'Closed';
			//$strData->status_code = '121';		
		//	$strData->certificates = $urls;
			
			$strData->etstimate_silt_qantity = isset($requestJson->etSiltQantity) ? $requestJson->etSiltQantity : '';
			$strData->etstimate_work_start_date = isset($requestJson->etWorkStartDate) ? $requestJson->etWorkStartDate : '';
			$strData->etstimate_work_completion_date = isset($requestJson->etWorkCompletionDate) ? $requestJson->etWorkCompletionDate : '';
			$strData->etstimate_operational_days = isset($requestJson->etOperationalDays) ? $requestJson->etOperationalDays : '';
			
			$strData->etstimate_diesel_consumed_amount = isset($requestJson->etDieselConsumedAmount) ? $requestJson->etDieselConsumedAmount : '';
			$strData->etstimate_diesel_consumed_quantity = isset($requestJson->etDieselConsumedQuantity) ? $requestJson->etDieselConsumedQuantity : '';
			$strData->etstimate_work_dimension = isset($requestJson->etWorkDimension) ? $requestJson->etWorkDimension : '';
			
			$params['request_type'] =  self::NOTIFICATION_STRUCTURE_CLOSED;
			$params['update_status'] = 'Structure Closed';
			$status = 'Closed';
			
		} else if ($this->request['structureStatus'] == '123') {
				
			//$strData->partially_closed_images = $urls;
				
			$statusCode = \App\StatusCode::where(['status_code'=>'123', 'type'=>'structure'])->first();
			$strData->status = $statusCode['status_name'];
			$strData->status_code = $statusCode['status_code'];	
			
			//$strData->status = 'Closed';
			//$strData->status_code = '121';		
			//$strData->certificates = $urls;
			
			$strData->etstimate_silt_qantity = isset($requestJson->etSiltQantity) ? $requestJson->etSiltQantity : '';
			$strData->etstimate_work_start_date = isset($requestJson->etWorkStartDate) ? $requestJson->etWorkStartDate : '';
			$strData->etstimate_work_completion_date = isset($requestJson->etWorkCompletionDate) ? $requestJson->etWorkCompletionDate : '';
			$strData->etstimate_operational_days = isset($requestJson->etOperationalDays) ? $requestJson->etOperationalDays : '';
			
			$strData->etstimate_diesel_consumed_amount = isset($requestJson->etDieselConsumedAmount) ? $requestJson->etDieselConsumedAmount : '';
			$strData->etstimate_diesel_consumed_quantity = isset($requestJson->etDieselConsumedQuantity) ? $requestJson->etDieselConsumedQuantity : '';
			$strData->etstimate_work_dimension = isset($requestJson->etWorkDimension) ? $requestJson->etWorkDimension : '';
			
			$params['request_type'] =  self::NOTIFICATION_STRUCTURE_PARTIALLY_CLOSED;
			$params['update_status'] = 'Structure Partially Closed';
			$status = 'Partially Closed';
		}		
		
		$strData->project_id = $project_id;		
		$strData->updated_by = $this->request->user_id;
		//$strData->save();

		try {
			$strData->save();
			
			//Release all the machines from structure
			$strDataMapping = StructureMachineMapping::where(['status' => 'deployed',
													'structure_id'=>$requestJson->structure_id])
													->get()->toArray();

			if (count($strDataMapping) > 0) {
				
				foreach ($strDataMapping as $data) {//print_r( $data);exit;
					$machineData = Machine::find($data['machine_id']);
					
					$statusCode = \App\StatusCode::where(['statusCode'=>'106', 'type'=>'machine'])->first();
					$machineData->status = $statusCode['status_name'];
					$machineData->status_code = $statusCode['statusCode'];	
			
					//$machineData->status = 'Available';
					//$machineData->status_code = '106';
					$machineData->save();					
				}
				$updateData = ['status' =>'closed' ];
				//Release all the machines from structure
				$strDataMap = StructureMachineMapping::where(['status' => 'deployed',
														'structure_id'=>$requestJson->structure_id])
													->update($updateData);
	
				//$strObj->status = 'closed';
				//$strObj->save();				
			}
			
			$this->request->status = 'Completed';
			$this->request->status_code = '120';
			$this->request->action = 'Structure Completed';			
			$this->request['structure_id'] = $requestJson->structure_id;
			
			//$this->request->user_id = 'adasd';
			$this->structureLog($this->request);
			
			//send notification('DM','TC','HO OPS','FA','MIS HO')
			$roleArr = array('110','111','112','114','115');
			$params['org_id'] =  $org_id;
			$params['projectId'] = $project_id;
			
			$params['code'] = $strData->code;
			//$params['org_id'] = $org_id;
			
			$params['stateId'] = $strData->state_id;
			$params['districtId'] = $strData->district_id;
			$params['talukaId'] = $strData->taluka_id;
			//$params['reason'] = isset($requestJson->reason) ? $requestJson->reason :'';
			$params['modelName'] = 'Structure';

			$this->request['functionName'] = __FUNCTION__;
			//$this->request->action = 'Preparation Checklist';			
			$this->request['params'] =  $params;
			$this->request['roleArr'] = $roleArr;
			$this->request['structure_id'] = $requestJson->structure_id;

			dispatch((new DataQueue($this->request)));			
			
			//$this->sendSSNotification($this->request,$params, $roleArr);			
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Structure ".$status." Successfully.");
			$this->logData($this->logInfoPath,$this->request->all(),'DB',$responseData);
							
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
	
/*	public function machineDailyWorkDetails(Request $request) {

		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');		
		
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
	}*/

	public function machineDieselRecord(Request $request) {

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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');		
		$requestDataJson = json_decode(file_get_contents('php://input'), true);
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		$dieselImage = 0;
		$registerImage = 0;
		
		$dieselImageUrl = [];
		$registerImageUrl = [];
		
		for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {			
			
			$fileName = 'diesel'.$dieselImage;
				
			if ($this->request->has($fileName)) {
				
				if ($this->request->file($fileName)->isValid()) {
			
					$fileInstance = $this->request->file($fileName);
				
					$name = $fileInstance->getClientOriginalName();
					$ext = $this->request->file($fileName)->getClientMimeType(); 
					$newName = uniqid().'_'.$name.'.jpg';
					$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
					
					$dieselImageUrl[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
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
						$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
						
						$registerImageUrl[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
						
					}					
				$registerImage++;			
			}			
					
		}
		$database = $this->connectTenantDatabase($this->request,$org_id);
		
		if ($database === null) {
			
			return response()->json(['status' => 'error',								  
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
			$machineDieselRecordObj->created_by = $this->request->user_id;

			try { 
			
				$machineDieselRecordObj->save();
				
				$responseData = array( 'code'=>200,
										'status' =>200,
										'message'=>"Machine diesel details saved successfully."
										);
				$this->logData($this->logInfoPath,$this->request->all(),'DB',$responseData);
				
			} catch(Exception $e) {
				//echo $e->getMessage();exit;
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
		//	$this->logData($this->logInfoPath,$this->request->all(),'Error',$responseData);
							
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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');		
		
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
		
		$strObj = StructureMachineMapping::where(['structure_id' => $this->request['new_structure_id'],
													  'machine_id'=> $this->request['machine_id'],
													  'status'=> 'deployed'])
													  ->first();			
			
		//already machine deployed in new structure from current structure
		/*$mshiftingCnt =  MachineShifting::where(['machine_id'=> $this->request['machine_id'],
											'current_structure_id'=> $this->request['current_structure_id'],
											'new_structure_id'=> $this->request['new_structure_id'],
											])
											->count();	*/										
		if ($strObj) {
			
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
		$mshifting->project_id = $project_id;		
		
		try {
			$mshifting->save();	

			//update previous mapping status to shifted
			$strObj = StructureMachineMapping::where(['structure_id' => $this->request['current_structure_id'],
													  'machine_id'=> $this->request['machine_id'],
													  'status'=> 'deployed'])
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
			$statusCode = \App\StatusCode::where(['status_code'=>'117', 'type'=>'structure'])->first();
			$this->request->status = $statusCode['status_name'];
			$this->request->status_code = $statusCode['status_code'];	
			
			//$this->request->status = 'In Progress';
			//$this->request->status_code = '117';
			
			$this->request->action = 'Machine Deployed';
			$this->request['structure_id'] = $this->request['new_structure_id'];	
			$this->structureUpdateStatus($this->request);
			
			//update achine status		
			$machineData = Machine::where('_id',$this->request['machine_id'])->first();
			
			$statusCode = \App\StatusCode::where(['statusCode'=>'107', 'type'=>'machine'])->first();
			$machineData->status = $statusCode['status_name'];
			$machineData->status_code = $statusCode['statusCode'];	
			
			//$machineData->status = "Deployed";
			//$machineData->status_code = "107";
			$machineData->save();
			
			//insert into machine log
			$this->machineStatusLog($this->request,$machineData->code, '107');
			
			
			$roleArr = array('110','111','112','114','115');
			
			$params['org_id'] = $org_id;
			$params['projectId'] = $project_id;
			
			$params['request_type'] =  self::NOTIFICATION_MACHINE_SHIFTED;
			$params['update_status'] = 'Machine shifted';				
			$params['code'] = $machineData->machine_code;
			
			$currStrObj = Structure::where("_id",$this->request['current_structure_id'])->first();
			$newStrObj = Structure::where("_id",$this->request['new_structure_id'])->first();
		
			$params['current_structure_code'] = $currStrObj->code;
			$params['new_structure_code'] = $newStrObj->code;
			
			$params['stateId'] = $machineData->state_id;
			$params['districtId'] = $machineData->district_id;
			$params['talukaId'] = $machineData->taluka_id;
			$params['modelName'] = 'Machine';
			
			
			$this->request->functionName = __FUNCTION__;
			$this->request['params'] =  $params;
			$this->request['roleArr'] = $roleArr;
			
			dispatch((new DataQueue($this->request)));
			
			//$this->sendSSNotification($this->request,$params, $roleArr);		
				
		
			//$strObj = Structure::where("_id",$this->request['current_structure_id'])->first();
				
			$responseData = array( 'code'=>200,
									'status' =>200,
									'message'=>"Machine deployed successfully."
									);
			$this->logData($this->logInfoPath,
							$this->request->all(),
							'Error',
							$responseData
							);
			$this->logData($this->logInfoPath,$this->request->all(),'DB',$responseData);		
			
			
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
		$this->logData($this->logInfoPath,$this->request->all(),'DB');		
		
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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');
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
		
		$database = $this->connectTenantDatabase($this->request,$org_id);
		
		//$user->org_id);		
		
		if ($database === null) {
			
			return response()->json(['status' => 'error', 
									 'data' => '', 
									 'message' => 'User does not belong to any Organization.'],
									 403);
		}

		$siltRegisterImage = [];
		
		for ($cnt = 0; $cnt < $request['imageArraySize']; $cnt++) {
				
			$fileName = 'register'.$cnt;
			//$imageData = $this->imageUpload($fileName , env('AWS_MACHINE_FORM_IMAGE'), $this->request);			
			//echo $imageData."-----";exit;
			//if ($imageData != '') {
			
				/*$fileInstance = $this->request->file($fileName);
				$name = $fileInstance->getClientOriginalName();
				$ext = $this->request->file($fileName)->getClientMimeType(); 
				//echo $ext;exit;
				$newName = uniqid().'_'.$name.'.jpg';
				$s3Path = $this->request->file($fileName)->storePubliclyAs('staging/machine/forms', $newName, 'octopusS3');
				*/
				//$siltRegisterImage[] = $imageData;
				//'https://' . env('OCT_AWS_CDN_PATH') . '/staging/machine/forms/' . $newName;
			//}
			if ($this->request->has($fileName)) {				
					
						if ($this->request->file($fileName)->isValid()) {
					
							$fileInstance = $this->request->file($fileName);
						
							$name = $fileInstance->getClientOriginalName();
							//$ext = $this->request->file($fileName)->getClientMimeType(); 
							
							$newName = uniqid().'_'.$name.'.jpg';
							$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
							
							$siltRegisterImage[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
							
						}					
					//$licenseImage++;			
				}
		}
		//print_r($siltRegisterImage);exit;
		$requestJson->beneficiaries_count =  isset($requestJson->beneficiaries_count) ? $requestJson->beneficiaries_count :0;
		$requestJson->farmer_count = isset($requestJson->farmer_count) ? $requestJson->farmer_count : 0;
		$requestJson->tractor_trips = isset($requestJson->tractor_trips) ? $requestJson->tractor_trips : 0;
		$requestJson->tipper_trips = isset($requestJson->tipper_trips) ? $requestJson->tipper_trips :0;
		//echo $requestJson->machine_id;exit;
		//print_r($requestJson);exit;
		$machineSiltObj = new MachineSiltDetails;
		$machineSiltObj->machine_id = $requestJson->machine_id;
		$machineSiltObj->structure_id = $requestJson->structure_id;//echo "er werwroo";exit;
		$machineSiltObj->project_id = $project_id;	
		$machineSiltObj->transport_date = (int)$requestJson->transport_date;
		$machineSiltObj->tractor_trip_number = (int) $requestJson->tractor_trips;
		$machineSiltObj->tipper_trip_number = (int) $requestJson->tipper_trips;
		$machineSiltObj->farmer_count = (int) $requestJson->farmer_count;
		$machineSiltObj->beneficiaries_count = (int)$requestJson->beneficiaries_count;
		$machineSiltObj->silt_register_image = $siltRegisterImage;
		$machineSiltObj->created_by = $this->request->user_id;
		
		try {
			$machineSiltObj->save();
			
			$responseData = array( 'code'=>200,
									'status' =>200,
									'message'=>"Silt details saved successfully."
									);
			$this->logData($this->logInfoPath,
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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');		
		$requestDataJson = json_decode(file_get_contents('php://input'), true);
		
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		$database = $this->connectTenantDatabase($request,$org_id);
		
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
				$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_MACHINE'), $newName, 'octopusS3');
				
				//if ($s3Path == null || !$s3Path) {
					//return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
				//}
				$url[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_MACHINE').'/' . $newName;
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
										
				$this->logData($this->logInfoPath,$this->request->all(),'DB',$responseData);										
				
			} catch(Exception $e) {				
				
				$error = array('code'=>400,
										'status' =>400,
										'message'=>'Some error has occured .Please try again',
										);
				$this->logData($this->logInfoPath,$this->request->all(),'Error',$error);	
					
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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();		
		$database = $this->connectTenantDatabase($request,$org_id);	
		
		$this->request->userId =  $user->id;
		$this->logData($this->logInfoPath,$this->request->all(),'DB');
		
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
		$lastStructureCode = '100000';
		$getlastStructure = Structure::all()->last();
		
		if ($getlastStructure) { 
			$lastStructureCode = (explode("-",  $getlastStructure['code'])[1]);
		}
		
		$lastStructureCode = $lastStructureCode + 1;
				
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
		
		foreach ($cVillageIds as $key=>$data) {
			
			$catArr[$key]['id'] = $data;		
		}	
		
		foreach ($cVillage as $key=>$data) {
			
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
						'lat' => isset($requestJson['lat']) ? (float) $requestJson['lat']  : 0,
						'long' => isset($requestJson['log']) ? (float) $requestJson['log']  : 0,
						'ff_id' => isset($requestJson['ff_id']) ? $requestJson['ff_id']  : '',
						
						'nota_detail' => isset($requestJson['notaDetail']) ? $requestJson['notaDetail']  : '',
						'water_shed_no' => isset($requestJson['waterShedNo']) ? $requestJson['waterShedNo']  : '',
						'gat_no' => isset($requestJson['gatNo']) ? $requestJson['gatNo']  : '',
						'area' => isset($requestJson['area']) ? $requestJson['area']  : '',			 	
						'intervention_id' => isset($requestJson['intervention_id']) ? $requestJson['intervention_id']  : '',			 	
						'created_by' => $user->id,
						'updated_by' => $user->id,
						'is_active' => 1,
						'status'=>'Approved',
						'status_code'=> '115',
						'created_at' => $created_at,
						'updated_at' => $created_at
                    ];
		try {
				DB::table('structure')->insert($resultData);
				
				//send notification
				$roleArr = array('110','111','115');
				//$params['org_id'] = $user->org_id;
				$params['request_type'] =  self::NOTIFICATION_STRUCTURE_APPROVED;
				$params['update_status'] = 'Structure Approved';				
				$params['code'] = 'SBJS-'.$lastStructureCode;
				
				
				$params['stateId'] = $requestJson['state_id'];
				$params['districtId'] = $requestJson['district_id'];
				$params['talukaId'] = $requestJson['taluka_id'];
				$params['modelName'] = 'Structure';	
				$params['org_id'] =  $org_id;
				$params['projectId'] = $project_id;
			

				$this->request['functionName'] = __FUNCTION__;
				$this->request->action = 'New Structure';				
				$this->request['params'] =  $params;
				$this->request['roleArr'] = $roleArr;

				dispatch((new DataQueue($this->request)));			
			
				//$this->sendSSNotification($this->request,$params, $roleArr);			
					
				$responseData = array('code'=>200,
										'status' =>200,
										'message'=>"Structure created successfully.");									
				
				$this->logData($this->logInfoPath,$this->request->all(),'DB',$responseData);
			
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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');
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
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 'error', 									 
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
			$cmMobilisationObj->project_id = $project_id;
			$cmMobilisationObj->task = isset($requestJson->task) ? $requestJson->task : '';
			$cmMobilisationObj->activity_name = $requestJson->activity_name;
			$cmMobilisationObj->created_by = $user->_id;			
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
					$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_STRUCTURE'), $newName, 'octopusS3');
					
					//if ($s3Path == null || !$s3Path) {
						//return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
					//}
					$url[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_STRUCTURE').'/' . $newName;
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
			
			$data['program_image'] = $url;			
			$cmMobilisationObj->orientation = $data;
		}
		
		try {
			$cmMobilisationObj->save();	
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Structure data saved successfully.");
									
			$this->logData($this->logInfoPath,$this->request->all(),'DB',$responseData);
							
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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');		
		
		
		$database = $this->connectTenantDatabase($request,$org_id);
		
		if ($database === null) {
			return response()->json(['status' => 400,									  
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
					$s3Path = $this->request->file($fileName)->storePubliclyAs(env('SS_IMAGE_PATH_STRUCTURE'), $newName, 'octopusS3');
					
					//if ($s3Path == null || !$s3Path) {
						//return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
					//}
					$url[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SS_IMAGE_PATH_STRUCTURE').'/' . $newName;
					//return response()->json(['status' => 'success', 'data' => ['url' => $result], 'message' => 'Image successfully uploaded in S3']);
				}
			}
		}
		
		$structureData = new StructureVisit;
		//::where('_id',$requestJson['structure_id'])->first();
		$structureData->structure_id = $requestJson->structure_id;
		$structureData->project_id = $project_id;		
		$structureData->is_safety_signage = isset($requestJson->is_safety_signage) ? $requestJson->is_safety_signage : '';							
		$structureData->is_guidelines_followed = isset($requestJson->is_guidelines_followed) ? $requestJson->is_guidelines_followed : "";
		
		//$structureData->structure_photos = $requestJson['images'];
		$structureData->status_record_id = isset($requestJson->status_record_id) ? $requestJson->status_record_id : '';
		$structureData->issue_related_id = isset($requestJson->issue_related_id) ? $requestJson->issue_related : '';
		$structureData->issue_description = isset($requestJson->issue_description) ? $requestJson->issue_description: '';
		
		$structureData->image_url =  $url;
		$structureData->created_by =  $user->_id;
				
		try {
			$structureData->save();	
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Structure visit data saved successfully.");
									
			$this->logData($this->logInfoPath,$this->request->all(),'DB',$responseData);
							
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
		$this->request->userId =  $user->id;
		
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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$orgDetails = $user['orgDetails'];
		
		$userLocation = [];
		foreach ($orgDetails as $data) {
			
			if ($data['org_id'] == $orgId && $data['project_id'] ==  $projectId) {
				$status = $data['status'];
				break;
			}		
		}	
		
		//print_r($status);exit;
		if(isset($roleId) && $roleId !='') {
		
			$rolename = \App\Role::select('role_code','display_name')->where("_id",$roleId)->first();
		
			if (!$rolename) {
				return response()->json([
				'code'=>400,	
					'status' => 400,            
					'message' => 'Invalid role  id'
				]);			
			}	
		
			$resultData = [];
			$role_access=[];	
			
			if ($rolename->role_code  == '110') { //DM Role

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
				$role_access[9] = array ('action_code'=> 102, 'action_name'=> 'Save Offine Structure');
				
				/*$role_access[9] = array ('action_code'=> 100, 'action_name'=> 'Add Structure');			
				$role_access[10] = array ('action_code'=> 102, 'action_name'=> 'Save Offine Structure');
				$role_access[11] = array ('action_code'=> 103, 'action_name'=> 'Prepared Structure');
				$role_access[12] = array ('action_code'=> 104, 'action_name'=> 'Machine Deployed');
				$role_access[13] = array ('action_code'=> 105, 'action_name'=> 'Communication Mobilisation');
				$role_access[14] = array ('action_code'=> 106, 'action_name'=> 'Struture visit & monitoring record');
				$role_access[15] = array ('action_code'=> 107, 'action_name'=> 'Structure Complete');
				
				$role_access[16] = array ('action_code'=> 114, 'action_name'=> 'Machine Deploy');
				$role_access[17] = array ('action_code'=> 115, 'action_name'=> 'Machine Visit & Validation of Working Hours Record');			
				$role_access[18] = array ('action_code'=> 116, 'action_name'=> 'Silt Transportation Record');	
				$role_access[19] = array ('action_code'=> 117, 'action_name'=> 'Record of Diesel Received');	
				$role_access[20] = array ('action_code'=> 118, 'action_name'=> 'Machine Shifting');		
				$role_access[21] = array ('action_code'=> 119, 'action_name'=> 'Machine free From Taluka');			
				$role_access[22] = array ('action_code'=> 120, 'action_name'=> 'Change State');		
				$role_access[23] = array ('action_code'=> 121, 'action_name'=> 'Change District');
				$role_access[24] = array ('action_code'=> 122, 'action_name'=> 'Change Taluka');		
				$role_access[25] = array ('action_code'=> 123, 'action_name'=> 'Change Village');	*/
				//$role_access[10] = array ('action_code'=> 127, 'action_name'=> 'Structure Boundary');
				$role_access[10] = array ('action_code'=> 128, 'action_name'=> 'Upload MOU');
				$role_access[11] = array ('action_code'=> 107, 'action_name'=> 'Structure Complete');
				/*$role_access[12] = array ('action_code'=> 131, 'action_name'=> 'Create Operator');
				$role_access[13] = array ('action_code'=> 132, 'action_name'=> 'Assign Operator');
				$role_access[14] = array ('action_code'=> 133, 'action_name'=> 'Release Operator');
				
				*/
			} else if ($rolename->role_code  == '111') {//TC

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
				$role_access[15] = array ('action_code'=> 124, 'action_name'=> 'Structure Closed');
				$role_access[16] = array ('action_code'=> 127, 'action_name'=> 'Structure Boundary');
				$role_access[17] = array ('action_code'=> 129, 'action_name'=> 'Sign Off');
				//$role_access[18] = array ('action_code'=> 122, 'action_name'=> 'Change Taluka');		
				
				$role_access[18] = array ('action_code'=> 131, 'action_name'=> 'Create Operator');
				$role_access[19] = array ('action_code'=> 132, 'action_name'=> 'Assign Operator');
				$role_access[20] = array ('action_code'=> 133, 'action_name'=> 'Release Operator');
					
			
			} else if ($rolename->role_code  == '112' || $rolename->role_code  == '115') { //HO OPS & HO MIS
			
				
				$resultData['role_code'] = (int) $rolename->role_code;
				$resultData['role_name'] = $rolename->display_name;
				$role_access[0] = array ('action_code'=> 100, 'action_name'=> 'Add Structure');
				$role_access[1] = array ('action_code'=> 101, 'action_name'=> 'View Structure');
				//$role_access[2] = array ('action_code'=> 102, 'action_name'=> 'Save Offine Structure');
				//$role_access[3] = array ('action_code'=> 103, 'action_name'=> 'Prepared Structure');
				//$role_access[4] = array ('action_code'=> 104, 'action_name'=> 'Machine Deployed');
				//$role_access[5] = array ('action_code'=> 105, 'action_name'=> 'Communication Mobilisation');
				//$role_access[6] = array ('action_code'=> 106, 'action_name'=> 'Struture visit & monitoring record');
				//$role_access[7] = array ('action_code'=> 107, 'action_name'=> 'Structure Complete');
				$role_access[2] = array ('action_code'=> 108, 'action_name'=> 'Add Machine');
				$role_access[3] = array ('action_code'=> 109, 'action_name'=> 'View Machine');
				/*$role_access[10] = array ('action_code'=> 110, 'action_name'=> 'MOU Machine');
				$role_access[11] = array ('action_code'=> 111, 'action_name'=> 'Machine Eligible');
				$role_access[12] = array ('action_code'=> 112, 'action_name'=> 'Machine MOU Termnated');
				$role_access[13] = array ('action_code'=> 113, 'action_name'=> 'Machine Available');
				$role_access[14] = array ('action_code'=> 114, 'action_name'=> 'Machine Deploy');
				$role_access[15] = array ('action_code'=> 115, 'action_name'=> 'Machine Visit & Validation of Working Hours Record');			
				$role_access[16] = array ('action_code'=> 116, 'action_name'=> 'Silt Transportation Record');	
				$role_access[17] = array ('action_code'=> 117, 'action_name'=> 'Record of Diesel Received');	
				$role_access[18] = array ('action_code'=> 118, 'action_name'=> 'Machine Shifting');		
				$role_access[19] = array ('action_code'=> 119, 'action_name'=> 'Machine free From Taluka');			
				$role_access[20] = array ('action_code'=> 120, 'action_name'=> 'Change State');	*/	
				$role_access[4] = array ('action_code'=> 121, 'action_name'=> 'Change District');
				$role_access[5] = array ('action_code'=> 122, 'action_name'=> 'Change Taluka');		
				//$role_access[23] = array ('action_code'=> 123, 'action_name'=> 'Change Village');
				
				if ($rolename->role_code  == '115') {//HO MIS				
					$role_access[6] = array ('action_code'=> 130, 'action_name'=> 'Change Meter Reading');			
				}		
				
		} else if ($rolename->role_code  == '117' &&  $status['status'] == 'approved') {//SS-Media

			$resultData['role_code'] = $rolename->role_code;
			$resultData['role_name'] = $rolename->display_name;
			$role_access[0] = array ('action_code'=> 125, 'action_name'=> 'Add Feed');
			$role_access[1] = array ('action_code'=> 126, 'action_name'=> 'Delete Feed');
			
		}

		if (count($role_access) == 0) {
			return response()->json([
			'code'=>400,	
            'status' => 400,            
            'message' => 'No data available '
        ]);		
		}
		
		$resultData['role_access'] = $role_access;
		
		return response()->json([
			'code'=>200,	
            'status' => 200,
            'data' => $resultData,
            'message' => 'Role access data'
        ]);

         } else
		{

			return response()->json([
			'code'=>400,	
            'status' => 400,            
            'message' => 'No data available'
        ]);

		}	
	}

	/*public function sendSSNotification($request,$params, $roleArr) {
		
		//print_r($params);exit;
		$logInfoPath = "logs/Structure/DB/Notification/logs_".date('Y-m-d').'.log';
		$errorPath = "logs/Structure/Error/Notification/logs_".date('Y-m-d').'.log';

		//$stateId = $params['stateId'];
		$districtId = $params['districtId'];
		$talukaId = $params['talukaId'];
		$villageId = isset($params['villageId']) ? $params['villageId'] : '';
		DB::setDefaultConnection('mongodb');
		//loop for role
		foreach ($roleArr as  $roleCode) {
		
			
		
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
			$dtArry = array($districtId);			
			$query = \App\User::where(['role_id' => $roleData->_id])
			
			 ->whereIn('location.district',$dtArry);

			// 'location.district' => $districtId]);
			
			if ($roleCode == '111') {
				$talukaIds  = array($talukaId);
				$query->whereIn(['location.taluka' => $talukaIds]);
			}

			if ($roleCode == '114') {
				$villageIds  = array($villageId);
				$query->whereIn(['location.village' => $villageIds]);
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
					//$userData['firebase_id'],
					'eI_FdLaocPU:APA91bE_HZck00WgG4HJmIuDQJu6jolos0rFeyO_fN1N9qwqOUrHFv1adpLRQTX4n3Y1w6MKCEFtBk9iQOUsDHcS3G1AGWEl2rQgX39gn1y4Oqmnlh2eXs0uUNUVhdGkQG7L6HNjkM7h',
					[ 
						'phone'=> '9028724868',//$userData['phone']
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
	}*/

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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');

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

	
	public  function structureBoundary(Request $request) {
		
		
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
			$this->logData($this->logInfoPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			
			return response()->json($response_data,200);			
		}
		
		$user = $this->request->user();
		$this->request->user_id = $user->_id;
		
		//insert user request to log
		$this->logData($this->logInfoPath,$this->request->all(),'DB');

		$requestJson = json_decode(file_get_contents('php://input'), true);
		//echo $requestJson['structure_id'];exit;
		
		//validate structure id
		if (!isset($requestJson['structureId'])) {
			$error = array('status' =>400,
							'msg' => 'Structure id is missing',							
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
		
		$strData = Structure::find($requestJson['structureId']);
		if (empty($strData)) {
			
			$error = array('status' =>400,
							'msg' => 'Invalid Structure id',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);	
		}

		$strData->structure_boundary = json_decode($requestJson['structureBoundary']);
		
		try {
			
			$strData->save();	
			
			$responseData = array('code'=>200,
									'status' =>200,
									'message'=>"Structure data saved successfully.");
									
			$this->logData($this->logInfoPath,$this->request->all(),'DB',$responseData);
							
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
	
	public function getStructurePreparedData (Request $request) {
		
		$dateData = time();
			//$strData->closed_date =
		$database = $this->connectTenantDatabase($request,'5dcfa18c5dda7605c043f2b3');
			
		$d = new \MongoDB\BSON\UTCDateTime($dateData * 1000 );
		$data =  Structure::find('5e1d59c2083ae33e1c5e0e1c');
		$data->closed_date = $d;
		$data->save();
		echo $data->id;exit;
			
		$database = $this->connectTenantDatabase($request,'5dcfa18c5dda7605c043f2b3');
		
		$data =  Structure::where(['is_active'=>1])->where('status_code', '<>','115')
							->with('structurePrepared.userDetails')->select('_id','code')
							->get();
		
		$dataResult = array('code'=>200,
									'status' =>'success',
									'data'=>$data,
									);
		//	$this->logData($this->errorPath,$this->request->all(),'Error',$error);
			
			return response()->json($dataResult);
		//echo '<echo>';print_r($data->toArray());exit;
	}

		
}
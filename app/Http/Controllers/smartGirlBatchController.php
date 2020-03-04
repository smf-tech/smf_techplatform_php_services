<?php 
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\User;
use Maklad\Permission\Models\Role;
use Maklad\Permission\Models\Permission;
use Dingo\Api\Routing\Helpers;
use App\Organisation;
use App\Project;
use App\Module;
use App\RoleConfig;
use App\Event;
use App\EventType; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\ApprovalLog;
use Carbon\Carbon;
use App\Category;
use App\User_detail;
use App\Survey;
use App\PlannerTransactions;
use DateTimeImmutable;
use DateTime;
use App\PlannerLeaveApplications;
use App\UserController;
use App\ApprovalsPending; 
use App\PlannerClaimCompoffRequests; 
use App\Entity;


use App\SmartGirlBatch;


use Illuminate\Support\Arr;

class smartGirlBatchController extends Controller
{
	use Helpers;

	protected $types = [
			'profile' => 'BJS/Images/profile',
			'form' => 'BJS/Images/forms',
			'story' => 'BJS/Images/stories'
		];

	/**
	 *
	 * @var Request
	 */
	protected $request;

	public function __construct(Request $request) 
	{
		$this->request = $request;
		$this->logInfoPath = "logs/smartGirl_Batch/DB/logs_".date('Y-m-d').'.log';
		$this->logerrorPath = "logs/smartGirl_Batch/Error/logs_".date('Y-m-d').'.log';
	}



	public function getbatchCategory(Request $request)
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
	      }else{

	        
	        $message['message'] = "insufficent header info";
	        $message['function'] = 'getbatchCategory'; 
	        $this->logData($this->logerrorPath ,$message,'Error');
	        $response_data = array('status' =>'404','message'=>$message);
	        return response()->json($response_data,200); 
	        // return $message;
	      }

	       $user = $this->request->user();
            // $all_user=User::select('role_id')->where('approve_status','pending')->get();
            $database = $this->connectTenantDatabase($request, $org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

	      $categoryList = Category::where('project_id',$project_id)->get();

	     

        if($categoryList)
             {
                $response_data = array('status'=>200,'data' => $categoryList,'message'=>"success");
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                return response()->json($response_data,300); 
            }

	}


	public function getAdditionalMasterTrainers(Request $request)
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
	      }else{

	        
	        $message['message'] = "insufficent header info";
	        $message['function'] = 'getMasterTrainerList'; 
	        $this->logData($this->logerrorPath ,$message,'Error');
	        $response_data = array('status' =>'404','message'=>$message);
	        return response()->json($response_data,200); 
	        // return $message;
	      }

	       $user = $this->request->user();
            // $all_user=User::select('role_id')->where('approve_status','pending')->get();
            $database = $this->connectTenantDatabase($request, $org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

           $data = json_decode(file_get_contents('php://input'), true);
		 
		   $this->logData($this->logInfoPath,$data,'DB');
            
           DB::setDefaultConnection('mongodb'); 

           $masterTrainerRoleId = Role::select('_id')
           								->where('project_id',$project_id)
           								->where('name','Smart Girl Master Trainer')
           								->get();
         

	      $masterTrainers = User::select('_id','name')//,'orgDetails')
	      								->where('orgDetails.project_id',$project_id)
	      								->where('orgDetails.role_id',$masterTrainerRoleId[0]->_id)
	      								->where('orgDetails.location.state',$data['state_id'])
	      								//->orWhere('orgDetails.location.district',$data['district_id'])
	      								->get();

        if($masterTrainers)
             {
                $response_data = array('status'=>200,'data' => $masterTrainers,'message'=>"success");
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                return response()->json($response_data,300); 
            }

	}

	public function getMasterTrainerList(Request $request)
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
	      }else{

	        
	        $message['message'] = "insufficent header info";
	        $message['function'] = 'getMasterTrainerList'; 
	        $this->logData($this->logerrorPath ,$message,'Error');
	        $response_data = array('status' =>'404','message'=>$message);
	        return response()->json($response_data,200); 
	        // return $message;
	      }

	       $user = $this->request->user();
            // $all_user=User::select('role_id')->where('approve_status','pending')->get();
            $database = $this->connectTenantDatabase($request, $org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

           

           DB::setDefaultConnection('mongodb'); 

           $masterTrainerRoleId = Role::select('_id')
           								->where('project_id',$project_id)
           								->where('name','Smart Girl Master Trainer')
           								->get();

           			
         						
	      $masterTrainerList = User::select('_id','name')//,'orgDetails')
	      								->where('orgDetails.project_id',$project_id)
	      								
	      								->where('orgDetails.role_id',$masterTrainerRoleId[0]->_id)
	      								->get();


        if($masterTrainerList)
             {
                $response_data = array('status'=>200,'data' => $masterTrainerList,'message'=>"success");
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                return response()->json($response_data,300); 
            }

	}

    public function getTrainerList(Request $request)
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
	      }else{

	        
	        $message['message'] = "insufficent header info";
	        $message['function'] = 'getTrainerList'; 
	        $this->logData($this->logerrorPath ,$message,'Error');
	        $response_data = array('status' =>'404','message'=>$message);
	        return response()->json($response_data,200); 
	        // return $message;
	      }

	       $user = $this->request->user();
            // $all_user=User::select('role_id')->where('approve_status','pending')->get();
            $database = $this->connectTenantDatabase($request, $org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }


           DB::setDefaultConnection('mongodb'); 

           $trainerRoleId = Role::select('_id')
           								->where('project_id',$project_id)
           								->where('name','Smart Girl Trainer')
           								->get();

         	$trainerList = User::select('_id','name')//,'orgDetails')
	      								->where('orgDetails.project_id',$project_id)
	      								->where('orgDetails.role_id',$trainerRoleId[0]->_id)
	      								->get();

        if($trainerList)
             {
                $response_data = array('status'=>200,'data' => $trainerList,'message'=>"success");
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                return response()->json($response_data,300); 
            }

	}


	public function createBatch(Request $request)
	{
		  $header = getallheaders();
		  //$user = $this->request->user();
	      if(isset($header['orgId']) && ($header['orgId']!='') 
	        && isset($header['projectId']) && ($header['projectId']!='')
	        && isset($header['roleId']) && ($header['roleId']!='')
	        )
	      { 
	        $org_id =  $header['orgId'];
	        $project_id =  $header['projectId'];
	        $role_id =  $header['roleId'];
	      }else{

	        
	        $message['message'] = "insufficent header info";
	        $message['function'] = 'createBatch'; 
	        $this->logData($this->logerrorPath ,$message,'Error');
	        $response_data = array('status' =>'404','message'=>$message);
	        return response()->json($response_data,200); 
	        // return $message;
	      }

	      	$user = $this->request->user();
	      	$database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			$data = json_decode(file_get_contents('php://input'), true);
		 
			$this->logData($this->logInfoPath,$data,'DB');

			//echo json_encode($data);
			//$batchData = [];
			$batchData = new SmartGirlBatch();

			foreach($data as $key => $value)
			{

				$batchData[$key]= $value;


			}
			unset($batchData['startDate']);
			unset($batchData['endDate']);

			$batchData['schedule.startDate'] = $data['startDate']; 
			$batchData['schedule.endDate'] = $data['endDate'];

			$start_date_str = Carbon::createFromTimestamp($data['startDate'] / 1000)->toDateTimeString();
			$end_date_str = Carbon::createFromTimestamp($data['endDate'] / 1000)->toDateTimeString();
			$start_date_time = Carbon::parse($start_date_str)->startOfDay();  
			$end_date_time = Carbon::parse($end_date_str)->endOfDay();

			$dateFlag = $end_date_time->greaterThanOrEqualTo($start_date_time);

			if($dateFlag != 1)
			{
			 	$response_data = array('status' =>400,'message'=>"End date must be grater than start date.");
				return response()->json($response_data,200);
			}

			$batchData['schedule.starttiming'] =  new \MongoDB\BSON\UTCDateTime($data['startDate']);
			$batchData['schedule.endtiming'] = new \MongoDB\BSON\UTCDateTime($data['endDate']);

			$batchData['created_by']=$user->_id;
			


			try{ 

				$success = $batchData->save();
			}
			catch(Exception $e)
			{
				$response_data = array('status' =>'200','message'=>'error','data' => $e);
				return response()->json($response_data,200); 
			}

			if($success)
			{
			
				$msg = 'The batch has been creatd successfully.';
				$response_data = array('status' =>'200','message'=>$msg);
				return response()->json($response_data,200); 
			}
			else
			{
				$response_data = array('status' =>400,'message'=>"Couldn't create the batch, please try after some time.");
				return response()->json($response_data,200); 
			} 
			
	}

	public function editBatch(Request $request)
	{
	  $header = getallheaders();
	  //$user = $this->request->user();
      if(isset($header['orgId']) && ($header['orgId']!='') 
        && isset($header['projectId']) && ($header['projectId']!='')
        && isset($header['roleId']) && ($header['roleId']!='')
        )
      { 
        $org_id =  $header['orgId'];
        $project_id =  $header['projectId'];
        $role_id =  $header['roleId'];
      }else{

        
        $message['message'] = "insufficent header info";
        $message['function'] = 'createBatch'; 
        $this->logData($this->logerrorPath ,$message,'Error');
        $response_data = array('status' =>'404','message'=>$message);
        return response()->json($response_data,200); 
        // return $message;
      }

      	$user = $this->request->user();
      	$database = $this->connectTenantDatabase($request,$org_id);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
		$data = json_decode(file_get_contents('php://input'), true);
	 
		$this->logData($this->logInfoPath,$data,'DB');

		
		$batchData =  SmartGirlBatch::where('_id',$data['batch_id'])
								->first();


		foreach($data as $key => $value)
		{

			$batchData[$key]= $value;


		}
		$batchData['updated_by']=$user->_id;
		unset($batchData['startDate']);
		unset($batchData['endDate']);

		$batchData['schedule.startDate'] = $data['startDate']; 
		$batchData['schedule.endDate'] = $data['endDate'];
		$batchData['schedule.starttiming'] =  new \MongoDB\BSON\UTCDateTime($data['startDate']);
		$batchData['schedule.endtiming'] = new \MongoDB\BSON\UTCDateTime($data['endDate']);
		


		try{ 

			$success = $batchData->save();
		}
		catch(Exception $e)
		{
			$response_data = array('status' =>'200','message'=>'error','data' => $e);
			return response()->json($response_data,200); 
		}

		if($success)
		{
			$msg = 'The batch has been updated successfully.';
			$response_data = array('status' =>'200','message'=>$msg);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>400,'message'=>"Couldn't update the batch, please try after some time.");
			return response()->json($response_data,200); 
		} 
		
	}

	public function batchList(Request $request)
	{
		$header = getallheaders();
		//$user = $this->request->user();
		if(isset($header['orgId']) && ($header['orgId']!='') 
			&& isset($header['projectId']) && ($header['projectId']!='')
			&& isset($header['roleId']) && ($header['roleId']!='')
		)
		{ 
			$org_id =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		}else{


			$message['message'] = "insufficent header info";
			$message['function'] = 'batchList'; 
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}

		$user = $this->request->user();
		$database = $this->connectTenantDatabase($request,$org_id);
		$batchListData = SmartGirlBatch::
						 with('State')			
						//->where('district_id',$requestJson['district'])  
						->with('District')->get();
		$batchInfoCnt = 0;
		//$userName = [];
		DB::setDefaultConnection('mongodb'); 
		foreach ($batchListData as &$value) {
			
			
			//echo $value['additional_master_trainer']['user_id'];die();
			$userInfo = $this->getUserInfo($value['additional_master_trainer']['user_id']);
			if($userInfo)
			{	
			$value['additional_master_trainer'] = array_merge($value['additional_master_trainer'], array("user_name" => $userInfo[0]['name']));
			$value['additional_master_trainer'] = array_merge($value['additional_master_trainer'], array("user_phone" => $userInfo[0]['phone']));
			}
			//die(); 
			$batchInfoCnt = $batchInfoCnt +1;
		}

		if($batchListData)
             {
                $response_data = array('status'=>200,'data' => $batchListData,'message'=>"success");
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                return response()->json($response_data,300); 
            } 
	}

	public function getUserInfo($userId)
	{ 
		$userList =User::select('name','_id','phone')->where('_id', $userId)->get();

		if($userList)
		{
			return $userList;
		}
		//echo json_encode($userId);die();
	}


	public function batchDetails(Request $request, $batchId)
	{
		$header = getallheaders();
		//$user = $this->request->user();
		if(isset($header['orgId']) && ($header['orgId']!='') 
			&& isset($header['projectId']) && ($header['projectId']!='')
			&& isset($header['roleId']) && ($header['roleId']!='')
		)
		{ 
			$org_id =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		}else{


			$message['message'] = "insufficent header info";
			$message['function'] = 'batchList'; 
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}

		$user = $this->request->user();
		$database = $this->connectTenantDatabase($request,$org_id);
		$batchListData = SmartGirlBatch::where('_id',$batchId)
								->get();

		if($batchListData)
             {
                $response_data = array('status'=>200,'data' => $batchListData,'message'=>"success");
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                return response()->json($response_data,300); 
            } 

	}

	public function registerToBatch(Request $request)
	{
		$header = getallheaders();
		  //$user = $this->request->user();
	      if(isset($header['orgId']) && ($header['orgId']!='') 
	        && isset($header['projectId']) && ($header['projectId']!='')
	        && isset($header['roleId']) && ($header['roleId']!='')
	        )
	      { 
	        $org_id =  $header['orgId'];
	        $project_id =  $header['projectId'];
	        $role_id =  $header['roleId'];
	      }else{

	        
	        $message['message'] = "insufficent header info";
	        $message['function'] = 'createBatch'; 
	        $this->logData($this->logerrorPath ,$message,'Error');
	        $response_data = array('status' =>'404','message'=>$message);
	        return response()->json($response_data,200); 
	        // return $message;
	      }

	      $user = $this->request->user();
	      	$database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			$registerFormData = json_decode(file_get_contents('php://input'), true);
		 
			$this->logData($this->logInfoPath,$registerFormData,'DB');



	}


	public function FeedbackForBatch(Request $request)
	{
		  $header = getallheaders();
		  //$user = $this->request->user();
	      if(isset($header['orgId']) && ($header['orgId']!='') 
	        && isset($header['projectId']) && ($header['projectId']!='')
	        && isset($header['roleId']) && ($header['roleId']!='')
	        )
	      { 
	        $org_id =  $header['orgId'];
	        $project_id =  $header['projectId'];
	        $role_id =  $header['roleId'];
	      }else{

	        
	        $message['message'] = "insufficent header info";
	        $message['function'] = 'createBatch'; 
	        $this->logData($this->logerrorPath ,$message,'Error');
	        $response_data = array('status' =>'404','message'=>$message);
	        return response()->json($response_data,200); 
	        // return $message;
	      }

	      	$user = $this->request->user();
	      	$database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			$feedBackData = json_decode(file_get_contents('php://input'), true);
		 
			$this->logData($this->logInfoPath,$feedBackData,'DB');

			//echo json_encode($data);
			//$batchData = [];
			$batchData = new SmartGirlBatch();

			foreach($feedBackData as $key => $value)
			{

				$batchData[$key]= $value;


			}
			

			$batchData['schedule.starttiming'] =  new \MongoDB\BSON\UTCDateTime($data['startDate']);
			$batchData['schedule.endtiming'] = new \MongoDB\BSON\UTCDateTime($data['endDate']);

			$batchData['created_by']=$user->_id;
			


			try{ 

				$success = $batchData->save();
			}
			catch(Exception $e)
			{
				$response_data = array('status' =>'200','message'=>'error','data' => $e);
				return response()->json($response_data,200); 
			}

			if($success)
			{
			
				$msg = 'The batch has been creatd successfully.';
				$response_data = array('status' =>'200','message'=>$msg);
				return response()->json($response_data,200); 
			}
			else
			{
				$response_data = array('status' =>400,'message'=>"Couldn't create the batch, please try after some time.");
				return response()->json($response_data,200); 
			} 
			
	}

	//fetch all the list of members according to filter
	public function addmember(Request $request)
	{
		$request = json_decode(file_get_contents('php://input'), true);
		$userdetails = $this->request->user();
		$this->logData($this->logInfoPath,$request,'DB');
		$maindata = array(); 
		if(isset($request['org_id']))
		{	
	
			$org_id = explode(',',$request['org_id']);
			$maindata=User::select('name','role_id')->whereIn('org_id',$org_id)->orderBy('name','asc');
			
			if($request['role'] !='')
			{     
				$role = explode(',',$request['role']);
				$maindata->whereIn('role_id',$role);
				  if($request['state']!='')
				{
					$state = explode(',',$request['state']);
					$maindata->whereIn('location.state',$state);
				}
				if($request['district']!='')
				{
					$district = explode(',',$request['district']);
					$maindata->whereIn('location.district',$district);
				}
				if($request['taluka']!='')
				{
					$taluka = explode(',',$request['taluka']);
					$maindata->whereIn('location.taluka',$taluka);
				}
				if($request['village']!='')
				{
					$village = explode(',',$request['village']);
					$maindata->whereIn('location.village',$village); 
				} 
				
				 
			}else{
				$response_data = array('status' =>'404','message'=>'No Roles are Selected');
				return response()->json($response_data,200); 
			}
			$tempData = $maindata->get();
			 
			$main =array();
			foreach($tempData as $row)
			{
				$role_name = Role::select('display_name')->where('_id',$row['role_id'])->get();
				
				 if(count($role_name)==0){
					
				$temp_arr = array(
				'id'=>$row['_id'],
				'name'=>$row['name'],
				'role_name'=>''
				);
				}
				else{ 
				$temp_arr = array(
				'id'=>$row['_id'],
				'name'=>$row['name'],
				'role_name'=>$role_name[0]['display_name']
				);
				array_push($main,$temp_arr);  
				}
			}
			
		}
		 
		if($main)
		{
			$response_data = array('status' =>'200','message'=>'success','data' => $main);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'404','message'=>'No Members Found..');
			return response()->json($response_data,200); 
		}
		
	} 


	//Fetch all the events by Day
	public function getEventByDay(Request $request)
	{
		$org_id = $this->request->user();
		$database = $this->connectTenantDatabase($request,$org_id->org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			} 
		$requestjson = json_decode(file_get_contents('php://input'), true);
		$this->logData($this->logInfoPath,$requestjson,'DB');
		$type = $requestjson['type'];
		$month = $requestjson['month'];
		$day = $requestjson['day'];
		$year = $requestjson['year'];
		 
		 $userId = $org_id['_id'];
		//exit;
		$timestamp = strtotime($year."-".$month."-".$day);
		 
		$start_date_str = Carbon::createFromTimestamp($timestamp)->toDateTimeString();
		$start_date_time = Carbon::parse($start_date_str)->startOfDay(); 
		//$start_date_time1 = $start_date_time->subDays(1); 
		 
		$end_date_str = Carbon::createFromTimestamp($timestamp)->toDateTimeString();
		$end_date_time = Carbon::parse($end_date_str)->endOfDay(); 			 
		//$end_date_time1 = $end_date_time->addDays(1); 			 
		// echo $start_date_time."-".$end_date_time;  
		$maindata = PlannerTransactions:://select('type','title','thumbnail_image','address','description','schedule','ownerid','required_forms','event_status','registration_required','is_mark_attendance_required','participants_count','attended_completed','registration_schedule')
										where('schedule.starttiming','<=',$end_date_time)
										->where('schedule.endtiming','>=',$start_date_time)
										->where('type',$type)
										->orderby('schedule.starttiming','asc')
										->get();

	 
		$data = array(); 
		
		if($maindata){
		foreach($maindata as $row)
		{ 
			if($row['ownerid'] == $userId ) 
			{
			 array_push($data,$row);
			  unset($data["participants"]); 
			} 
			else
			{
				if($row['participants'])
				{
					foreach($row['participants'] as $participants )
					{
						if($participants['id'] == $userId){
							unset($row["participants"]); 
							 array_push($data,$row);
						}
					}
				}
				 
			}  
		}		
		}
		DB::setDefaultConnection('mongodb');
		
		// $maindata['name'] = $org_id->name; 
		if(count($data)>0)
		{
			$response_data = array('status' =>'200','message'=>'success','data' => $data);
			return response()->json($response_data,200); 
		}
		else
		{
			$response_data = array('status' =>'300','message'=>'No Data Found..');
			return response()->json($response_data,200); 
		}
		
	}
	
	//delete task
	public function deleteTask(Request $request,$taskId)
	{
		$org_id = $this->request->user();
		$this->logData($this->logInfoPath,$taskId,'DB');  
		$timestamp = Date('Y-m-d H:i:s');
		$database = $this->connectTenantDatabase($request,$org_id->org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
		try{
				$task=PlannerTransactions::find($taskId);
				if($task){
				$members = $task['participants'];
				$type = $task['type'];
				$title = $task['title'];
				 
				$task->delete($task->id); 
				DB::setDefaultConnection('mongodb'); 
				if($members){
				foreach($members as $mem)
				{
					$firebase_id = User::where('_id',$mem['id'])->first(); 
					$rolename = \App\Role::select('display_name')->where("_id",$firebase_id['role_id'])->first();
		
					 if(isset($rolename['display_name']))
					 { 
						$newrolename =  $rolename['display_name'];
					 }
					/* $this->sendPushNotification(
					$this->request,
					self::NOTIFICATION_TYPE_EVENT_DELETED,
					$firebase_id['firebase_id'],
					[
						'phone' => "9881499768",
						'title' => $title,
						'type' => $type,
						'rolename' => $newrolename,
						'update_status' => self::NOTIFICATION_TYPE_EVENT_DELETED,
						'model' => $type,
						'approval_log_id' => "Testing"
					],
					$firebase_id['org_id']
					);  */
						
				}
				
				$response_data = array('status' =>'200','message'=>'success');
				return response()->json($response_data,200); 		
				}				
			}else{
				$response_data = array('status' =>'200','message'=>'No Event Found');
				return response()->json($response_data,200); 
			}	
		}
			catch(exception $e)
			{
				$response_data = array('status' =>'200','message'=>'success','data' => $e);
				return response()->json($response_data,200); 
			}
			
	
	}


}

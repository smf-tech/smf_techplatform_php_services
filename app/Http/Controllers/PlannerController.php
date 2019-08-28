<?php

//owner:Sayli Dixit
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
use App\PlannerUserLeaveBalance;
use App\PlannerTransactions;
use App\PlannerAttendanceTransaction;
use App\PlannerLeaveApplications;
use App\PlannerHolidayMaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\ApprovalLog;
use Carbon\Carbon;
use App\Category;



use Illuminate\Support\Arr;

class PlannerController extends Controller
{
    use Helpers;
	/**
     *
     * @var Request
     */
    protected $request;

    public function __construct(Request $request) 
    {
        $this->request = $request;
    }

// function for getting dashboard data


    public function getDashBoardSummary(Request $request)
    {
            $user = $this->request->user();
            // $all_user=User::select('role_id')->where('approve_status','pending')->get();
            $database = $this->connectTenantDatabase($request,$user->org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

            $role_config=RoleConfig::where('role_id', $user->role_id)->get()->first();
            $default_modules = $on_approve_modules = [];
            if($role_config){
                $default_modules = $this->getmodules($role_config->default_modules);
                $on_approve_modules = $this->getmodules($role_config->on_approve_modules);
                $roleConfigData = ['default_modules'=>$default_modules,'on_approve_modules'=>$on_approve_modules];
            }
            //echo json_encode($roleConfigData);
           //exit;


            $emptyArray =[];

            
            
            //$leaveData = PlannerUserLeaveBalance::select('leave_balance')->where('user_id',$user->_id)->get();


            $leaveData['total'] = '20'; 
            $leaveData['used'] = '12'; 
            $leaveData['balance'] = '8'; 
           // total, used, balance

            //print_r();
            //exit;
            $currentDateTime = Carbon::now();
            $eventData = PlannerTransactions::where('type','Event')
                                        ->where('schedule.starttiming','>=',$currentDateTime)
                                        ->orWhere('ownerid',$user->_id)
                                        ->orWhere('participants.id',$user->_id)
                                        ->where('event_status','Active')->offset(0)->limit(2)
                                        ->orderby('schedule.starttiming','asc')

                                        ->get();

            //select('title','thumbnail_image','default.created_by')->
            $taskData = PlannerTransactions::where('type','Task')
                            ->where('schedule.starttiming','>=',$currentDateTime)
                            ->orWhere('ownerid',$user->_id)    
                            ->orWhere('participants.id',$user->_id)
                            ->where('event_status','Active')
                            ->offset(0)->limit(2)
                            ->orderby('schedule.starttiming','asc')
                            ->get();

                
          
            $currentDateStartTime = Carbon::now()->startOfDay();
            $currentDateEndTime = Carbon::now()->endOfDay();
      
            $attendanceData = PlannerAttendanceTransaction::
                            //select('created_at','created_on')
                           whereBetween('created_at', array($currentDateStartTime,$currentDateEndTime))
                          ->where('user_id',$user->_id)->get();   
                
            if(count($attendanceData) > 0 ) 
            {                      
            if(is_array($attendanceData[0]['check_out']) && $attendanceData[0]['check_out.time'] == 0)
                {
                    unset($attendanceData[0]['check_out']);
                }              
            }
           //echo json_encode($attendanceData);
           //die();

           
            $data = [
                [
                  "subModule"=> "attendance",
                  "attendanceData" => $attendanceData
                ],
                [ 
                    "subModule"=> "event",
                    "eventData" => $eventData
                ],
                         
                [
                  "subModule"=> "task",
                  "taskData" => $taskData
                ],
                [
                "subModule"=> "leave",
                //"leave"=> isset($leaveData[0])? $leaveData[0]['leave_balance']:$emptyArray
                "leave"=> $leaveData
                
                ]
                ];

                //echo json_encode($data);
               // die();
            
            if($data)
        {
            $response_data = array('status' =>'200', 'message' => 'success', 'data' => $data);
            return response()->json($response_data,200); 
        }
        else
        {
            $response_data = array('status' =>'error','data' => 'No rows found please check user id');
            return response()->json($response_data,300); 
        }
    }

     public function getmodules($module_ids){
        $modules =  Module::whereIn('_id', $module_ids)->get();
        return $modules;
    }

    public function getHolidayList(Request $request,$year,$month)
    {
        $user = $this->request->user();
        // $all_user=User::select('role_id')->where('approve_status','pending')->get();
        $database = $this->connectTenantDatabase($request,$user->org_id);
        if ($database === null) {
            return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
        }
         //echo $dt = Carbon::createFromFormat('m', 10); 

        $dt = Carbon::createFromDate($year, $month);

        $startDateMonth = new \MongoDB\BSON\UTCDateTime($dt->startOfMonth());
        $endDateMonth = new \MongoDB\BSON\UTCDateTime($dt->endOfMonth());
              
        $holidayList = PlannerHolidayMaster::select('Name','Date')
                       ->whereBetween('Date',array($startDateMonth,$endDateMonth))
                       ->where('type', 'holiday')
                       ->get();
        $holidayListData = [];
        $i =0;               
            foreach($holidayList as $holidayData)
            {
                $holidayListData[$i]['Name'] = $holidayData['Name'];
                $holidayListData[$i]['Date'] = (array)$holidayData['Date'];
                $i = $i+1;
            }               
                     

         //print_r($holidayListData);
         //exit;                             

        if($holidayList)
             {
                $response_data = array('status'=>200,'data' => $holidayListData,'message'=>"success");
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                return response()->json($response_data,300); 
            }

    }

    public function getYearHolidayList(Request $request)
    {
        $user = $this->request->user();
        // $all_user=User::select('role_id')->where('approve_status','pending')->get();
        $database = $this->connectTenantDatabase($request,$user->org_id);
        if ($database === null) {
            return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
        }

        $yearHolidayList = PlannerHolidayMaster::select('Name','holiday_date')
                              ->whereBetween('Date', array(Carbon::now()->startOfYear(),
                                Carbon::now()->endOfYear()))
                              ->where('type', 'holiday')
                              ->get();               
                     
                       

        if($yearHolidayList)
             {
                $response_data = array('status'=>200,'data' => $yearHolidayList,'message'=>"success");
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>300,'data' => 'No rows found','message'=>"error");
                return response()->json($response_data,200); 
            }

    }

    public function getUserLeaveBalance(Request $request)
    {
        $user = $this->request->user();
        // $all_user=User::select('role_id')->where('approve_status','pending')->get();
        $database = $this->connectTenantDatabase($request,$user->org_id);
        if ($database === null) {
            return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
        }

        $leaveData = PlannerUserLeaveBalance::select('leave_balance')->where('user_id',$user->_id)->get();

        //echo json_encode($leaveData[0]['leave_balance']);
        //die();


         if($leaveData)
            {
                $response_data = array('status' =>'200', 'message' => 'success', 'data' => $leaveData[0]['leave_balance']);
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>'error','data' => 'No rows found please check user id');
                return response()->json($response_data,300); 
            }

    }
	
	public function getUserRole($userId)
	{
		DB::setDefaultConnection('mongodb');  
		$Userdetails = User::find($userId); 
		$rolename = \App\Role::where("_id",$Userdetails['role_id'])->first(); 
		return $rolename;
	}

	public function getTeamAttendance(Request $request,$date)
	{ 
		$user = $this->request->user();
		$database = $this->connectTenantDatabase($request,$user->org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}	
            $userLocation = $user['location']; 
			$approverRoleConfig = \App\RoleConfig::where('approver_role', $user->role_id)->get();
			$levelIds = [];
			$jurisdictionIds = [];
			$roleIds = [];
		if(count($approverRoleConfig) > 0)
		{	
			foreach($approverRoleConfig as $approverData)
			{
				array_push($levelIds,$approverData['level']);
				array_push($jurisdictionIds,$approverData['jurisdiction_type_id']);
				array_push($roleIds,$approverData['role_id']);
			}
			
			if(!empty($approverRoleConfig))
			{  
				$levelDetail = \App\Jurisdiction::whereIn('_id',$levelIds)->get(); 
				
				$levelname = $levelDetail[0]->levelName;
				$jurisdictions = \App\JurisdictionType::whereIn('_id',$jurisdictionIds)->pluck('jurisdictions')[0];
				
				DB::setDefaultConnection('mongodb'); 
				$userList =\App\User::select('name','role_id')->whereIn('role_id', $roleIds);
				 
					 foreach ($jurisdictions as $singleLevel) { 
						if (isset($userLocation[strtolower($singleLevel)])) {
							$userList->whereIn('location.' . strtolower($singleLevel), $userLocation[strtolower($singleLevel)]); 
							if ($singleLevel == $levelname) { 
								break;
							} 
						} 
					}  
			}
			$user = $this->request->user();
			$database = $this->connectTenantDatabase($request,$user->org_id);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}	
            DB::setDefaultConnection('mongodb'); 
			$users = $userList->get(); 
			if($users)
			{ 
				$start_date_str = Carbon::createFromTimestamp($date /1000);

				$end_date_str = Carbon::createFromTimestamp($date /1000);

				$carbonStartDate = new Carbon($start_date_str);
				 $carbonStartDate->timezone = 'Asia/Kolkata';
				 $start_date_time = Carbon::parse($carbonStartDate)->startOfDay();


				$carbonEndDate = new Carbon($end_date_str);
				$carbonEndDate->timezone = 'Asia/Kolkata';
				$end_date_time = Carbon::parse($carbonEndDate)->endOfDay();
                //Carbon::parse($attendanceDate)->endOfDay()

				//echo $start_date_time = Carbon::($start_date)->startOfDay();  
				//echo '---end ----'.$end_date_time = Carbon::($end_date)->endOfDay();
                 
                $user = $this->request->user();
			    $database = $this->connectTenantDatabase($request,$user->org_id);
                $userOrgId = $user->org_id;
                if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }	
				$username = $users->pluck('_id'); 
				$allAttendance = PlannerAttendanceTransaction::select('check_in','check_out','user_id')->whereIn('user_id',$username)
														  ->where('created_at','>=',$start_date_time)
														  ->where('created_at','<=',$end_date_time)
														  ->get();

                                      
				$newAttendance = array();	
				$oldAttendancedata = array();
                $cnt = 0;	  
                   
                foreach($username as $user)
                {
                   
                      $database = $this->connectTenantDatabase($request,$userOrgId);
                    $attendanceInfo = PlannerAttendanceTransaction::select('check_in','check_out','user_id')
                            ->where('user_id',$user)
                            ->where('created_at','>=',$start_date_time)
                            ->where('created_at','<=',$end_date_time)
                            ->get();
                        

                       if(count($attendanceInfo) > 0 )
                       {
                          //echo $attendanceInfo[0]['user_id'];

                          DB::setDefaultConnection('mongodb'); 
                          $name = User::where('_id',$attendanceInfo[0]['user_id'])->get();
                          
                          $rolename= $this->getUserRole($attendanceInfo[0]['user_id']);
                          
                          $oldAttendancedata['name'] = $name[0]['name'];
                          if($name[0]['profile_pic'] !="" && $name[0]['profile_pic'] !=null)
                          $oldAttendancedata['imageUrl'] = $name[0]['profile_pic'];
                          $oldAttendancedata['role_name'] = $rolename['display_name'];
                          $oldAttendancedata['status'] = 'Present';
                          $oldAttendancedata['check_in'] = $attendanceInfo[0]['check_in'];
                          $oldAttendancedata['check_out'] = $attendanceInfo[0]['check_out']; 
                          
                           array_push($newAttendance,$oldAttendancedata);

                        }else {
                                $database = $this->connectTenantDatabase($request,$userOrgId);    
                                 $leave_application = PlannerLeaveApplications::where('user_id',$user)
                                              ->where('created_at','>=',$start_date_time)
                                              ->where('created_at','<=',$end_date_time)
                                              ->where('status.status','approved')
                                              ->get();

                                if(count($leave_application) > 0)
                                 {
                                     DB::setDefaultConnection('mongodb'); 
                                  $name = User::where('_id',$leave_application[0]['user_id'])->get();
                                 
                                  $rolename= $this->getUserRole($leave_application[0]['user_id']);
                                  $leaveData['name'] = $name[0]['name'];
                                  if($name[0]['profile_pic'] !="" && $name[0]['profile_pic'] !=null)
                                  $leaveData['imageUrl'] = $name[0]['profile_pic'];
                                  $leaveData['role_name'] = $rolename['display_name'];
                                  $leaveData['status'] = 'Leave';
                                  // $oldAttendancedata['check_in'] = array();
                                  // $oldAttendancedata['check_out'] = array();  
                                  array_push($newAttendance,$leaveData);
                                } else{

                                    DB::setDefaultConnection('mongodb'); 
                                    $name = User::where('_id',$user)->get();
                                    $rolename= $this->getUserRole($user);
                                    $absentData['name'] = $name[0]['name'];
                                    if($name[0]['profile_pic'] !="" && $name[0]['profile_pic'] !=null)
                                    $absentData['imageUrl'] = $name[0]['profile_pic'];
                                     $absentData['role_name'] = $rolename['display_name'];
                                    $absentData['status'] = 'Absent';
                                    // $oldAttendancedata['check_in'] = array();
                                    // $oldAttendancedata['check_out'] = array();  
                                    array_push($newAttendance,$absentData);

                                }  

                                         


                        }



                } 
              
			}  
			
			if($newAttendance)
			{
				$response_data = array('status' =>'300','message'=>'sucess','data' => $newAttendance);
				return response()->json($response_data,200);
			}else{
				$response_data = array('status' =>'300','message'=>'No Roles Found..');
				return response()->json($response_data,200);
			}
		}	
		else{
			$response_data = array('status' =>'300','message'=>'No Users Found..');
			return response()->json($response_data,200);
		}	
	}
	
						
			 
	
}
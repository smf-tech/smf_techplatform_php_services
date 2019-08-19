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

            
            
            $leaveData = PlannerUserLeaveBalance::select('leave_balance')->where('user_id',$user->_id)->get();

            //print_r();
            //exit;
            $currentDateTime = Carbon::now();
            $eventData = PlannerTransactions::where('type','Event')
                                        ->where('schedule.starttiming','>=',$currentDateTime)
                                        ->where('ownerid',$user->_id)
                                        ->whereOr('participants.id',$user->_id)
                                        ->where('event_status','Active')->offset(0)->limit(2)->get();

            //select('title','thumbnail_image','default.created_by')->
            $taskData = PlannerTransactions::where('type','Task')
                            //->where('schedule.starttiming','>=',$currentDateTime)
                            ->where('ownerid',$user->_id)    
                            ->whereOr('participants.id',$user->_id)->where('event_status','Active')->get();

                
          
            $currentDateStartTime = Carbon::now()->startOfDay();
            $currentDateEndTime = Carbon::now()->endOfDay();
      
            $attendanceData = PlannerAttendanceTransaction::
                            //select('created_at','created_on')
                           whereBetween('created_at', array($currentDateStartTime,$currentDateEndTime))
                          ->where('user_id',$user->_id)->get();              

           

           
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
                "leave"=> isset($leaveData[0])? $leaveData[0]['leave_balance']:$emptyArray
                
                ]
                ];
            
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


    public function getUserLeaveBalance(Request $request)
    {
            $user = $this->request->user();
            // $all_user=User::select('role_id')->where('approve_status','pending')->get();
            $database = $this->connectTenantDatabase($request,$user->org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

            $leaveData = PlannerUserLeaveBalance::select('leave_balance')->where('user_id',$user->_id)->get();

             if($leaveData)
            {
                $response_data = array('status' =>'200', 'message' => 'success', 'data' => $leaveData);
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
}
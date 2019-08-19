<?php

//owner:Kumood Bongale
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

class PlannerLeavesController extends Controller
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


    public function getLeavesSummary(Request $request,$year,$month)
    {
            $user = $this->request->user();
            // $all_user=User::select('role_id')->where('approve_status','pending')->get();
            $database = $this->connectTenantDatabase($request,$user->org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

            $dt = Carbon::createFromDate($year, $month);
            $startDateMonth = new \MongoDB\BSON\UTCDateTime($dt->startOfMonth());
            $endDateMonth = new \MongoDB\BSON\UTCDateTime($dt->endOfMonth()); 
			 // var_dump($startDateMonth);die();
			// $start_date_str = Carbon::createFromTimestamp($startDateMonth)->toDateTimeString();
			// $end_date_str = Carbon::createFromTimestamp($endDateMonth)->toDateTimeString();
			// $start_date_time = Carbon::parse($start_date_str)->startOfDay();  
			// $end_date_time = Carbon::parse($end_date_str)->endOfDay();	
            // $leaveBalanceData = PlannerUserLeaveBalance::select('leave_balance')->where('user_id',$user->_id)->get();
			
            $leaveAppliedData = PlannerLeaveApplications::select('leave_type','full_half_day','startdate','enddate','user_id','reason','paid_leave','status.status','status.rejection_reason')->where('startdates','>=',$startDateMonth)->where('startdates','<=',$endDateMonth )->where('user_id',$user->_id)->get();
            $leavesdata = array();
            foreach($leaveAppliedData as $leave){
                $leavedata=[
                    "id"=>$leave->_id,
                    "leave_type"=>$leave->leave_type,
                    "full_half_day"=> $leave->full_half_day,
                    "startdate"=> $leave->startdate,
                    "enddate"=> $leave->enddate,
                    "user_id"=> $leave->user_id,
                    "reason"=>$leave->reason,
                    "paid_leave"=>$leave->paid_leave,
                    "status"=> $leave->status['status'],
                    "rejection_reason"=>$leave->status['rejection_reason']
                ];
                array_push($leavesdata, $leavedata);
        }
            
            $holidaylist = PlannerHolidayMaster::select('name','holiday_date','type')->whereBetween('Date', array($startDateMonth,$endDateMonth))->get();
                   
           
            $data = [               
               "leaveData"=>$leavesdata,
               "holidayData"=>$holidaylist
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
}
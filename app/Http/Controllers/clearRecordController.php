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
use App\TestCollection; 
use App\PlannerAttendanceTransaction;
use App\PlannerClaimCompoffRequests;

use Illuminate\Support\Arr;
class clearRecordController extends Controller
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


    public  function clearRecord(Request $request)
    {
       $user = $this->request->user();
        // $all_user=User::select('role_id')->where('approve_status','pending')->get();
        $database = $this->connectTenantDatabase($request,$user->org_id);
        if ($database === null) {
            return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
        }

        $approvalLogData = PlannerAttendanceTransaction::select('_id')->get();
        foreach($approvalLogData as $data)
        {
            // echo $data->id;die();
                $logid = PlannerAttendanceTransaction::find($data->id);
                // var_dump( $Testid);die();
                $logid->delete();
        }
        

              
      //TestCollection::truncate();
      
        
    }

    

}
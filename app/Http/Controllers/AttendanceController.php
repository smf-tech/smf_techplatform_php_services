<?php

//owner:Kumood Suresh Bongale
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
use App\PlannerTransactions;
use App\PlannerAttendanceTransaction;
use App\PlannerLeaveApplications;
use App\PlannerHolidayMaster;
use App\ApprovalsPending;
use Jcf\Geocode\Geocode;

use Illuminate\Support\Arr;

class AttendanceController extends Controller
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


    //monthwise attendance of user
    public function getAttendanceByMonth(Request $request,$year,$month)
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
            // $attendance = PlannerAttendanceTransaction::select('created_on','status')->where('user_id',$user->_id)->get(); 
            // die();
            $attendance = PlannerAttendanceTransaction::whereBetween('created_at', array($startDateMonth,$endDateMonth))
                          ->where('user_id',$user->_id)->get();

            $holidayList = PlannerHolidayMaster::select('Name','holiday_date','Date')
                           ->whereBetween('Date',array($startDateMonth,$endDateMonth))
                           //->where('status', ture)
                           ->get();

            $data = [               
                        [
                            "subModule"=> "attendance",
                            "attendance"=>$attendance
                        ],
                        [
                            "subModule" => "holidayList",
                            "holidayList" => $holidayList
                        ]
                        
                    ];  


            if($attendance)
                 {
                    $response_data = array('status'=>200,'data' => $data,'message'=>"success");
                    return response()->json($response_data,200); 
                }
                else
                {
                    $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                    return response()->json($response_data,300); 
                }
    }

    //for inserting attendance record like check in or check out
    public function insertAttendance(Request $request)
    {
      $timestamp = Date('Y-m-d H:i:s');
      $user = $this->request->user();
      $data = json_decode(file_get_contents('php://input'), true);

      $database = $this->connectTenantDatabase($request,$user->org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }
    
      if($data['type']=='checkin')
      {

         $currentDateStartTime = Carbon::now()->startOfDay();
         $currentDateString = Carbon::now()->toDateString();
         $currentDateEndTime = Carbon::now()->endOfDay();

         $attendanceDate = Carbon::createFromTimestamp((int)$data['dates']/1000);



          $carbonDate = new Carbon($attendanceDate);
          $carbonDate->timezone = 'Asia/Kolkata';
          $attendanceDateString = $carbonDate->toDateString();
            

         if($currentDateString != $attendanceDateString)  
         {
       
              $response_data = array('status' =>'300','data'=>" ",'message'=>" Current, server Date and attendance date is not matching");
               return response()->json($response_data,200);
         }

    
         $attendanceInfo = PlannerAttendanceTransaction:://select('created_at')
                          //->
                          whereBetween('created_at', array($currentDateStartTime,$currentDateEndTime))
                          ->where('user_id',$user->_id)->get();
          // $leaveInfo =    PlannerLeaveApplications::select('')  
          $holidayInfo = PlannerHolidayMaster::select('holiday_date','type')
                           ->whereBetween('Date',array($currentDateStartTime,$currentDateEndTime))
                           ->where('type', 'holiday')
                           ->get();


                $leaveInfo  = PlannerLeaveApplications::where('startdates', '<=', Carbon::now())
                           ->where('enddates', '>=', Carbon::now())
                           ->where('user_id',$user->_id)
                          ->get();
          
               if(count($leaveInfo) > 0)
                {
                    // $response_data = array('status' =>'300','data'=>$leaveInfo,'message'=>"Your on leave, can't checkin!");
                    // return response()->json($response_data,200); 
                }    
                       
               if(count($holidayInfo) > 0)
                  {
                      //$response_data = array('status' =>'300','message'=>"Today is Holiday!");
                      //return response()->json($response_data,200); 
                  }                              


              
             if(count($attendanceInfo)==0)
                 {
                    
                    $attendanceData = new PlannerAttendanceTransaction();
                    $currentDateTime = new \MongoDB\BSON\UTCDateTime(Carbon::now());


                    $response = Geocode::make()->latLng($data['lattitude'],$data['longitude']);
           
                    if ($response)
                             {
                              $address =  $response->formattedAddress();
                             }else
                             {
                               $address =  "Unknown place";
                             }  

                    $attendanceData['user_id'] = $user->_id;
                    $attendanceData['check_in.lat'] = $data['lattitude'];
                    $attendanceData['check_in.long'] = $data['longitude'];


                    $attendanceData['check_in.time'] = $data['dates'];
                    $attendanceData['check_in.address'] = $address;
                    $attendanceData['check_out.lat'] = '';
                    $attendanceData['check_out.long'] = '';
                    $attendanceData['check_out.time'] = '';
                    $attendanceData['check_out.address'] = '';
                    $attendanceData['status'] = 'pending';
                    $attendanceData['created_on'] = $data['dates'];
                    $attendanceData['created_by'] = $user->_id;
                    $attendanceData['updated_on'] = $data['dates'];
                    $attendanceData['updated_by'] = $user->_id;
                    $attendanceData['org_id'] = $user->org_id;
                    $attendanceData['project_id'] = $user->project_id[0];

                    try{
                       $attendanceData->save(); 
                        $attendanceData->id=$attendanceData->_id;
                       $data = [
                       "attendanceId" => $attendanceData->id
                          ];
                        $approverUsers = array();
                        $approverList = $this->getApprovers($this->request,$user->role_id, $user->location, $user->org_id);
                        $approverIds =array();
                        foreach($approverList as $approver) { 
                        $approverIds = $approver['id'];  
                        
                          array_push($approverUsers,$approverIds);
                        } 
                       
					              $PendingApprovers = new ApprovalsPending;
							
						            $PendingApprovers['entity_id'] = $attendanceData->id;
                        $PendingApprovers['entity_type'] = "attendance";
                        $PendingApprovers['approver_ids'] = $approverUsers;
                        $PendingApprovers['status'] = "pending";
                        $PendingApprovers['userName'] = $user->_id;
                        $PendingApprovers['reason'] = "";
                        $PendingApprovers['createdDateTime'] = $currentDateTime;
                        $PendingApprovers['updatedDateTime'] = $currentDateTime;
                        $PendingApprovers['is_deleted'] = false;
                        
                        $PendingApprovers['default.org_id'] = $user->org_id;
                        $PendingApprovers['default.updated_by'] = "";
                        $PendingApprovers['default.created_by'] = $user->org_id;
                        $PendingApprovers['default.created_on'] = $timestamp;    
                        $PendingApprovers['default.updated_on'] = "";
                        $PendingApprovers['default.project_id'] = $user->project_id[0];	
					
						
                        $ApprovalLogs = new ApprovalLog;
        
                        $ApprovalLogs['entity_id'] = $attendanceData->id;
                        $ApprovalLogs['entity_type'] = "attendance";
                        $ApprovalLogs['approver_ids'] = $approverUsers;
                        $ApprovalLogs['status'] = "pending";
                        $ApprovalLogs['userName'] = $user->_id;
                        $ApprovalLogs['reason'] = "";
                        $ApprovalLogs['createdDateTime'] = $currentDateTime;
                        $ApprovalLogs['updatedDateTime'] = $currentDateTime;
                        $ApprovalLogs['is_deleted'] = false;
                        
                        $ApprovalLogs['default.org_id'] = $user->org_id;
                        $ApprovalLogs['default.updated_by'] = "";
                        $ApprovalLogs['default.created_by'] = $user->org_id;
                        $ApprovalLogs['default.created_on'] = $timestamp;    
                        $ApprovalLogs['default.updated_on'] = "";
                        $ApprovalLogs['default.project_id'] = $user->project_id[0]; 
                        $database = $this->connectTenantDatabase($request,$user->org_id);
                        if ($database === null) {
                          return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                        } 
                        try{
                          $ApprovalLogs->save(); 
                          $PendingApprovers->save(); 
						  
							foreach($approverUsers as $row){  
							
								DB::setDefaultConnection('mongodb');
								$firebase_id = User::where('_id',$row)->first(); 
								$this->sendPushNotification(
								$this->request,
								self::NOTIFICATION_TYPE_ATTENDANCE_APPROVAL,
								$firebase_id['firebase_id'],
								[
									'phone' => "9881499768",
									'update_status' => self::STATUS_PENDING,
									'approval_log_id' => "Testing"
								],
								$firebase_id['org_id']
								); 
								  
							}
						}
                        catch(exception $e)
                          {
                            $response_data = array('status' =>'200','message'=>'success','data' => $e);
                            return response()->json($response_data,200); 
                          }


                    }catch(Exception $e)
                    {
                      return $e;
                    } 

                    
                    if($attendanceData)
                    {
                      $response_data = array('status'=>200,'data' => $data,'message'=>"success");
                      return response()->json($response_data,200);
                    }   
                  }
                  else
                  {
                      $response_data = array('status' =>'300','data' =>$attendanceInfo ,'message'=>"Record is present for the date");
                      return response()->json($response_data,300); 
                  }

      }elseif($data['type']=='checkout')
      {
          $currentDateStartTime = Carbon::now()->startOfDay();
          $currentDateString = Carbon::now()->toDateString();
          $currentDateEndTime = Carbon::now()->endOfDay();

          $attendanceDate = Carbon::createFromTimestamp((int)$data['dates']/1000);

          $carbonDate = new Carbon($attendanceDate);
          $carbonDate->timezone = 'Asia/Kolkata';
          $attendanceDateString = $carbonDate->toDateString();


           if($currentDateString != $attendanceDateString)  
           {
                 $response_data = array('status' =>'300','data'=>" ",'message'=>" Current, server Date and attendance date is not matching");
                  return response()->json($response_data,200);
           }



          $taskData = PlannerTransactions::
                        where('schedule.endtiming','>=',$currentDateStartTime)->where('schedule.endtiming','<=',$currentDateEndTime)
                      //whereBetween('schedule.endtiming', array($currentDateStartTime,$currentDateEndTime))
                      ->where('ownerid',$user->_id)
                      ->where('mark_complete',false)  
                      ->get();
                      
            
            if(count($taskData) > 0)
             {
                $response_data = array('status' =>'300','data' => $taskData,'message'=>"Please complete your today's task");
                return response()->json($response_data,200); 
                
             }                            

            
          $attendanceData=PlannerAttendanceTransaction::where('_id',$data['attendanceId'])->where('user_id',$user->_id)->first(); 

          $response = Geocode::make()->latLng($data['lattitude'],$data['longitude']);
           
              if ($response)
                       {
                        $address =  $response->formattedAddress();
                       }else
                       {
                         $address =  "Unknown place";
                       } 
          
          $currentDateTime = new \MongoDB\BSON\UTCDateTime(Carbon::now()); 
          $attendanceData['check_out.lat'] = $data['lattitude'];
          $attendanceData['check_out.long'] = $data['longitude'];
          $attendanceData['check_out.time'] = $data['dates'];
          $attendanceData['check_out.address'] = $address;
          $attendanceData['totalHours'] = $data['totalHours'];
          $attendanceData['updated_on'] = $data['dates'];
          $attendanceData['updated_by'] = $user->_id;
          

         try{
             $attendanceData->save(); 
            }catch(Exception $e)
            {
              return $e;
            }  
          if($attendanceData)
          {
            $response_data = array('status'=>200,'message'=>"success");
            return response()->json($response_data,200);
          } else
          {
             $response_data = array('status' =>301,'data' => 'Record is present for the date','message'=>"error");
              return response()->json($response_data,301); 

          }
          

        
      }

    }

    //for getting attendance record for specific date
    public function attendanceOfDate(Request $request,$date)
    {
      $user = $this->request->user();
      // $all_user=User::select('role_id')->where('approve_status','pending')->get();
      $database = $this->connectTenantDatabase($request,$user->org_id);
      if ($database === null) {
          return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
        }


         $attendanceDate = Carbon::createFromTimestamp($date/1000);
         
         $start_date_time = Carbon::parse($attendanceDate)->startOfDay();  
         $end_date_time = Carbon::parse($attendanceDate)->endOfDay();  
       
       //exit;
        $attendance = PlannerAttendanceTransaction::
                       //where('created_at', '<=', $attendanceDate)
                       where('created_at','>=',$start_date_time)
                       ->where('created_at','<=',$end_date_time)
                       ->where('user_id',$user->_id)->get();
         $data = [               
                        [
                            "subModule"=> "attendance",
                            "attendance"=>$attendance
                        ]
                       
                        
                    ];  
           /* echo  json_encode($attendance);
            exit;  */      

            if($attendance)
                 {
                    $response_data = array('status'=>200,'data' => $data,'message'=>"success");
                    return response()->json($response_data,200); 
                }
                else
                {
                    $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                    return response()->json($response_data,200); 
                }              
    }   
  
}

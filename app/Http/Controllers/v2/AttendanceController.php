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
use App\LocationRoleBaseaddress;
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
        $this->logInfoPath = "logs/Attendance/DB/logs_".date('Y-m-d').'.log';
        $this->logerrorPath = "logs/Attendance/ERROR/logs_".date('Y-m-d').'.log';
    }

    //monthwise attendance of user
    public function getAttendanceByMonth(Request $request,$year,$month)
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
            $message['function'] = 'getAttendanceByMonth'; 
            $this->logData($this->logerrorPath ,$message,'Error');
            $response_data = array('status' =>'404','message'=>$message);
            return response()->json($response_data,200); 
            // return $message;
          }
        $user = $this->request->user();
        // $all_user=User::select('role_id')->where('approve_status','pending')->get();
        $database = $this->connectTenantDatabase($request,$user->org_id);
        if ($database === null) {
            return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
        }
         //echo $dt = Carbon::createFromFormat('m', 10); 
        //echo "dsadsa";
        //die();
        $dt = Carbon::createFromDate($year, $month);

        $startDateMonth = new \MongoDB\BSON\UTCDateTime($dt->startOfMonth());
        $endDateMonth = new \MongoDB\BSON\UTCDateTime($dt->endOfMonth());
        
         $data['year'] = $year;
         $data['month'] = $month;
         $data['function'] = 'getAttendanceByMonth';   
         $this->logData($this->logInfoPath ,$data,'DB');


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
          $message['function'] = 'insertAttendance'; 
          $this->logData($this->logerrorPath ,$message,'Error');
          $response_data = array('status' =>'404','message'=>$message);
          return response()->json($response_data,200); 
          // return $message;
        }

      $timestamp = Date('Y-m-d H:i:s');
      $user = $this->request->user();
      $data = json_decode(file_get_contents('php://input'), true);
      $data['function'] = 'insertAttendance'; 
      $data['user_id'] = $user->_id; 
      $this->logData($this->logInfoPath ,$data,'DB');


      $approverUsers = array();
      $approverList = $this->getApprovers($this->request,$role_id, $user->location, $org_id);
      $approverIds =array();
      foreach($approverList as $approver) { 
      $approverIds = $approver['id'];  
      
        array_push($approverUsers,$approverIds);
      }

      $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

             
    
      if($data['type']=='checkin')
      {
        
         DB::setDefaultConnection('mongodb');
         $role_name = Role::select('display_name')->where('_id',$role_id)->get();

         $database = $this->connectTenantDatabase($request,$org_id);
         $currentDateStartTime = Carbon::now()->startOfDay();
         $currentDateString = Carbon::now()->toDateString();
         $currentDateEndTime = Carbon::now()->endOfDay();

         $attendanceDate = Carbon::createFromTimestamp($data['dates']/1000);

         $start_date_time = new Carbon(Carbon::now());
         $start_date_time->timezone = 'Asia/Kolkata';
         $start_date = $start_date_time->startOfDay();


         $end_date_time = new Carbon(Carbon::now());
         $end_date_time->timezone = 'Asia/Kolkata';
         $end_date = $end_date_time->startOfDay();

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


                $leaveInfo  = PlannerLeaveApplications::
                            where('startdates', '<=', $start_date)
                            ->where('enddates', '>=', $end_date)
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


                  $rolaBaseAddress = LocationRoleBaseaddress::where('role_id',$role_id)->get();


                    if($rolaBaseAddress && count($rolaBaseAddress)>0)
                    {

                       

                      $latLngResponse = Geocode::make()->address($rolaBaseAddress[0]->address);


                      $latLngResponseData = $latLngResponse->response->geometry->location;


                      if($latLngResponseData)
                      {
                      $distance = Geocode::make()->getDistanceBetweenPoints($data['lattitude'],$data['longitude'],$latLngResponseData->lat,$latLngResponseData->lng);
                      //echo json_encode($distance);
                      //die();

                      }
                    }  
                    else
                    {
                      $distance['meters'] = 51;
                    }   
                    //echo $distance['meters'];
                   // die();
                    $attendanceData['user_id'] = $user->_id;
                    $attendanceData['check_in.lat'] = $data['lattitude'];
                    $attendanceData['check_in.long'] = $data['longitude'];


                    $attendanceData['check_in.time'] = $data['dates'];
                    $attendanceData['check_in.address'] = $address;
                    $attendanceData['check_out.lat'] = 0;
                    $attendanceData['check_out.long'] = 0;
                    $attendanceData['check_out.time'] = 0;
                    $attendanceData['check_out.address'] = 0;
                    // $attendanceData['status'] = 'pending';
                    if($distance['meters'] > 50)
                    {
                        $attendanceData['status.status'] = 'pending';
                    }else
                    {
                       $attendanceData['status.status'] = 'approved';
                    }
                    $attendanceData['status.action_by'] = $user->_id;
                    $attendanceData['status.action_on'] = $data['dates'];
                    $attendanceData['status.rejection_reason'] = '';
                    $attendanceData['created_on'] = $data['dates'];
                    $attendanceData['created_by'] = $user->_id;
                    $attendanceData['updated_on'] = $data['dates'];
                    $attendanceData['updated_by'] = $user->_id;
                    $attendanceData['org_id'] = $org_id;
                    $attendanceData['project_id'] = $project_id;
                    $attendanceData['role_id'] = $role_id;

                    try{
            
                    $data['date'] = $attendanceData;
                    $data['function'] = 'insertAttendance';   
                    $data['user_id'] = $user->_id;   
                    $this->logData($this->logInfoPath ,$data,'DB');
                    $attendanceData->save(); 
            
                    DB::setDefaultConnection('mongodb');
                    $firebase_id = User::where('_id',$user->_id)->first(); 

                    $this->sendPushNotification(
                    $this->request,
                    self::NOTIFICATION_TYPE_CHECKIN,
                    $firebase_id['firebase_id'],
                    [
                    'phone' => "9881499768",
                    'update_status' => self::STATUS_PENDING,
                    'approval_log_id' => "Testing"
                    ],
                    $firebase_id['org_id']
                    );
              
                    foreach($approverUsers as $row){       
                      $firebase_id = User::where('_id',$row)->first(); 
                      $this->sendPushNotification(
                      $this->request,
                      self::NOTIFICATION_TYPE_CHECKIN_APPROVAL,
                      $firebase_id['firebase_id'],
                      [
                       'phone' => "9881499768",
                       'update_status' => self::STATUS_PENDING,
                       'approval_log_id' => "Testing",
                       'rolename' => $role_name[0]['display_name']
                      ],
                      $firebase_id['org_id']
                      );  
                    }
                            
                            
                        $attendanceData->id=$attendanceData->_id;
                       $data = [
                       "attendanceId" => $attendanceData->id,
                       "Data" =>json_decode(file_get_contents('php://input'), true)
                          ];
                          
                        
                       
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
                        
                        $PendingApprovers['default.org_id'] = $org_id;
                        $PendingApprovers['default.updated_by'] = "";
                        $PendingApprovers['default.created_by'] = $org_id;
                        $PendingApprovers['default.created_on'] = $timestamp;    
                        $PendingApprovers['default.updated_on'] = "";
                        $PendingApprovers['default.project_id'] = $project_id;  
                        $PendingApprovers['default.role_id'] = $role_id;  

                        
          
            
                        $ApprovalLogs = new ApprovalLog;
        
                        $ApprovalLogs['entity_id'] = $attendanceData->id;
                        $ApprovalLogs['entity_type'] = "attendance";
                        $ApprovalLogs['approver_ids'] = $approverUsers;
                       if($distance['meters'] > 50)
                        {
                            $ApprovalLogs['status'] = "pending";
                        }else
                        {
                           $ApprovalLogs['status'] = 'approved';
                        }
                        $ApprovalLogs['userName'] = $user->_id;
                        $ApprovalLogs['reason'] = "";
                        $ApprovalLogs['createdDateTime'] = $currentDateTime;
                        $ApprovalLogs['updatedDateTime'] = $currentDateTime;
                        $ApprovalLogs['is_deleted'] = false;
                        
                        $ApprovalLogs['default.org_id'] = $org_id;
                        $ApprovalLogs['default.updated_by'] = "";
                        $ApprovalLogs['default.created_by'] = $org_id;
                        $ApprovalLogs['default.created_on'] = $timestamp;    
                        $ApprovalLogs['default.updated_on'] = "";
                        $ApprovalLogs['default.project_id'] = $project_id; 
                        $ApprovalLogs['default.role_id'] = $user->role_id; 
                        $database = $this->connectTenantDatabase($request,$user->org_id);
                        if ($database === null) {
                          return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                        } 
                        try{ 
                          $ApprovalLogs->save();
                        if($distance['meters'] > 50)
                          { 
                            $PendingApprovers->save();
                             DB::setDefaultConnection('mongodb');
                            
                            foreach($approverUsers as $row){  
               
                             
                              $firebase_id = User::where('_id',$row)->first(); 
                              $this->sendPushNotification(
                              $this->request,
                              self::NOTIFICATION_TYPE_ATTENDANCE_APPROVAL,
                              $firebase_id['firebase_id'],
                              [
                               'phone' => "9881499768",
                               'update_status' => self::STATUS_PENDING,
                               'approval_log_id' => "Testing",
                               'rolename' => $role_name[0]['display_name']
                              ],
                              $firebase_id['org_id']
                              ); 
                                
                            }
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
                      $response_data = array('status'=>200,'data' => $data,'message'=>"checked-in successfully");

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

          $attendanceDate = Carbon::createFromTimestamp($data['dates']/1000);

          $carbonDate = new Carbon($attendanceDate);
          $carbonDate->timezone = 'Asia/Kolkata';
          $attendanceDateString = $carbonDate->toDateString();


           if($currentDateString != $attendanceDateString)  
           {
                 $response_data = array('status' =>'300','data'=>" ",'message'=>" Current, server Date and attendance date is not matching");
                  return response()->json($response_data,200);
           }
             $database = $this->connectTenantDatabase($request,$user->org_id);
            if ($database === null) {
              return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            } 
  

          $taskData = PlannerTransactions::
                        where('schedule.endtiming','>=',$currentDateStartTime)->where('schedule.endtiming','<=',$currentDateEndTime)
                      //whereBetween('schedule.endtiming', array($currentDateStartTime,$currentDateEndTime))
                      ->where('ownerid',$user->_id)
                      ->where('mark_complete',false)  
                      ->get();
                      
            
            // if(count($taskData) > 0)
            //  {
            //     $response_data = array('status' =>'300','data' => $taskData,'message'=>"Please complete your today's task");
            //     return response()->json($response_data,200); 
                
            //  }                            

            
       $attendanceData=PlannerAttendanceTransaction::where('_id',$data['attendanceId'])->where('user_id',$user->_id)->first(); 
       if($attendanceData == Null)
       {
            $response_data = array('status' =>300, 'message' => 'Checkin Record not present, Please Checkin first.');
              return response()->json($response_data,200); 
       }
           if(isset($attendanceData['check_out.time']) && ($attendanceData['check_out.time']!='' ))
          {

             $response_data = array('status' =>300, 'data'=>$attendanceData ,'message' => 'Record is present for the date','message'=>"error");
              return response()->json($response_data,200); 

          }  

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
          //$attendanceData['totalHours'] = $data['totalHours'];
          $attendanceData['updated_on'] = $data['dates'];
          $attendanceData['updated_by'] = $user->_id;
          

         try{
              $data['date'] = $attendanceData;
              $data['function'] = 'insertAttendance-checkout';   
              $data['user_id'] = $user->_id;   
              $this->logData($this->logInfoPath ,$data,'DB');
             $attendanceData->save(); 


                DB::setDefaultConnection('mongodb');
                $firebase_id = User::where('_id',$user->_id)->first(); 

                $this->sendPushNotification(
                $this->request,
                self::NOTIFICATION_TYPE_CHECKOUT,
                $firebase_id['firebase_id'],
                [
                'phone' => "9881499768",
                'update_status' => self::STATUS_PENDING,
                'approval_log_id' => "Testing"
                ],
                $firebase_id['org_id']
                );

             //check out notification code start from here
              // DB::setDefaultConnection('mongodb');
              //   $role_name = Role::select('display_name')->where('_id',$role_id)->get();
                  
              //   foreach($approverUsers as $row){  
   
                 
              //     $firebase_id = User::where('_id',$row)->first(); 
              //     $this->sendPushNotification(
              //     $this->request,
              //     self::NOTIFICATION_TYPE_ATTENDANCE_APPROVAL,
              //     $firebase_id['firebase_id'],
              //     [
              //      'phone' => "9881499768",
              //      'update_status' => self::STATUS_PENDING,
              //      'approval_log_id' => "Testing",
              //      'rolename' => $role_name[0]['display_name']
              //     ],
              //     $firebase_id['org_id']
              //     ); 
                    
              //   } //check out notification code end here

            }catch(Exception $e)
            {
              return $e;
            }  
          if($attendanceData)
          {

            $responseData = [
                       
                       "Data" =>json_decode(file_get_contents('php://input'), true)
                          ];

            $response_data = array('status'=>200,'data'=>$responseData,'message'=>"checked-out successfully");
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
          $message['function'] = "attendanceOfDate";
           $data['user_id'] = $user->_id;
          $this->logData($this->logerrorPath ,$message,'Error');
          $response_data = array('status' =>'404','message'=>$message);
          return response()->json($response_data,200); 
          // return $message;
        }

      $user = $this->request->user();
      // $all_user=User::select('role_id')->where('approve_status','pending')->get();
      $database = $this->connectTenantDatabase($request,$org_id);
      if ($database === null) {
          return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
        }


         $attendanceDate = Carbon::createFromTimestamp($date/1000);
         
         $start_date_time = Carbon::parse($attendanceDate)->startOfDay();  
         $end_date_time = Carbon::parse($attendanceDate)->endOfDay();  
         
         $data['date'] = $date;
         $data['function'] = 'attendanceOfDate';   
         $this->logData($this->logInfoPath ,$data,'DB'); 
       //exit;
        $attendance = PlannerAttendanceTransaction::
                       //where('created_at', '<=', $attendanceDate)
                       where('created_at','>=',$start_date_time)
                       ->where('created_at','<=',$end_date_time)
                       ->where('org_id',$org_id)
                       ->where('project_id',$project_id)
                       ->where('role_id',$role_id)
                       ->where('user_id',$user->_id)->get();
          //echo json_encode($attendance);
          //die();             
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



    public function getTeamUserAttendance(Request $request)
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
            $message['function'] = 'getTeamUserAttendance'; 
            $this->logData($this->logerrorPath ,$message,'Error');
            $response_data = array('status' =>'404','message'=>$message);
            return response()->json($response_data,200); 
            // return $message;
          }
       
        $database = $this->connectTenantDatabase($request,$org_id);
        if ($database === null) {
            return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
        }
         
         $requestData = json_decode(file_get_contents('php://input'), true);



         $year = $requestData['year'];
         $month = $requestData['month'];
         $user_id = $requestData['userId'];

         $data['year'] = $year;
         $data['month'] = $month;
         $data['user_id'] = $user_id;
         $data['function'] = 'getTeamUserAttendance';   
         $this->logData($this->logInfoPath ,$data,'DB');

         $yearMonth = $requestData['year'].'-'.$month;
         $start = Carbon::parse($yearMonth)->startOfMonth();
          $end = Carbon::parse($yearMonth)->endOfMonth();

          $monthDates = [];
          while ($start->lte($end)) {
               $monthDates[] = $start->copy();
               $start->addDay();
          }

          $newAttendance = array(); 
          $oldAttendancedata = array();
          $cnt = 0;
           $tomorrowDate = Carbon::tomorrow('Asia/Kolkata');//->endOfDay();
        
         // echo "<pre>", print_r($monthDates), "</pre>";
          foreach($monthDates as $dateData)
          {

             $dateData->toDateTimeString();

             $carbonStartDate = new Carbon($dateData->toDateTimeString());
              $carbonStartDate->timezone = 'Asia/Kolkata';
             $start_date_time = Carbon::parse($carbonStartDate)->startOfDay();

              $carbonEndDate = new Carbon($dateData->toDateTimeString());
              $carbonEndDate->timezone = 'Asia/Kolkata';
             $end_date_time = Carbon::parse($carbonEndDate)->endOfDay();
           //die();
              if(Carbon::parse($start_date_time)->lte(Carbon::now('Asia/Kolkata')))
              {
                
                    $database = $this->connectTenantDatabase($request,$org_id);
                    $attendanceInfo = PlannerAttendanceTransaction::select('check_in','check_out','user_id')
                            ->where('user_id',$user_id)
                            ->where('created_at','>=',$start_date_time)
                           ->where('created_at','<=',$end_date_time)
                            ->get();
                       

                       if(count($attendanceInfo) > 0 )
                       {
                         
                          $oldAttendancedata['date'] = $dateData->toDateTimeString();
                          $oldAttendancedata['status'] = 'Present';
                          $oldAttendancedata['check_in'] = $attendanceInfo[0]['check_in'];
                          $oldAttendancedata['check_out'] = $attendanceInfo[0]['check_out'];
                           $oldAttendancedata['user_id'] = $attendanceInfo[0]['user_id'];  
                          
                           array_push($newAttendance,$oldAttendancedata);

                        }else {
                                $database = $this->connectTenantDatabase($request,$org_id);    
                                 $leave_application = PlannerLeaveApplications::where('user_id',$user_id)
                                              ->where('startdates','>=',$start_date_time)
                                              ->where('enddates','<=',$end_date_time)
                                              ->where('status.status','approved')
                                              ->get();

                                if(count($leave_application) > 0)
                                 {
                                    
                                 
                                 $leaveData['user_id'] = $leave_application[0]['user_id'];
                                  $leaveData['date'] = $dateData->toDateTimeString();
                                  $leaveData['status'] = 'Leave';
                                   
                                  array_push($newAttendance,$leaveData);
                                } else{
                   
                                    $absentData['user_id'] = $user_id;
                                    $absentData['date'] = $dateData->toDateTimeString();
                                    $absentData['status'] = 'Absent';
                                    
                                    array_push($newAttendance,$absentData);

                                }  


                        }
          
              }
               
          }

         
       
        $dt = Carbon::createFromDate($year, $month);

        $startDateMonth = new \MongoDB\BSON\UTCDateTime($dt->startOfMonth());
        $endDateMonth = new \MongoDB\BSON\UTCDateTime($dt->endOfMonth());

     
        


         $attendancedata = array();
       
         $cnt = 0;
         
        $attendance = PlannerAttendanceTransaction::
                        select('check_in','check_out',' user_id')
                        ->whereBetween('created_at', array($startDateMonth,$endDateMonth))
                      ->where('user_id',$user_id)->get();


       

            if($attendance)
            {  
              $totalSeconds = 0;          
              foreach($attendance as $data)
              {
                

                 if($data->check_in['time'] && $data->check_out['time']!=0  )
                 {
                    
                    
                    $checkin=Carbon::createFromTimestamp($data->check_in['time'] /1000);
                    $checkout= Carbon::createFromTimestamp($data->check_out['time'] /1000);
                    
                     //echo 'timediff'.$checkin->diffInSeconds($checkout);
                     $totalSeconds = ($checkin->diffInSeconds($checkout))+$totalSeconds;
                 }

                 $cnt = $cnt+1;
              }            
            }

            $minutes = round($totalSeconds / 60);
            $totalHours = floor($minutes / 60);
            $remainMinutes = ($minutes % 60);
        
          $data = $newAttendance;
      $totalWorkingHours = $totalHours.' hrs '.$remainMinutes.' mins';      


        if($attendance)
             {
                $response_data = array('status'=>200,'data' => $data,'totalWorkingHours' => $totalWorkingHours,'message'=>"success");
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                return response()->json($response_data,300); 
            }
    
    } 
  
}

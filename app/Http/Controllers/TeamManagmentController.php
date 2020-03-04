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
use App\Entity;
use App\RoleConfig;
use App\Event;
use App\EventType; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\ApprovalLog;
use Carbon\Carbon;
use App\Category;
use App\Survey;
use DateTime;
use App\PlannerAttendanceTransaction;
use App\PlannerLeaveApplications;
use App\PlannerUserLeaveBalance;
use App\ApprovalsPending;
use App\PlannerClaimCompoffRequests;
 

use Illuminate\Support\Arr;

class TeamManagmentController extends Controller
{
    use Helpers;
    
    protected $request;

    public function __construct(Request $request) 
    {
        $this->request = $request;
		$this->logInfoPath = "logs/TeamManagment/DB/logs_".date('Y-m-d').'.log';
		$this->logerrorPath = "logs/TeamManagment/Error/logs_".date('Y-m-d').'.log';
    }

// function for getting dashboard data
    public function getallcount(Request $request)
    {
            $user = $this->request->user();
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
				$message['function'] = "getallcount";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
            // $all_user=User::select('role_id')->where('approve_status','pending')->get();
            $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

           // $new_role_id = RoleConfig::selectRaw('role_id')->where('approver_role',$user->role_id)->get();

            $entity_type = ApprovalsPending::distinct()->select('entity_type')->where('status','pending')->where('approver_ids',$user->_id)->get();
          
            $form_count= count(ApprovalsPending::where('status','pending')->where('approver_ids',$user->_id)->where('entity_type','form')->get());
             $user_count= count(ApprovalsPending::where('status','pending')->where('approver_ids',$user->_id)->where('entity_type','userapproval')->get());
             $leave_count= count(ApprovalsPending::where('status','pending')->where('approver_ids',$user->_id)->where('entity_type','leave')->get());
            $attendance_count= count(ApprovalsPending::where('status','pending')->where('approver_ids',$user->_id)->where('entity_type','attendance')->get());
            $compoff_count=count(ApprovalsPending::where('status','pending')->where('approver_ids',$user->_id)->where('entity_type','compoff')->get());;

            //  DB::setDefaultConnection('mongodb');
            //  $users=[];
            // foreach($new_role_id as $role)
            // {
            //    unset($role['_id']);
            //     $user_data = User::where('role_id',$role->role_id)->where('approve_status','pending')->where('location.state',$user->location['state'][0])->get();
                
            //    array_push($users,$user_data);
            // }

            

            // $user_count = count($users);
            $data = [[
                "id"=>123,
                "approvalType"=>"User Approval",
                "type"=>"userapproval",
                "pendingCount"=>$user_count
            ],
            [
                "id"=>124,
                "approvalType"=>"Form Approval",
                 "type"=>"forms",
                "pendingCount"=>$form_count
            ],
            [
                "id"=>125,
                "approvalType"=>"Attendance Approval",
                 "type"=>"attendance",
                "pendingCount"=>$attendance_count
            ],
            [
                "id"=>126,
                "approvalType"=>"Leave Approval",
                 "type"=>"leave",
                "pendingCount"=>$leave_count
            ],
            [
                "id"=>127,
                "approvalType"=>"CompOff Approval",
                 "type"=>"compoff",
                "pendingCount"=>$compoff_count
            ]

        ];
            
            if($data)
        {
            $response_data = array('status' =>200,'data' => $data,'message'=>"success");
            return response()->json($response_data,200); 
        }
        else
        {
            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
            return response()->json($response_data,300); 
        }
    }

    public function getfilterbytype(Request $request)
    {
         $user = $this->request->user();
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
				$message['function'] = "getfilterbytype";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
         $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

            $category= Category::select('name','name')->where('type','Form')->get();

          $data = [
            [
                "id"=>123,
                "approvalType"=>"User Approval",
                "type"=>"userapproval",
                "filterSet"=>[
                    [
                    "id"=>123,
                    "filterType"=>"date",
                    "name"=>[
                        "default"=>"date",
                        "hi"=>"तारीख",
                        "mr"=>"दिनांक"
                        ]
                    ]
                ]
            ],
            [
                "id"=>123,
                "approvalType"=>"Form Approval",
                "type"=>"forms",
                "filterSet"=>[
                  [ 
                    "id"=>123,
                    "filterType"=>"date",
                    "name"=>[
                        "default"=>"date",
                        "hi"=>"तारीख",
                        "mr"=>"दिनांक"
                   ]
                  ],
                  [ 
                    "id"=>123,
                    "filterType"=>"category",
                    "filterset"=>$category
                  ],
                ]
            ],
            [
               "id"=>123,
                "approvalType"=>"Attendance Approval",
                "type"=>"attendance",
                "filterSet"=>[[
                    "id"=>123,
                    "filterType"=>"date",
                    "name"=>[
                        "default"=>"date",
                        "hi"=>"तारीख",
                        "mr"=>"दिनांक"
                   ]
                ]]
            ],
            [
                "id"=>123,
                "approvalType"=>"Leave Approval",
                "type"=>"leave",
                "filterSet"=>[[
                    "id"=>123,
                    "filterType"=>"date",
                    "name"=>[
                        "default"=>"date",
                        "hi"=>"तारीख",
                        "mr"=>"दिनांक"
                   ]
                ]]
            ],
            [
                "id"=>123,
                "approvalType"=>"CompOff Approval",
                "type"=>"compoff",
                "filterSet"=>[[
                    "id"=>123,
                    "filterType"=>"date",
                    "name"=>[
                        "default"=>"date",
                        "hi"=>"तारीख",
                        "mr"=>"दिनांक"
                   ]
                ]]
            ]

        ];
            
            if($data)
        {
            $response_data = array('status' =>200,'data' => $data,'message'=>"success");
            return response()->json($response_data,200); 
        }
        else
        {
            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
            return response()->json($response_data,300); 
        }

    }

  public function getListByFilter(Request $request)
    {
        $user = $this->request->user();
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
			$message['function'] = "getlistbyfilter";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200);  
		}
        $data = json_decode(file_get_contents('php://input'), true);
		$data['function'] = "getlistbyfilter";
		$this->logData($this->logInfoPath ,$data,'DB');
        $approval_type = $data['approval_type'];
        if($data['type']=='forms')
        {

             $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

            
                $filtertype = $data['filterSet']['filterType'];

                if(empty($filtertype))
                {
                    
                    /* $start = (int)$data['filterSet']['start_date'];
                    $end = (int)$data['filterSet']['end_date']; 
                    $sdt = Carbon::createFromTimestamp($start);
                    $start_date = new \MongoDB\BSON\UTCDateTime($sdt);
                    $edt = Carbon::createFromTimestamp($end);
                    $end_date = new \MongoDB\BSON\UTCDateTime($edt); */


                    // $start_date_str = Carbon::createFromTimestamp($data['filterSet']['start_date'] /1000)->toDateTimeString();
                    // $end_date_str = Carbon::createFromTimestamp($data['filterSet']['end_date'] /1000)->toDateTimeString();
                    // $start_date_time = Carbon::parse($start_date_str)->startOfDay();  
                    // $end_date_time = Carbon::parse($end_date_str)->endOfDay();  

                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();

                    if($approval_type == 'pending'){
                    $user_id = ApprovalsPending::select('userName')
                                ->where('entity_type','form')
                                ->where('approver_ids',$user->_id)
                                //->whereBetween('created_at',array($start_date,$end_date))
                                ->where('created_at','>=',$start_date_str)->where('created_at','<=',$end_date_time)
                                ->where('status', $approval_type)->get();

                              

                    }
                    else{
                     $user_id = ApprovalLog::select('userName')
                                ->where('entity_type','form')
                                ->where('approver_ids',$user->_id)
                                //->whereBetween('created_at',array($start_date,$end_date))
                                ->where('created_at','>=',$start_date_str)->where('created_at','<=',$end_date_time)
                                ->where('status', $approval_type)->get();
                    }
                    $uid = $user_id->pluck('userName');
                    DB::setDefaultConnection('mongodb');
                    $usernew = User::whereIn('_id',$uid)->get();
                    $roleData = \App\Role::get();
                        $i=0;
                        foreach ($usernew as $userValues) {
                            
                            foreach ($roleData as $roleValue) {
                                if($userValues['role_id'] == $roleValue['_id'])
                                {
                                    //echo $userValues['role_id'];
                                    $usernew[$i]['role_id'] = $roleValue['display_name'];

                                }
                                
                            }
                           $i= $i+1;
                        }
                     if($usernew)
                        {
                            $response_data = array('status' =>200,'data' => $usernew,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,300); 
                        }

                } elseif($filtertype == 'category')
                {
                    /* $start = (int)$data['filterSet']['start_date'];
                    $end = (int)$data['filterSet']['end_date']; 
                    $sdt = Carbon::createFromTimestamp($start);
                    $start_date = new \MongoDB\BSON\UTCDateTime($sdt);
                    $edt = Carbon::createFromTimestamp($end);
                    $end_date = new \MongoDB\BSON\UTCDateTime($edt);  */
                    // $start_date_str = Carbon::createFromTimestamp($data['filterSet']['start_date'] /1000)->toDateTimeString();
                    // $end_date_str = Carbon::createFromTimestamp($data['filterSet']['end_date']  /1000)->toDateTimeString();
                    // $start_date_time = Carbon::parse($start_date_str)->startOfDay();  
                    // $end_date_time = Carbon::parse($end_date_str)->endOfDay();  




                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();



                    
                    $id = $data['filterSet']['id'];
                     
                    $entity_id = Survey::select('entity_id')->whereIn('category_id',$id)->get();  
                    $eid = $entity_id->pluck('entity_id');
                    $eids=$eid->toArray(); 
                    if($approval_type == 'pending'){ 


                    $user_id = ApprovalsPending::select('userName','entity_id')
                            ->where('entity_type','form')
                            ->where('status',$approval_type)
                            ->where('approver_ids',$user->_id)
                            //->whereIn('entity_id',$eids)
                            ->where('created_at','>=',$start_date_time)
                            ->where('created_at','<=',$end_date_time)
                            ->get();
                    // echo json_encode($user_id);
                    //exit();
                    $sid = $user_id->pluck('entity_id');
                    $sids=$sid->toArray(); 
                     
                    $surveyId = Survey::select('microservice_id')->whereIn('entity_id',$sids)->get(); 
                    $ssid = $surveyId->pluck('microservice_id');
                    $ssids=$ssid->toArray(); 
                    }
                    else{
                        
                         $user_id = ApprovalLog::select('userName','entity_id')->where('entity_type','form')->where('status',$approval_type)->where('approver_ids',$user->_id)->whereIn('entity_id',$eids)->where('created_at','>=',$start_date_time)->where('created_at','<=',$end_date_time)->get();
                    }
                 
                    $uid = $user_id->pluck('userName');
                    DB::setDefaultConnection('mongodb');
                    $usernew = User::whereIn('_id',$uid)->get();
                    $roleData = \App\Role::get();
                        $i=0;
                        foreach ($usernew as $userValues) {
                            
                            foreach ($roleData as $roleValue) {
                                if($userValues['role_id'] == $roleValue['_id'])
                                {
                                    //echo $userValues['role_id'];
                                    $usernew[$i]['role_id'] = $roleValue['display_name'];
                                    $usernew[$i]['microservice_id'] = $ssids;

                                }
                                
                            }
                           $i= $i+1;
                        }
                     if($usernew)
                        {
                            // $usernew['microservice_id'] = $ssids;
                            $response_data = array('status' =>200,'data' =>$usernew ,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,300); 
                        }

                }
        }
        if($data['type']=='userapproval')
        {
            
             $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }
                 
                $filtertype = $data['filterSet']['filterType'];
                if(empty($filtertype))
                {
     //                $start_date_str = Carbon::createFromTimestamp($data['filterSet']['start_date'] /1000)->toDateTimeString();
                    // $end_date_str = Carbon::createFromTimestamp($data['filterSet']['end_date'] /1000)->toDateTimeString();
                    // $start_date_time = Carbon::parse($start_date_str)->startOfDay();  
                    // $end_date_time = Carbon::parse($end_date_str)->endOfDay();  
                    

                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();

                 
                    if($approval_type == 'pending'){
						 
                    $user_id = ApprovalsPending::select('userName')
                                ->where('entity_type','userapproval')
                                ->where('approver_ids',$user->_id) 
                                ->where('created_at','>=',$start_date_time)
                                ->where('created_at','<=',$end_date_time) 
                                ->where('status',$approval_type)
                                ->get();
                                $uid = $user_id->pluck('userName');
								 
                    }
                    else{  
                     $user_id = ApprovalLog::select('entity_id')
                                  ->where('entity_type','userapproval')
                                  ->where('action_by',$user->_id) 
                                  ->where('created_at','>=',$start_date_time)
                                  ->where('created_at','<=',$end_date_time)
								 // ->where('default.project_id',$project_id)
                                  ->where('status',$approval_type)
                                  ->get(); 
                                $uid = $user_id->pluck('entity_id');
                    } 
                     
                    DB::setDefaultConnection('mongodb');
				 
                    $usernew = User::whereIn('_id',$uid)->where('orgDetails.project_id',$project_id)->get();
					  
                    $roleData = \App\Role::get();
                   
                        $i=0;
                        foreach ($usernew as $userValues) {
                            
                            foreach ($roleData as $roleValue) {
                                if($userValues['role_id'] == $roleValue['_id'])
                                {
                                    //echo $userValues['role_id'];
                                    $usernew[$i]['role_id'] = $roleValue['display_name'];

                                }
                                
                            }
                           $i= $i+1;
                        }
                    
                     if($usernew)
                        {
                            $response_data = array('status' =>200,'data' => $usernew,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,300); 
                        }

                } 
        }
        if($data['type']=='attendance')
        {
             $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

                $filtertype = $data['filterSet']['filterType'];
                if(empty($filtertype))
                {
     //                $start_date_str = Carbon::createFromTimestamp($data['filterSet']['start_date'] /1000)->toDateTimeString();
                    // $end_date_str = Carbon::createFromTimestamp($data['filterSet']['end_date'] /1000)->toDateTimeString();
                    // $start_date_time = Carbon::parse($start_date_str)->startOfDay();  
                    // $end_date_time = Carbon::parse($end_date_str)->endOfDay(); 
                   

                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();



                    if($approval_type == 'pending'){
                    $user_id = ApprovalsPending::select('userName')
                            ->where('entity_type','attendance')
                            ->where('approver_ids',$user->_id)
                            ->where('created_at','>=',$start_date_time)
                            ->where('created_at','<=',$end_date_time)
                            ->where('status',$approval_type)->get();
                            $uid = $user_id->pluck('userName');
                    }
                    else{
                        
                         $user_id = ApprovalLog::
                                    select('userName')
                                    ->where('entity_type','attendance')
                                    ->where('action_by',$user->_id)
                                    ->where('created_at','>=',$start_date_time)
                                    ->where('created_at','<=',$end_date_time) 
                                    ->where('status',$approval_type)
                                    ->get();
                                    $uid = $user_id->pluck('userName');
                    }
                    
                    DB::setDefaultConnection('mongodb');
                    $usernew = User::whereIn('_id',$uid)->get();
                    $roleData = \App\Role::get();
                        $i=0;
                        foreach ($usernew as $userValues) {
                            
                            foreach ($roleData as $roleValue) {
                                if($userValues['role_id'] == $roleValue['_id'])
                                {
                                    //echo $userValues['role_id'];
                                    $usernew[$i]['role_id'] = $roleValue['display_name'];

                                }
                                
                            }
                           $i= $i+1;
                        }
                     if($usernew)
                        {
                            $response_data = array('status' =>200,'data' => $usernew,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,300); 
                        }

                } 
        }
         if($data['type']=='leave')
        { 
            
             $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

                $filtertype = $data['filterSet']['filterType'];
                if(empty($filtertype)) 
                { 
                    //  $start_date_str = Carbon::createFromTimestamp($data['filterSet']['start_date'] /1000)->toDateTimeString();
                    // $end_date_str = Carbon::createFromTimestamp($data['filterSet']['end_date'] /1000)->toDateTimeString();
                    // $start_date_time = Carbon::parse($start_date_str)->startOfDay();  
                    // $end_date_time = Carbon::parse($end_date_str)->endOfDay(); 
                    

                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();
                   
                    if($approval_type == 'pending'){
                                        
                    $user_id = ApprovalsPending::select('userName')
                                ->where('entity_type','leave')
                                ->where('approver_ids',$user->_id)
                                ->where('created_at','>=',$start_date_time)
                                ->where('created_at','<=',$end_date_time) 
                                ->where('status',$approval_type)
                                ->get();                           

                    }

                    if($approval_type == 'approved') //approved
                    {
                        
                        $user_id = ApprovalLog::select('userName','status')
                                    ->where('entity_type','leave')
                                     ->where('action_by',$user->_id)
                                    ->where('status',$approval_type)
                                    ->where('created_at','>=',$start_date_time)
                                    ->where('created_at','<=',$end_date_time) 
                                    ->get();
                                     
                                   
                    }
                    if($approval_type == 'rejected') 
                    {
                        $user_id = ApprovalLog::select('userName')
                                    ->where('entity_type','leave')
                                    ->where('action_by',$user->_id)
                                    ->where('created_at','>=',$start_date_time)
                                    ->where('created_at','<=',$end_date_time)
                                    ->where('status',$approval_type)->get();
                    }
                    $uid = $user_id->pluck('userName');
                       
                    DB::setDefaultConnection('mongodb');
                    $usernew = User::whereIn('_id',$uid)->get();

                    $roleData = \App\Role::get();
                        $i=0;
                        foreach ($usernew as $userValues) {
                            
                            foreach ($roleData as $roleValue) {
                               

                                if($userValues['orgDetails'][$i]['role_id'] == $roleValue['_id'])
                                {
                                    //echo $userValues['role_id'];
                                   //$usernew[$i]['role_id'] = $roleValue['display_name'];

                                    
                                    $usernew[$i]['role_id'] = $roleValue['display_name'];
                                  
                                 
                                }
                                
                            }
                           $i= $i+1;
                        }
                        //exit;
                     if($usernew)
                        {
                            $response_data = array('status' =>200,'data' => $usernew,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,300); 
                        }

                } 
        }
		 if($data['type']=='compoff')
        { 
             $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

                $filtertype = $data['filterSet']['filterType'];
                if(empty($filtertype)) 
                { 
                 
                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    
                    $end_date_time = Carbon::parse($end_date)->endOfDay();
                   //die();
                    if($approval_type == 'pending'){
                                        
                    $user_id = ApprovalsPending::select('userName')
                                ->where('entity_type','compoff')
                                ->where('approver_ids',$user->_id)
                                ->where('created_at','>=',$start_date_time)
                                ->where('created_at','<=',$end_date_time) 
                                ->where('status',$approval_type)
                                ->get();
                           

                    }
                    if($approval_type == 'approved') //approved
                    {
                        
                        $user_id = ApprovalLog::select('userName','status')
                                    ->where('entity_type','compoff')
                                    ->where('action_by',$user->_id)
                                    ->where('status',$approval_type)
                                    ->where('created_at','>=',$start_date_time)
                                    ->where('created_at','<=',$end_date_time) 
                                    ->get();
                                     
                                   
                    }
                    if($approval_type == 'rejected') 
                    {
                      
                        $user_id = ApprovalLog::select('userName')
                                    ->where('entity_type','compoff')
                                    ->where('action_by',$user->_id)
                                    ->where('created_at','>=',$start_date_time)
                                    ->where('created_at','<=',$end_date_time)
                                    ->where('status',$approval_type)
                                    ->get();
                    }

               
                    $uid = $user_id->pluck('userName');
                     
                    DB::setDefaultConnection('mongodb');
                    $usernew = User::whereIn('_id',$uid)->get();
                    $roleData = \App\Role::get();
                        $i=0;
                        foreach ($usernew as $userValues) {
                            
                            foreach ($roleData as $roleValue) {
                                if($userValues['role_id'] == $roleValue['_id'])
                                {
                                    //echo $userValues['role_id'];
                                   $usernew[$i]['role_id'] = $roleValue['display_name'];

                                }
                                
                            }
                           $i= $i+1;
                        }
                        //exit;
                     if($usernew)
                        {
                            $response_data = array('status' =>200,'data' => $usernew,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,300); 
                        }

                } 
        }


    }


   public function getUserByFilter(Request $request)
    { 
        $user = $this->request->user();
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
				$message['function'] = "getUserByFilter";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
        $data = json_decode(file_get_contents('php://input'), true);
		$data['function'] = "getUserByFilter";
        $this->logData($this->logInfoPath ,$data,'DB');
        $approval_type = $data['approval_type'];
        $single_user = $data['user_id'];
        if($data['type']=='forms')
        {
             $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

            
                $filtertype = $data['filterSet']['filterType'];
                if(empty($filtertype))
                {
                    //$start_date_str = Carbon::createFromTimestamp($data['filterSet']['start_date'] /1000)->toDateTimeString();
                    //$end_date_str = Carbon::createFromTimestamp($data['filterSet']['end_date'] /1000)->toDateTimeString();
                    //$start_date_time = Carbon::parse($start_date_str)->startOfDay();  
                    //$end_date_time = Carbon::parse($end_date_str)->endOfDay(); 
                    
                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();
                   




                    $limit = (int)$this->request->input('limit') ?:50;
                    $offset = $this->request->input('offset') ?:0;
                    $order = $this->request->input('order') ?:'desc';
                    $field = $this->request->input('field') ?:'createdDateTime';
                    $page = $this->request->input('page') ?:1;
                    if($approval_type == 'pending'){
                    $entity_id = ApprovalsPending::select('entity_id','category_id')->where('entity_type','form')
                        ->where('approver_ids',$user->_id)
                        ->where('created_at','>=',$start_date_time)
                        ->where('created_at','<=',$end_date_time)
                        ->where('status', $approval_type)
                        ->where('userName',$single_user)
                        ->get(); 
                     

                    }
                    else{
                        $entity_id = ApprovalLog::select('entity_id','category_id')
                                    ->where('entity_type','form')
                                    ->where('approver_ids',$user->_id)
                                    ->where('created_at','>=',$start_date_time)
                                    ->where('created_at','<=',$end_date_time)
                                    ->where('status', $approval_type)
                                    ->where('userName',$single_user)
                                    ->get();
                                     
                    }
                    $eid = $entity_id->pluck('category_id');
                    
                     $surveys = Survey::whereIn('entity_id',$eid)->get();
                     $surveyResults=array();
                        foreach($surveys as $survey)
                        {
                             $collection_name = 'entity_'.$survey->entity_id;           
                             $surveyResult['form_detail'] = DB::collection('entity_'.$survey->entity_id)
                                ->select('_id','survey_id','userName')
                                ->where('survey_id','=',$survey->_id)
                                ->where('userName','=',$single_user)
                                ->where('isDeleted','!=',true)->get();
                             //  $form_title =$this->generateFormTitle($survey,$surveyResult['form_detail'][0]['_id'],$collection_name);
                             // $surveyResult['form_title'] = $form_title;
                             // $surveyResult['microservice_id'] = $survey->microservice_id;
                             // $surveyResult['survey_name'] = $survey->name; 
                             // array_push($surveyResults, $surveyResult);


                             foreach ($surveyResult['form_detail'] as $formData) {

                                    $surveyResult['form_detail'] =    $formData;     
                                    $form_title =$this->generateFormTitle($survey,$formData['_id'],$collection_name);
                                    $formData['form_title'] = $form_title;
                                    $formData['microservice_id'] = $survey->microservice_id;
                                    $formData['entity_id'] = $survey->entity_id;
                                    $formData['survey_name'] = $survey->name; 
                                    array_push($surveyResults, $formData);
                                }
                             
                        }
                     if($surveyResults)
                        {
                            $response_data = array('status' =>200,'data' => $surveyResults,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,200); 
                        }

                } elseif($filtertype == 'category')
                {
                    // $start_date_str = Carbon::createFromTimestamp($data['filterSet']['start_date'] /1000)->toDateTimeString();
                    // $end_date_str = Carbon::createFromTimestamp($data['filterSet']['end_date'] /1000)->toDateTimeString();
                    // $start_date_time = Carbon::parse($start_date_str)->startOfDay();  
                    // $end_date_time = Carbon::parse($end_date_str)->endOfDay(); 
                    
                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();





                    $id = $data['filterSet']['id'];
                    $entity_id = Survey::select('entity_id')->whereIn('category_id',$id)->get();
                    $eid = $entity_id->pluck('entity_id');

                    if($approval_type == 'pending'){
                    $new_entity_id = ApprovalsPending::select('entity_id','category_id')
                                    ->where('entity_type','form')
                                    ->where('approver_ids',$user->_id)
                                    ->where('created_at','>=',$start_date_time)
                                    ->where('created_at','<=',$end_date_time)
                                    ->where('status', $approval_type)
                                    ->where('userName',$single_user)->get(); 

                    }
                    else{
                    $new_entity_id = ApprovalLog::select('entity_id','category_id')->where('entity_type','form')->where('userName',$single_user)->where('approver_ids',$user->_id)->get();    
                    }

                    $neweid = $new_entity_id->pluck('category_id');
                     
                    $surveys = Survey::whereIn('entity_id',$neweid)->get();
                   
                     $surveyResults=array();
                     
                        foreach($surveys as $survey)
                        {
                            if(isset($survey->entity_id)){
                                
                             $collection_name = 'entity_'.$survey->entity_id;                                
                             $surveyResult['form_detail'] = DB::collection('entity_'.$survey->entity_id)
                                ->select('_id','survey_id','userName')
                                ->where('survey_id','=',$survey->_id)
                                ->where('userName','=',$single_user)
                                ->where('isDeleted','!=',true)->get();
                                  
                                if(count($surveyResult['form_detail'])>0){ 
                                    foreach ($surveyResult['form_detail'] as $formData) {
                                     
                                     $surveyResult['form_detail'] =    $formData;
                                     $form_title =$this->generateFormTitle($survey,$formData['_id'],$collection_name);
                                     $formData['form_title'] = $form_title;
                                     $formData['microservice_id'] = $survey->microservice_id;
                                     $formData['entity_id'] = $survey->entity_id;
                                     $formData['survey_name'] = $survey->name; 
                                     array_push($surveyResults, $formData);

                                   
                                    } 
                                }
                         } else { 
                            $collection_name = 'survey_results';
                            $surveyResult['form_detail'] = DB::collection('survey_results')
                                ->select('_id','survey_id','userName')
                                ->where('survey_id','=',$survey->_id)
                                ->where('userName','=',$single_user)
                                ->where('isDeleted','!=',true)->get();
                                 if(count($surveyResult['form_detail'])>0){
                                    foreach ($surveyResult['form_detail'] as $formData) {

                                    $surveyResult['form_detail'] =    $formData;     
                                    $form_title =$this->generateFormTitle($survey,$formData['_id'],$collection_name);
                                    $formData['form_title'] = $form_title;
                                    $formData['microservice_id'] = $survey->microservice_id;
                                    $formData['entity_id'] = $survey->entity_id;
                                    $formData['survey_name'] = $survey->name; 
                                    array_push($surveyResults, $formData);
                                }
                         }
                         }
                        }
                          
                     if($surveyResults)
                        {
                            $response_data = array('status' =>200,'data' =>$surveyResults ,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No Data found for this user','message'=>"error");
                            return response()->json($response_data,200); 
                        }

                }
        }
        if($data['type']=='userapproval')
        {
             $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }

            $count = count($data['filterSet']);
            
            
                $filtertype = $data['filterSet']['filterType'];
                if(empty($filtertype))
                {
                    //$start_date_str = Carbon::createFromTimestamp($data['filterSet']['start_date'] /1000)->toDateTimeString();
                    // $end_date_str = Carbon::createFromTimestamp($data['filterSet']['end_date'] /1000)->toDateTimeString();
                    // $start_date_time = Carbon::parse($start_date_str)->startOfDay();  
                    // $end_date_time = Carbon::parse($end_date_str)->endOfDay(); 
                    
                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();



                    if($approval_type == 'pending'){

                    $user_id = ApprovalsPending::select('userName')
                        ->where('entity_type','userapproval')
                        ->where('approver_ids',$user->_id)
                        ->where('created_at','>=',$start_date_time)
                        ->where('created_at','<=',$end_date_time)
                        //->where('default.project_id',$project_id)
                        ->where('status',$approval_type)
                        ->where('userName',$single_user)
                        ->get();
                         $uid = $user_id->pluck('userName');
                      
                    }
                    else{ 
                            $user_id = ApprovalLog::select('entity_id')
                                ->where('entity_type','userapproval')
                                ->where('action_by',$user->_id)
                                ->where('created_at','>=',$start_date_time)
                                ->where('created_at','<=',$end_date_time)
                                ->where('status',$approval_type) 
                                ->where('entity_id',$single_user)
								//->where('default.project_id',$project_id)
                                ->get();
                                $uid = $user_id->pluck('entity_id');
                                
                              
                    }
                    
                  
                    DB::setDefaultConnection('mongodb');
                    $usernew = User::whereIn('_id',$uid)->where('orgDetails.project_id',$project_id)->get();
                    $this->getUserAssociatedData($usernew);   
                     if($usernew)
                        {
                            $response_data = array('status' =>200,'data' => $usernew,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,200); 
                        }

                } 
        }
        if($data['type']=='attendance')
        {
             $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }
           
                $filtertype = $data['filterSet']['filterType'];
                if(empty($filtertype))
                {
                    //$start_date_str = Carbon::createFromTimestamp($data['filterSet']['start_date'] /1000)->toDateTimeString();
                    //$end_date_str = Carbon::createFromTimestamp($data['filterSet']['end_date'] /1000)->toDateTimeString();
                    //$start_date_time = Carbon::parse($start_date_str)->startOfDay();  
                    //$end_date_time = Carbon::parse($end_date_str)->endOfDay(); 
                    
                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();
                    
                    if($approval_type == 'pending'){
                    $user_id = ApprovalsPending::select('userName')
                                ->where('entity_type','attendance')
                                ->where('approver_ids',$user->_id)
                                ->where('created_at','>=',$start_date_time)
                                ->where('created_at','<=',$end_date_time)
                                ->where('status',$approval_type)
                                ->where('userName',$single_user)->get();
                    }
                    else{
                         
                     $user_id = ApprovalLog::select('userName')
                                ->where('entity_type','attendance')
                                ->where('action_by',$user->_id)
                                ->where('created_at','>=',$start_date_time)
                                ->where('created_at','<=',$end_date_time)
                                ->where('status',$approval_type)
                                ->where('userName',$single_user)->get();    
                    }
					 
                    $uid = $user_id->pluck('userName');
                    $usernew = PlannerAttendanceTransaction::where('status.status',$approval_type)->whereIn('user_id',$uid)->get();
                    
                     $userAttandanceData = [];
                      if(count($usernew) > 0 )  
                     {   
                        foreach($usernew  as $attendData)
                        {

                              if(is_array($attendData['check_out']) && $attendData['check_out.time'] == 0)
                                {
                                     unset($attendData['check_out']);
                                    array_push($userAttandanceData, $attendData);
                                } 
                           // $attCount=$attCount+1;    
                        }
                      }  
                     if($usernew)
                        {
                            $response_data = array('status' =>200,'data' => $usernew,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,200); 
                        }

                } 
        }
        if($data['type']=='leave')
        {


            $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }
            
             $filtertype = $data['filterSet']['filterType'];
                if(empty($filtertype))
                {
                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();
                    
                    /* 
                    $start = (int)$data['filterSet']['start_date'];
                    $end = (int)$data['filterSet']['end_date']; 
                    $sdt = Carbon::createFromTimestamp($start);
                    $start_date = new \MongoDB\BSON\UTCDateTime($sdt);
                    $edt = Carbon::createFromTimestamp($end);
                    $end_date = new \MongoDB\BSON\UTCDateTime($edt); */ 
                     if($approval_type == 'pending'){
                    $user_id = ApprovalsPending::select('userName')
                            ->where('entity_type','leave')
                            ->where('approver_ids',$user->_id) 
                            ->where('created_at','>=',$start_date_time)->where('created_at','<=',$end_date_time)
                            ->where('status',$approval_type)->where('userName',$single_user)
                            ->get();
                               
                    }
                    else{
                          $user_id = ApprovalLog::select('userName','status')
                                ->where('entity_type','leave')
                                ->where('action_by',$user->_id)
                                ->where('created_at','>=',$start_date_time)
                                ->where('created_at','<=',$end_date_time)
                                ->where('userName',$single_user)
                                ->where('status',$approval_type)
                                ->get();    
                                
                    }
                    
                    $uid = $user_id->pluck('userName');
                    
                    
                    $approval = $approval_type;
                    $applications = PlannerLeaveApplications::where('status.status',$approval)->whereIn('user_id',$uid)->get();
                    

                   $leavecount = PlannerUserLeaveBalance::select('user_id','leave_balance')
                                        ->whereIn('user_id',$uid)->get();
                    $data=[
                        "application"=>$applications,
                        "leave_count"=>$leavecount
                    ];
                     if($data)
                        { 
                            $response_data = array('status' =>200,'data' => $data,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,200); 
                        }

                } 
        }

          if($data['type']=='compoff')
        {


            $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }
            
             $filtertype = $data['filterSet']['filterType'];
                if(empty($filtertype))
                {
                    $start_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['start_date']/1000);

                    $end_date_str = Carbon::createFromTimestamp((int)$data['filterSet']['end_date'] /1000);

                    $carbonStartDate = new Carbon($start_date_str);
                    $carbonStartDate->timezone = 'Asia/Kolkata';
                    $start_date = $carbonStartDate->toDateTimeString();


                    $carbonEndDate = new Carbon($end_date_str);
                    $carbonEndDate->timezone = 'Asia/Kolkata';
                    $end_date = $carbonEndDate->toDateTimeString();

                    $start_date_time = Carbon::parse($start_date)->startOfDay();  
                    $end_date_time = Carbon::parse($end_date)->endOfDay();
                    
                    /*  
                    $start = (int)$data['filterSet']['start_date'];
                    $end = (int)$data['filterSet']['end_date']; 
                    $sdt = Carbon::createFromTimestamp($start);
                    $start_date = new \MongoDB\BSON\UTCDateTime($sdt);
                    $edt = Carbon::createFromTimestamp($end);
                    $end_date = new \MongoDB\BSON\UTCDateTime($edt); */ 
                     if($approval_type == 'pending'){
                    $user_id = ApprovalsPending::select('userName')
                            ->where('entity_type','compoff')
                            ->where('approver_ids',$user->_id) 
                            ->where('created_at','>=',$start_date_time)->where('created_at','<=',$end_date_time)
                            ->where('status',$approval_type)->where('userName',$single_user)
                            ->get();
                               
                    }
                    else{
                          $user_id = ApprovalLog::select('userName','status')
                                ->where('entity_type','compoff')
                                ->where('action_by',$user->_id)
                                ->where('created_at','>=',$start_date_time)
                                ->where('created_at','<=',$end_date_time)
                                ->where('userName',$single_user)
                                ->where('status',$approval_type)
                                ->get();    
                                
                    }
                    
                    $uid = $user_id->pluck('userName');

                    $approval = $approval_type;
                   
                    $applications = PlannerClaimCompoffRequests::where('status.status',$approval)->whereIn('user_id',$uid)->get();


                   $leavecount = PlannerUserLeaveBalance::select('user_id','leave_balance')
                                        ->whereIn('user_id',$uid)->get();
                    $data=[
                        "application"=>$applications,
                        "leave_count"=>$leavecount
                    ];
                     if($data)
                        { 
                            $response_data = array('status' =>200,'data' => $data,'message'=>"success");
                            return response()->json($response_data,200); 
                        }
                        else
                        {
                            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                            return response()->json($response_data,200); 
                        }

                } 
        }



    }
    
    public function getformdetail(Request $request)
    {
        $data = json_decode(file_get_contents('php://input'), true);
		$this->logData($this->logInfoPath ,$data,'DB');
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
				$message['function'] = "getformdetail";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) 
        {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        $user = $this->request->user();
        $formid = $data['_id']['$oid'];
        $survey = Survey::find($data['survey_id']);
        $single_user = $data['userName'];
        $collection_name = 'entity_'.$survey->entity_id;           
        $surveyResult['form_detail'] = DB::collection('entity_'.$survey->entity_id)
            ->where('survey_id','=',$survey->_id)
            ->where('_id','=',$formid)
            ->where('userName','=',$single_user)
            ->where('isDeleted','!=',true)->get();
        $form_title =$this->generateFormTitle($survey,$surveyResult['form_detail'][0]['_id'],$collection_name);
        $surveyResult['form_title'] = $form_title;
        $surveyResult['survey_name'] = $survey->name;
        $surveyResult['form_detail']=$this->getFormAssociatedData($surveyResult['form_detail'][0]); 
        if($surveyResult)
        {
            $response_data = array('status' =>200,'data' => $surveyResult,'message'=>"success");
            return response()->json($response_data,200); 
        }
        else
        {
            $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
            return response()->json($response_data,300); 
        }
    }


    public function getUserAssociatedData($usernew)
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
				$message['function'] = "getUserAssociatedData";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			$this->logData($this->logInfoPath ,$usernew,'DB');
        DB::setDefaultConnection('mongodb');
        if(isset($usernew[0]->org_id)){
            $organisation = Organisation::find($usernew[0]->org_id);
            $org_object = new \stdClass;
            $org_object->_id = $organisation->id;
            $org_object->name = $organisation->name;
            $usernew[0]->org_id = $org_object; 
        }
        if(isset($usernew[0]->role_id)){
            $role = \App\Role::find($usernew[0]->role_id);
            $role_object = new \stdClass;
            $role_object->_id = $role->id;
            $role_object->name = $role->display_name;
            $usernew[0]->role_id = $role_object;
        }
        
        if(isset($usernew[0]->location) && isset($usernew[0]->org_id)){
            $database = $this->connectTenantDatabase($this->request,$organisation->id);
            if ($database !== null) {
                $location = [];
                foreach($usernew[0]->location as $level => $location_level){
                    $level_data = array();
                    $newlocation=[];
                    foreach ($location_level as $location_id){
                   if ($level == 'country'){
                        $location_obj = \App\Country::find($location_id);
                    } if ($level == 'state'){
                        $location_obj = \App\State::find($location_id);
                    }
                    if ($level == 'district'){
                        $location_obj = \App\District::find($location_id);
                    }
                    if ($level == 'taluka'){
                        $location_obj = \App\Taluka::find($location_id);
                    }
                    if ($level == 'village'){
                        $location_obj = \App\Village::find($location_id);
                    }
                    if ($level == 'city'){
                        $location_obj = \App\City::find($location_id);
                    }
                    
                    array_push($newlocation, $location_obj->name);
                    //$location_std_obj->name = $location_obj->name; 
                    }
                    $location_std_obj =  new \stdClass; 
                    $location_std_obj->value=$newlocation;
                    $location_std_obj->display_name = ucfirst($level); 
                   // array_push($level_data,$location_std_obj);
                    array_push($location,$location_std_obj);
                }
                $usernew[0]->location = $location;
            }
        }

        if(isset($usernew[0]->project_id)){
            $database = $this->connectTenantDatabase($this->request,$organisation->id);
            $projects = array();
            if ($database !== null) {
           
            foreach($usernew[0]->project_id as $project_id){
                
                $project = Project::find($project_id); 
                //var_dump($database); exit;
                $project_object = new \stdClass;
                $project_object->_id = $project->id;
                $project_object->name = $project->name;
                array_push($projects,$project_object);
            }
            
            $usernew[0]->project_id = $projects;
            }
        }
        return $usernew;
    }

     public function getFormAssociatedData($usernew){
        DB::setDefaultConnection('mongodb');
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
				$message['function'] = "getFormAssociatedData";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			$this->logData($this->logInfoPath ,$usernew,'DB');
        if (isset($usernew['user_role_location']['role_id'])) {

            $role = \App\Role::find($usernew['user_role_location']['role_id']);
            $usernew['user_role_location']['role_id'] = $role->display_name ;
        }
        if(isset($usernew['user_role_location']['state']))
        {
            $database = $this->connectTenantDatabase($this->request);
            $state = \App\State::find($usernew['user_role_location']['state'][0]);
            $usernew['user_role_location']['state'][0] = $state->name;
        }
        if(isset($usernew['user_role_location']['district']))
        {
            $database = $this->connectTenantDatabase($this->request);
            $district = \App\District::find($usernew['user_role_location']['district'][0]);
            $usernew['user_role_location']['district'][0] = $district->name;
        }
          if(isset($usernew['user_role_location']['taluka']))
        {
            $database = $this->connectTenantDatabase($this->request);
            $taluka = \App\Taluka::find($usernew['user_role_location']['taluka'][0]);
            $usernew['user_role_location']['taluka'][0] = $taluka->name;
        }
         if(isset($usernew['village_name']))
        {
            $database = $this->connectTenantDatabase($this->request);
            $village = \App\Village::find($usernew['village_name']);
            $usernew['village_name'] = $village->name;
        }
         if(isset($usernew['userName']))
        {
            DB::setDefaultConnection('mongodb');
            $user = User::find($usernew['userName']);
            $usernew['userName'] = $user->name;
        }
        return $usernew;
    }


    public function applicationapproval(Request $request) {
		
		 $timestamp = Date('Y-m-d H:i:s');
        $user = $this->request->user();
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
				$message['function'] = "applicationapproval";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			
        $data = json_decode(file_get_contents('php://input'), true);
		$data['function'] = "applicationapproval";
		$this->logData($this->logInfoPath ,$data,'DB'); 
		$ordId = $role_id;
		 
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) 
        {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        if($data['type']=='userapproval'){

                $id = $data['id'];
				
				// Entry into the approval log collection 
				$approval_log = new ApprovalLog;
				$approval_log['status'] = $data['approve_type'];
				$approval_log['entity_id'] = $data['id'];
				$approval_log['entity_type'] = $data['type'];
                $approval_log['action_by'] = $user['_id'];
                $approval_log['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));
				$approval_log['reason'] = $data['reason'];
				$approval_log->save();
				
                
                $leaveData = Project::where('_id',$project_id)->first();
                // echo $leaveData['sick_leave'];
                // exit;
				$leave_balance = PlannerUserLeaveBalance::where('user_id',$id)->first();
				if($leave_balance){}
				else{
					$leave_balance = new PlannerUserLeaveBalance;
					$data = array();
					$data[0]['type'] = 'casual Leave';
					$data[0]['balance'] = $leaveData['casual_leave'];
					$data[1]['type'] = 'sick Leave';
					$data[1]['balance'] = $leaveData['sick_leave'];
					$data[2]['type'] = 'compoff';
					$data[2]['balance'] = 0;//$leaveData['sick_leave'];
					$leave_balance['user_id'] = $id;
					$leave_balance['leave_balance'] = $data; 
					$leave_balance['default.org_id'] = $org_id;
					$leave_balance['default.updated_by'] = "";
					$leave_balance['default.created_by'] = $user['_id'];
					$leave_balance['default.created_on'] = $timestamp;    
					$leave_balance['default.updated_on'] = "";
					$leave_balance['default.project_id'] = $project_id;
					$leave_balance->save();
				}
				
                $ApprovalsPending = ApprovalsPending::where('entity_id',$id)->first();
               /*  $ApprovalsPending['status'] = $data['approve_type'];
                $ApprovalsPending['action_by'] = $user['_id'];
                $ApprovalsPending['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));
                if($data['approve_type']=='rejected')
                {
                    if($data['reason']=""){
                        $response_data = array('status' =>300,'message'=>"Please enter rejection result");
                    return response()->json($response_data,300);
                    }
                    $ApprovalsPending['reason'] = $data['reason'];
                } */
				 
				if($ApprovalsPending)
                {    
                    $ApprovalsPending->delete();
                }
                DB::setDefaultConnection('mongodb');
                $user = User::where('_id',$id)->first();
                $newrolename = '';
				$rolename = \App\Role::select('display_name')->where("_id",$ordId)->first();
				 
				 if(isset($rolename['display_name']))
				 {
					$newrolename =  $rolename['display_name'];
				 }
				
				if($user)
				{
                $user['approve_status'] = $data['approve_type'];
				$counting = 0;	
				$count = 0;	
				 foreach($user->orgDetails as $row)
				 {
					if($row['project_id'] == $project_id){
					$count = $counting; 
					break;	
					} 
					$counting++;
				 }
				  
                $user['updated_at'] = Carbon::now();
				if($data['approve_type'] == 'approved')
				{
                   $user['orgDetails.'.$count.'.status.status'] = $data['approve_type']; 
                   $user['orgDetails.'.$count.'.status.action_by'] = $user->_id;  
                   $user['orgDetails.'.$count.'.status.rejection_reason'] = ''; 
                    
				 
                    $this->sendPushNotification(
                    $this->request,
                    self::NOTIFICATION_TYPE_APPROVED,
                    $user['firebase_id'],
                    [
                        'phone' => "9881499768",
						'rolename'=>$newrolename,
                        'update_status' => self::STATUS_APPROVED,
                        'approval_log_id' => "Testing"
                    ],
                    $org_id
                );
				}
				if($data['approve_type'] == 'rejected')
				{
					
                   $user['orgDetails.'.$count.'.status.status'] = $data['approve_type']; 
                   $user['orgDetails.'.$count.'.status.action_by'] = $user->_id; 
                  // $user['status.action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s'))); 
                   $user['orgDetails.'.$count.'.status.rejection_reason'] = $data['reason']; 


                $this->sendPushNotification(
                    $this->request,
                    self::NOTIFICATION_TYPE_REJECTED,
                    $user['firebase_id'],
                    [
                        'phone' => "9881499768",
						'rolename'=>$newrolename,
                        'update_status' => self::STATUS_REJECTED,
                        'approval_log_id' => "Testing",
						'reason' => $data['reason']
                    ],
                    $org_id
                );
				}
                
                $user->save();
				}
               
                if($approval_log)
                {
                    $response_data = array('status' =>200,'message'=>"User ".$data['approve_type']." successfully");
                    return response()->json($response_data,200); 
                }
                else
                {
                    $response_data = array('status' =>300,'data' => 'No rows found please check id','message'=>"error");
                    return response()->json($response_data,200); 
                }
        }

        if($data['type']=='leave'){
                $database = $this->connectTenantDatabase($this->request);
                if ($database === null) 
                {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }	 
                $id = $data['id'];
			
                $leave = PlannerLeaveApplications::where('_id',$id)->first();
				 
				$approval_log = new ApprovalLog;
				$approval_log['status'] = $data['approve_type'];
				$approval_log['entity_id'] = $data['id'];
                $approval_log['userName'] = $leave->user_id;
				$approval_log['entity_type'] = $data['type'];
                $approval_log['action_by'] = $user['_id'];
                $approval_log['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));
				$approval_log['reason'] = $data['reason'];
				 
				$approval_log->save();
				
                $ApprovalsPending = ApprovalsPending::where('entity_id',$id)->first();
                //echo json_encode($ApprovalsPending);die();
                /* $ApprovalsPending['_id'] = $user['_id'];
                $ApprovalsPending['status'] = $data['approve_type'];
                $ApprovalsPending['action_by'] = $user['_id'];
                $ApprovalsPending['userName'] = $user['username'];
                $Approval sPending['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));*/
                if(!$ApprovalsPending)
                {
                    $response_data = array('status' =>300,'message'=>"Leave record not found");
                    return response()->json($response_data,200);

                }else
                {

                    $ApprovalsPending->delete();
                }


                $start_date_str = Carbon::createFromTimestamp($data['startdate']/1000);
          

                $end_date_str = Carbon::createFromTimestamp($data['enddate'] /1000);//->toDateTimeString();

                $carbonStartDate = new Carbon($start_date_str);
                $carbonStartDate->timezone = 'Asia/Kolkata';
                $start_date = $carbonStartDate->toDateTimeString();


                $carbonEndDate = new Carbon($end_date_str);
                $carbonEndDate->timezone = 'Asia/Kolkata';
                $end_date = $carbonEndDate->toDateTimeString();

                $start_date_time = Carbon::parse($start_date)->startOfDay();  
                $end_date_time = Carbon::parse($end_date)->endOfDay();

                $days = $start_date_str->diffInDays($end_date_str)+1;

                $leaveTypeFlag = $leave->full_half_day; 
	 		   
               // $days =  unixtojd()-unixtojd();
				if($leaveTypeFlag == 'half day')
				{
					$days = 0.5;
				}
                $leave_balance = PlannerUserLeaveBalance::where('user_id',$leave->user_id)->first();
                $leaves = $leave_balance['leave_balance'];
				 
                $count = 0;
				if($leaves){

                foreach($leaves as $key=>$leaveData){
                    if($leaveData['type'] == $data['leave_type'] && ($data['approve_type'] == 'approved') ){
                        $leave_balance['leave_balance.'.$count.'.balance']=$leaveData['balance'] - $days;
                      
                    }
                $count++;
                } 
				 
				$leave_balance->save();
                $leave['status.status'] = $data['approve_type'];
                 $leave['status.rejection_reason'] = $data['reason'];
                    
                $leave->save();
                // if($ApprovalsPending)
                // {     $ApprovalsPending->delete();
                //     }
				 
				if($data['approve_type'] == 'approved')
				{  
					DB::setDefaultConnection('mongodb');
					$rolename = \App\Role::select('display_name')->where("_id",$ordId)->first();
				 if(isset($rolename['display_name']))
				 {
					$newrolename =  $rolename['display_name'];
				 }
					$firebase_id = User::where('_id',$leave['user_id'])->first(); 
					 
					$this->sendPushNotification(
                    $this->request,
                    self::NOTIFICATION_TYPE_LEAVE_APPROVED,
                    $firebase_id['firebase_id'],
                    [
                        'phone' => "9881499768",
						'rolename'=>$newrolename,
                        'update_status' => self::STATUS_APPROVED,
                        'approval_log_id' => "Testing"
                    ],
                    $firebase_id['org_id']
                );
				}
				if($data['approve_type'] == 'rejected')
				{
               DB::setDefaultConnection('mongodb');
			   $rolename = \App\Role::select('display_name')->where("_id",$role_id)->first();
				 if(isset($rolename['display_name']))
				 {
					$newrolename =  $rolename['display_name'];
				 }
					$firebase_id = User::where('_id',$leave['user_id'])->first(); 
					 
					$this->sendPushNotification(
                    $this->request,
                    self::NOTIFICATION_TYPE_LEAVE_REJECTED,
                    $firebase_id['firebase_id'],
                    [
                        'phone' => "9881499768",
						'rolename'=>$newrolename,
                        'update_status' => self::STATUS_REJECTED,
                        'approval_log_id' => "Testing"
                    ],
                    $firebase_id['org_id']
                );
				}
				 
                
				}  
                if($data['approve_type']=='rejected')
                {
                    if($data['reason']==""){
                        $response_data = array('status' =>300,'message'=>"Please enter rejection Reason");
                    return response()->json($response_data,200);
                    }
                     
                }
				 
				
                 

                if($approval_log)
                {
                    $response_data = array('status' =>200,'message'=>"Leave ".$data['approve_type']." successfully");
                    return response()->json($response_data,200); 
                }
                else
                {
                    $response_data = array('status' =>300,'data' => 'No rows found please check id','message'=>"error");
                    return response()->json($response_data,200); 
                }
        }
        if($data['type']=='compoff'){
                $database = $this->connectTenantDatabase($this->request);
                if ($database === null) 
                {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }    
                $id = $data['id'];
				 DB::setDefaultConnection('mongodb');
                $rolename = \App\Role::find($ordId);
				 
                 if(isset($rolename['display_name']))
                 {
                    $newrolename =  $rolename['display_name'];
                 }
				  $database = $this->connectTenantDatabase($this->request);
                $compoff = PlannerClaimCompoffRequests::where('_id',$id)->first();
               
                $approval_log = new ApprovalLog;
                $approval_log['status'] = $data['approve_type'];
                $approval_log['entity_id'] = $data['id'];
                $approval_log['userName'] = $compoff->user_id;
                $approval_log['entity_type'] = $data['type'];
                $approval_log['action_by'] = $user['_id'];
                $approval_log['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));
                $approval_log['reason'] = $data['reason'];
                 
                $approval_log->save();
                
                $ApprovalsPending = ApprovalsPending::where('entity_id',$id)->first();
                /* $ApprovalsPending['_id'] = $user['_id'];
                $ApprovalsPending['status'] = $data['approve_type'];
                $ApprovalsPending['action_by'] = $user['_id'];
                $ApprovalsPending['userName'] = $user['username'];
                $Approval sPending['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));*/
				if($ApprovalsPending)
                {
					$ApprovalsPending->delete();
                }else{
					$response_data = array('status' =>300,'data' => 'No rows found please check id','message'=>"error");
                    return response()->json($response_data,200); 
				}

                $start_date_str = Carbon::createFromTimestamp($data['startdate']/1000);
          

                $end_date_str = Carbon::createFromTimestamp($data['enddate'] /1000); 

                $carbonStartDate = new Carbon($start_date_str);
                $carbonStartDate->timezone = 'Asia/Kolkata';
                $start_date = $carbonStartDate->toDateTimeString();


                $carbonEndDate = new Carbon($end_date_str);
                $carbonEndDate->timezone = 'Asia/Kolkata';
                $end_date = $carbonEndDate->toDateTimeString();

                $start_date_time = Carbon::parse($start_date)->startOfDay();  
                $end_date_time = Carbon::parse($end_date)->endOfDay();

                $days = $start_date_str->diffInDays($end_date_str)+1;

                $compoffTypeFlag = $compoff->full_half_day; 
                
                if($compoffTypeFlag == 'half day')
                {
                    $days = 0.5;
                }
                $leave_balance = PlannerUserLeaveBalance::where('user_id',$compoff->user_id)->first();
                $leaves = $leave_balance['leave_balance'];
                 
                $count = 0;
                if($leaves){
				 
                foreach($leaves as $leaveData){  
                    if($leaveData['type'] == $data['type'] && $data['approve_type'] == 'approved' ){ 
                       $leave_balance['leave_balance.'.$count.'.balance']=$leaveData['balance'] + $days;
					} 
                $count++;
                }  
                $leave_balance->save();
                 
                if($data['approve_type'] == 'approved')
                {  
			
                    DB::setDefaultConnection('mongodb');
                    $firebase_id = User::where('_id',$compoff['user_id'])->first(); 
                     
                    $this->sendPushNotification(
                    $this->request,
                    self::NOTIFICATION_TYPE_COMOFF_APPROVED,
                    $firebase_id['firebase_id'],
                    [
                        'phone' => "9881499768",
                        'rolename'=>$newrolename,
                        'update_status' => self::STATUS_APPROVED,
                        'approval_log_id' => "Testing"
                    ],
                    $firebase_id['org_id']
                );
                }
                if($data['approve_type'] == 'rejected')
                {
               DB::setDefaultConnection('mongodb');
                    $firebase_id = User::where('_id',$compoff['user_id'])->first(); 
                     
                    $this->sendPushNotification(
                    $this->request,
                    self::NOTIFICATION_TYPE_COMOFF_REJECTED,
                    $firebase_id['firebase_id'],
                    [
                        'phone' => "9881499768",
                        'rolename'=>$newrolename,
                        'update_status' => self::STATUS_REJECTED,
                        'approval_log_id' => "Testing"
                    ],
                    $firebase_id['org_id']
                );
                }
                 
                
                }  
                if($data['approve_type']=='rejected')
                {
                    if($data['reason']==""){
                        $response_data = array('status' =>300,'message'=>"Please enter rejection Reason");
                    return response()->json($response_data,200);
                    }
                     
                } 
                 $compoff['status.status'] = $data['approve_type'];
                 $compoff['status.rejection_reason'] = $data['reason'];
                    
                $compoff->save();

                if($leave_balance)
                {
                    $response_data = array('status' =>200,'message'=>"Compoff ".$data['approve_type']." successfully");
                    return response()->json($response_data,200); 
                }
                else
                {
                    $response_data = array('status' =>300,'data' => 'No rows found please check id','message'=>"error");
                    return response()->json($response_data,200); 
                }
        }

         if($data['type']=='attendance'){
                $database = $this->connectTenantDatabase($this->request);
                if ($database === null) 
                {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }
                $id = $data['id'];
				
				
				
                $ApprovalsPending = ApprovalsPending::where('entity_id',$id)->first();
                /*$ApprovalsPending['status'] = $data['approve_type'];
                $ApprovalsPending['updated_at'] = Carbon::now();
				$approval_log['userName'] = $user['userId'];
                $ApprovalsPending['action_by'] = $user['_id'];
                $ApprovalsPending['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));*/
               
                if($data['approve_type']=='rejected')
                {
                     if($data['reason'] =="" )
                     {
                         $response_data = array('status' =>300,'message'=>"Please Enter Rejected Reason");
                          return response()->json($response_data,200);
                     }
                   /* $ApprovalsPending['reason'] = $data['reason'];*/
                }
				if($ApprovalsPending)
                $ApprovalsPending->delete();
                $attendance = PlannerAttendanceTransaction::where('_id',$id)->first();
               // $attendance['status'] = $data['approve_type'];
                //$attendance['reason'] = $data['reason'];

                 $attendance['status.status'] = $data['approve_type'];
                 $attendance['status.rejection_reason'] = $data['reason'];

                $attendanceid = $attendance['user_id'];

                $attendance->save();
				
				$approval_log = new ApprovalLog;
				$approval_log['status'] = $data['approve_type'];
				$approval_log['entity_id'] = $data['id'];
				$approval_log['entity_type'] = $data['type'];
                $approval_log['action_by'] = $user['_id'];
                $approval_log['userName'] = $attendance['user_id'];
                $approval_log['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));
				$approval_log['reason'] = $data['reason'];
				$approval_log->save();
				if($data['approve_type'] == 'approved')
				{  
					DB::setDefaultConnection('mongodb');
					$rolename = \App\Role::select('display_name')->where("_id",$ordId)->first();
					 if(isset($rolename['display_name']))
					 {
						$newrolename =  $rolename['display_name'];
					 }
					$firebase_id = User::where('_id',$attendanceid)->first(); 
					 
					$this->sendPushNotification(
                    $this->request,
                    self::NOTIFICATION_TYPE_ATTENDANCE_APPROVED,
                    $firebase_id['firebase_id'],
                    [
                        'phone' => "9881499768",
						'rolename'=>$newrolename,
                        'update_status' => self::STATUS_APPROVED,
                        'approval_log_id' => "Testing"
                    ],
                    $firebase_id['org_id']
                );
				}
				if($data['approve_type'] == 'rejected')
				{
               DB::setDefaultConnection('mongodb');
			   $rolename = \App\Role::select('display_name')->where("_id",$role_id)->first();
					 if(isset($rolename['display_name']))
					 {
						$newrolename =  $rolename['display_name'];
					 }
					$firebase_id = User::where('_id',$attendance['user_id'])->first(); 
					 
					$this->sendPushNotification(
                    $this->request,
                    self::NOTIFICATION_TYPE_LEAVE_REJECTED,
                    $firebase_id['firebase_id'],
                    [
                        'phone' => "9881499768",
						'rolename'=>$newrolename,
                        'update_status' => self::STATUS_REJECTED,
                        'approval_log_id' => "Testing"
                    ],
                    $firebase_id['org_id']
                );
				}
				
                if($approval_log)
                {
                    $response_data = array('status' =>200,'message'=>"Attendance ".$data['approve_type']." successfully");
                    return response()->json($response_data,200); 
                }
                else
                {
                    $response_data = array('status' =>300,'data' => 'No rows found please check id','message'=>"error");
                    return response()->json($response_data,200); 
                }
        }

        if($data['type']=='form'){
                $database = $this->connectTenantDatabase($this->request);
                if ($database === null) 
                {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }
                $id = $data['id'];
				$rolename = \App\Role::select('display_name')->where("_id",$ordId)->first();
                     if(isset($rolename['display_name']))
                     {
                        $newrolename =  $rolename['display_name'];
                     }
				$approval_log = new ApprovalLog;
				$approval_log['status'] = $data['approve_type'];
				$approval_log['entity_id'] = $data['id'];
				$approval_log['entity_type'] = $data['type'];
                $approval_log['action_by'] = $user['_id'];
                $approval_log['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));
				$approval_log['reason'] = $data['reason'];
				$approval_log->save();
				
				if($data['approve_type'] == 'approved')
				{  
					DB::setDefaultConnection('mongodb');
					$firebase_id = User::where('_id',$user['_id'])->first(); 
				 
					// $this->sendPushNotification(
     //                $this->request,
     //                self::NOTIFICATION_TYPE_ATTENDANCE_APPROVED,
     //                $firebase_id['firebase_id'],
     //                [
     //                    'phone' => "9881499768",
     //                    'update_status' => self::STATUS_APPROVED,
     //                    'approval_log_id' => "Testing"
     //                ],
     //                $firebase_id['org_id']
     //            );
				}
				if($data['approve_type'] == 'rejected')
				{
              // DB::setDefaultConnection('mongodb');
					// $firebase_id = User::where('_id',$user['_id'])->first(); 
					 
					// $this->sendPushNotification(
     //                $this->request,
     //                self::NOTIFICATION_TYPE_LEAVE_REJECTED,
     //                $firebase_id['firebase_id'],
     //                [
     //                    'phone' => "9881499768",
     //                    'update_status' => self::STATUS_REJECTED,
     //                    'approval_log_id' => "Testing"
     //                ],
     //                $firebase_id['org_id']
     //            );
				} 
				$database = $this->connectTenantDatabase($this->request);
                if ($database === null) 
                {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }
                $ApprovalsPending = ApprovalsPending::where('entity_id',$id)
                ->where('entity_type','form')
               ->first();

              
               /*  $ApprovalsPending['status'] = $data['approve_type'];
                $ApprovalsPending['action_by'] = $user['_id'];
                $ApprovalsPending['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));
                if($data['approve_type']=='rejected')
                {
                     if($data['reason'] =="" )
                     {
                         $response_data = array('status' =>300,'message'=>"Please Enter Rejected Reason");
                          return response()->json($response_data,200);
                     }
                    $ApprovalsPending['reason'] = $data['reason'];
                } */
				if($ApprovalsPending){

                $ApprovalsPending->delete();

               
				}	
				
				
                if($approval_log)
                {
                    $response_data = array('status' =>200,'message'=>"Form ".$data['approve_type']." successfully");
                    return response()->json($response_data,200); 
                }
                else
                {
                    $response_data = array('status' =>300,'data' => 'No rows found please check id','message'=>"error");
                    return response()->json($response_data,200); 
                }
        }
		
		/* if($data['type']=='compOff'){
			 
                $database = $this->connectTenantDatabase($this->request);
                if ($database === null) 
                {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }
                $id = $data['id'];
				$rolename = \App\Role::select('display_name')->where("_id",$ordId)->first();
                     if(isset($rolename['display_name']))
                     {
                        $newrolename =  $rolename['display_name'];
                     }
				$approval_log = new ApprovalLog;
				$approval_log['status'] = $data['approve_type'];    
				$approval_log['entity_type'] = $data['type'];
                $approval_log['user_id'] = $id;
                $approval_log['action_by'] = $user['_id'];
                $approval_log['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));
				$approval_log['reason'] = $data['reason'];
				// $approval_log->save();
				
                $ApprovalsPending = ApprovalsPending::where('type','compOff')->where('user_id',$id)->first();
                $ApprovalsPending['status'] = $data['approve_type'];
                $ApprovalsPending['action_by'] = $user['_id'];
                $ApprovalsPending['user_id'] = $user['_id'];
                $ApprovalsPending['action_on'] = new \MongoDB\BSON\UTCDateTime(new DateTime(date('y-m-d H:i:s')));
                if($data['approve_type']=='rejected')
                {
                     if($data['reason'] =="" )
                     {
                         $response_data = array('status' =>300,'message'=>"Please Enter Rejected Reason");
                          return response()->json($response_data,200);
                     }
                    $ApprovalsPending['reason'] = $data['reason'];
                }
				if($ApprovalsPending)
                // $ApprovalsPending->delete();
			    $leave_balance = PlannerUserLeaveBalance::where('user_id',$id)->first();
				// echo  date('Y-m-d',($data['startdate'] / 1000) ) ;
				$days = (strtotime(date('Y-m-d',($data['enddate'] / 1000) )) - strtotime(date('Y-m-d',($data['startdate'] / 1000) )))/60/60/24;
 
			    if($leave_balance)
				{ 
					$count = 0;
					$modify = 0;
					$index = count($leave_balance['leave_balance']); 
					 		
					foreach($leave_balance['leave_balance'] as $leave_bal)
					{  
						if($leave_bal['type'] == 'compOff')
						{   
							$modify = 1;
							$balance = $leave_bal['balance'] + $days; 
							$leave_balance['leave_balance.'.$count.'.balance'] = $balance; 
						}
						  // else{
							// $modify = 1;
							// echo '<pre>';
							// print_r($leave_balance);
							// echo '<pre>';
							// $leave_balance['leave_ balance.'.$index.'.balance'] = $days;  
							// $leave_balance['leave_balance.'.$index.'.type'] = 'compOff';  
						// } 
					$count ++;						
					}
					
					if($modify == 1) {
				    $leave_balance->save();	
					}
				    else
					{    
						// $leave_balance->leave_ balance = $days;  
						// $leave_balance->leave_ balance = 'compOff';
						// $leave_balance->save();	 						
					}
						
				
				}
                if($approval_log)
                {
                    $response_data = array('status' =>200,'message'=>"CompOff ".$data['approve_type']." successfully");
                    return response()->json($response_data,200); 
                }
                else
                {
                    $response_data = array('status' =>300,'data' => 'No rows found please check id','message'=>"error");
                    return response()->json($response_data,200); 
                }
        } */
    }




}

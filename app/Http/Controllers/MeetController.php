<?php 
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use DateTimeImmutable;
use DateTime;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;
use App\MatrimonyMeets;
use App\MatrimonyMasterData;
use App\Role;
use App\User;
use App\CommunityUser;
use App\Meet;
use App\Country;
use App\City;
use App\State;
use App\MeetTypes;
 

use Illuminate\Support\Arr;
date_default_timezone_set('Asia/Kolkata'); 
class MeetController extends Controller
{
    use Helpers;

    protected $request;
	
	public function __construct(Request $request) 
    {
        $this->request = $request;
		$this->logInfoPath = "logs/Meet/DB/Vlogs_".date('Y-m-d').'.log';
		$this->logerrorPath = "logs/Meet/Error/Vlogs_".date('Y-m-d').'.log';
    }
	  
	public function meet_types(Request $request)
	{
		if($request)
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
			$message['function'] = "meet_types";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200);  
		}
		$database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
		$meetType = MeetTypes::get();
		if($meetType)
                 {
                    $response_data = array('status'=>200,'data' => $meetType,'message'=>"success");
                    return response()->json($response_data,200); 
                }
                else
                {
                    $response_data = array('status' =>300,'message'=>"No Meet Types Found..");
                    return response()->json($response_data,200); 
                }
		}
		else
		{
			$response_data = array('status' =>300,'message'=>"Undefined Request..");
            return response()->json($response_data,200); 
		}
	
	}
	 
	//get list of roles with its users
	public function getMatrimonyRoleUsers(Request $request)
	{
		if($request)
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
			$message['function'] = "getMatrimonyRoleUsers";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200);  
		}
			$requestJson = json_decode(file_get_contents('php://input'), true);
			$requestJson['function'] = "getMatrimonyRoleUsers"; 
			$this->logData($this->logInfoPath,$requestJson,'DB');  
			$roles = Role::where('project_id',$requestJson['project_id'])->get();
			if(count($roles) > 0)
			{
				$responseData = array(); 
				foreach($roles as $role)
				{
					$userDetails = User::select('name','email','phone','orgDetails.role_id')->where('orgDetails.role_id',$role['_id'])->where('orgDetails.location.country',$requestJson['country_id'])->Orwhere('orgDetails.location.state',$requestJson['state_id'])->Orwhere('orgDetails.location.city',$requestJson['city_id'])->where('orgDetails.status.status','approved')->get();
					$mainData['_id'] = $role['_id'];
					$mainData['display_name'] = $role['display_name'];
					$mainData['userDetails']=$userDetails; 
					array_push($responseData,$mainData);
				 }
				if($responseData)
						 {
							$response_data = array('status'=>200,'data' => $responseData,'message'=>"success");
							return response()->json($response_data,200); 
						}
						else
						{
							$response_data = array('status' =>300,'message'=>"No roles defined for the project.");
							return response()->json($response_data,300); 
						}
			}else
				{
					$response_data = array('status' =>300,'message'=>"No roles defined for the project.");
					return response()->json($response_data,300); 
				}			
		}
		else
		{
			$response_data = array('status' =>300,'message'=>"Undefined Request..");
            return response()->json($response_data,200); 
		}		
	}

	//Insert Meet into db
	public function insertMeet(Request $request)
	{ 
		if($request)
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
			$message['function'] = "insertMeet";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200);  
		}		
		$database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
		$data = json_decode(file_get_contents('php://input'), true);
		$data['function'] = "insertMeet"; 
		$this->logData($this->logInfoPath,$data,'DB');
			
		$meet = new MatrimonyMeets;
	    $meet['title'] = $data['title'];
	    $meet['meetType'] = $data['meetType'];
	    // $meet['meetDescription'] = $data['meetDescription'];
		$meet['location.country'] = $data['location']['country'];
	    $meet['location.state'] = $data['location']['state'];
	    $meet['location.city'] = $data['location']['city'];
	    $meet['venue'] = $data['venue'];
	    $meet['schedule.dateTime'] = $data['schedule']['dateTime'];
		$start_date = Carbon::createFromTimestamp($data['schedule']['dateTime']/1000 );
		$startdate = new Carbon($start_date);
		$startdate->timezone = 'Asia/Kolkata';
		  
	    $meet['schedule.dateTiming'] = new \MongoDB\BSON\UTCDateTime($startdate->addHours(5)->addMinutes(30));
	    $meet['schedule.meetStartTime'] = $data['schedule']['meetStartTime'];
	    $meet['schedule.meetEndTime'] = $data['schedule']['meetEndTime'];
	    $meet['isRegPaid'] = $data['isRegPaid'];
	    $meet['registrationSchedule.regStartDateTime'] = $data['registrationSchedule']['regStartDateTime'];
	    $meet['registrationSchedule.regEndDateTime'] = $data['registrationSchedule']['regEndDateTime'];
	    $meet['regAmount'] = $data['regAmount'];
	    $meet['isOnlinePaymentAllowed'] = $data['isOnlinePaymentAllowed'];
	    $meet['meetImageUrl'] = 'https://ssyttestbucket.s3.ap-south-1.amazonaws.com/Screenshot_20190923-130101__01.jpg';//$data['meetImageUrl'];
	    $meet['is_published'] = $data['is_published'];
	    $meet['is_deleted'] = false;
	    $meet['is_archive'] = false;
	    $meet['is_allocate'] = false;
	    $meet['isBadgeFanlize'] = false;
	    $meet['contacts'] = [];
	    
		
		if(count($data['meetReferences']) > 0)
		{
			$Orgcount = 0; 
			foreach($data['meetReferences'] as $organizers)
			{ 
				$meet['meetOrganizers.'.$Orgcount.'.name'] = $organizers['name'];
				$meet['meetOrganizers.'.$Orgcount.'.phone'] = $organizers['phone'];
				// $meet['meetOrganizers.'.$Orgcount.'.role_id'] = $organizers['role_id'];
				$meet['meetOrganizers.'.$Orgcount.'.role_name'] = $organizers['role_name'];
				if(array_key_exists('email',$organizers)){
				$meet['meetOrganizers.'.$Orgcount.'.email'] = $organizers['email'];
				}
				$meet['meetOrganizers.'.$Orgcount.'._id'] = $organizers['_id'];
				
				$Orgcount ++;
			}	
	    }else{
			$meet['meetOrganizers'] = [];
		}
		if(count($data['meetReferences']) > 0)
		{
			$Refcount = 0;
			foreach($data['meetReferences'] as $references)
			{  
				$meet['meetReferences.'.$Refcount.'._id'] = $references['_id'];
				$meet['meetReferences.'.$Refcount.'.name'] = $references['name'];
				$meet['meetReferences.'.$Refcount.'.phone'] = $references['phone'];
				if(array_key_exists('email',$references)){
				$meet['meetReferences.'.$Refcount.'.email'] = ($references['email'] ?: '' ); 
				}
				// $meet['meetReferences.'.$Refcount.'.role_id'] = $references['role_id']; 
				$meet['meetReferences.'.$Refcount.'.role_name'] = $references['role_name']; 
				
				$Refcount ++;
			}	
	    }
		else{
			$meet['meetReferences'] = [];
		}
		try{ 
		$success = $meet->save();
		
		}catch(Exception $e)
			{
			$response_data = array('status' =>'200','message'=>'error','data' => $e);
			return response()->json($response_data,200); 
			}  
		
			if($success)
			{
				if($data['is_published'] == false){
					$msg = 'Meet has been created successfully, but it will be visible to users only after publishing.';
				}else{
					$msg = 'The meet has been published successfully and now visible to all the users.';
				}
				$response_data = array('status' =>'200','message'=>$msg);
				return response()->json($response_data,200); 
			}
			else
			{
				$response_data = array('status' =>400,'message'=>"Couldn't create the meet, please try after some time.");
				return response()->json($response_data,200); 
			}
		}else
		{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}	
	}
	
 
	
	//make an meet published
	public function meetpublished(Request $request,$meetId)
	{
		if($request)
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
				$message['function'] = "meetpublished";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}			
			$database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			$data = [
			'meetId'=>$meetId,
			'function'=>"meetpublished"
			];
			 
			$this->logData($this->logInfoPath,$data,'DB');
			if(isset($meetId))
			{ 
				$meet = MatrimonyMeets::find($meetId); 	
				$meet['is_published'] = true;
				try{ 
					$success = $meet->save();
					if($success)
					{
						$response_data = array('status' =>'200','message'=>'The meet has been published successfully and now visible to all the users.');
						return response()->json($response_data,200); 
					}
					else
					{
						$response_data = array('status' =>'300','message'=>"Couldn't publish the meet, please try after some time.");
						return response()->json($response_data,200); 
					}
				}
				catch(Exception $e)
				{ 
				 $response_data = array('status' =>'300','message'=>$e);
				 return response()->json($response_data,200); 	
				}
				 
			}
			else
			{
				$response_data = array('status' =>'500','message'=>"Couldn't find the meet.");
				return response()->json($response_data,200); 
			}
			
			
		}else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}			
	}
	 
	//get list of meets 
	public function getMeet(Request $request)
	{
		if($request)
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
				$message['function'] = "getMeet";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}			
			$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
			$requestJson = json_decode(file_get_contents('php://input'), true);
			$requestJson['function'] = "getMeet"; 
			$this->logData($this->logInfoPath,$requestJson,'DB');
			if(isset($requestJson['country_id']) || isset($requestJson['state_id']) || isset($requestJson['city_id']))
			{
				$citys = explode(',',$requestJson['city_id']);
				$currentDateTime = Carbon::now();
				$upcoming = MatrimonyMeets::select('_id')
				->where('location.country',$requestJson['country_id'])
				->whereOr('location.state',$requestJson['state_id'])
				->orWhereIn('location.city',$citys)
				->where('is_archive',false)
				->where('is_deleted',false)
				->where('schedule.dateTiming','>=',$currentDateTime)
				->orderby('schedule.dateTiming','asc')
				->offset(0)
				->limit(1)
				->get();
				 
				
				$roles = MatrimonyMeets::where('location.country',$requestJson['country_id'])->whereOr('location.state',$requestJson['state_id'])->orWhereIn('location.city',$citys)->where('is_archive',false)->where('is_deleted',false)
				->orderby('schedule.dateTiming','asc')
				->get();
				//echo json_encode($roles);die;
				if(!$upcoming->isEmpty())
				{
					$earliestMeetId = $upcoming[0]['_id']; 
				}else{
					$earliestMeetId = '';
				}				
				$mainData = array();	
				if(!$roles->isEmpty()){
					
				foreach($roles as $row)
				{  
					 
					$totalContacts = count($row['contacts']); 
					$userArr = array();
					$userArr['type'] = 'Total';
					$userArr['availableValue'] = $totalContacts;
					$userArr['totalValue'] = $totalContacts; 
					
					$maleArr = array();
					$femaleArr = array();
					$malepayArr = array();
					$femalepayArr = array();
					$male = 0;
					$maleage1 = 0;
					$maleage2 = 0;
					$maleage3 = 0;
					$maleage4 = 0;
					$femaleage1 = 0;
					$femaleage2 = 0;
					$femaleage3 = 0;
					$femaleage4 = 0;
					$female = 0;
					$malepay = 0;
					$femalepay = 0;
					$approved = 0;
					$pending = 0;
					$rejected = 0;
					foreach($row['contacts'] as $contact)
					{ 
					    
						if($contact['gender'] =='male')
						{ 
							$male ++;
							if($contact['age'] >=21 && $contact['age'] <=25)
							{
								$maleage1 ++;
							}
							if($contact['age'] >=26 && $contact['age'] <=30)
							{
								$maleage2 ++;
							}
							if($contact['age'] >=31 && $contact['age'] <=40)
							{
								$maleage3 ++;
							}
							if($contact['age'] >=40)
							{
								$maleage4 ++;
							}
						}
						if($contact['gender'] =='female')
						{
							$female ++;
							if($contact['age'] >=18 && $contact['age'] <=25)
							{
								$femaleage1 ++;
							}
							if($contact['age'] >=26 && $contact['age'] <=30)
							{
								$femaleage2 ++;
							}
							if($contact['age'] >=31 && $contact['age'] <=40)
							{
								$femaleage3 ++;
							}
							if($contact['age'] >=40)
							{
								$femaleage4 ++;
							}
						} 
						if($contact['paymentDone'] == true && $contact['gender'] =='male')
						{ 
							$malepay ++;
						}
						if($contact['paymentDone'] == true && $contact['gender'] =='female')
						{
							$femalepay ++;
						} 
						if($contact['isApproved'] == 'approved')
						{
							$approved ++;
						}
						if($contact['isApproved'] == 'pending')
						{
							$pending ++;
						}
						if($contact['isApproved'] == 'rejected')
						{
							$rejected ++;
						}
					}
					$temp = array();
					$totalPay = $malepay + $femalepay;
					
					$registrationArr = array();
					$registrationArr['displayLabel'] = "Registration Analysis"; 
					$registrationArr['description'] = ""; 
					$registrationArr['dataModules'] = array();
					
					$maleArr['totalValue'] = $totalContacts;
					$maleArr['type'] = 'Male';
					$maleArr['availableValue'] = $male; 
					
					$femaleArr['totalValue'] = $totalContacts;
					$femaleArr['type'] = 'Female';
					$femaleArr['availableValue'] = $female;		
						
					array_push($registrationArr['dataModules'],$userArr);
					array_push($registrationArr['dataModules'],$maleArr);					
					array_push($registrationArr['dataModules'],$femaleArr);
					
					//payment Analysis
					if($row['isRegPaid'] == true){
					// $userPayArr = array();
					// $userPayArr['totalValue'] = $totalContacts;
					// $userPayArr['type'] = 'Total';
					// $userPayArr['availableValue'] = $totalContacts; 
					
					// $malepayArr['totalValue'] = $totalContacts;
					// $malepayArr['type'] = 'Done';
					// $malepayArr['availableValue'] = $totalPay; 
					
					$remaining  = $totalContacts - $totalPay ;
					
					// $femalepayArr['totalValue'] = $totalContacts;
					// $femalepayArr['type'] = 'Remaining';
					// $femalepayArr['availableValue'] = $remaining; 
					
					// $PaymentArr = array();
					// $PaymentArr['displayLabel'] = "Payment Analysis"; 
					// $PaymentArr['description'] = ""; 
					// $PaymentArr['dataModules'] = array();
					
					// array_push($PaymentArr['dataModules'],$userPayArr);					
					// array_push($PaymentArr['dataModules'],$malepayArr);					
					// array_push($PaymentArr['dataModules'],$femalepayArr);
					
					}
					
					//pending approvals
					$ApprovalArr = array();
					$ApprovalArr['displayLabel'] = "Approval Analysis"; 
					$ApprovalArr['description'] = ""; 
					$ApprovalArr['dataModules'] = array();
					
					$ApprovalTotalArr = array();
					$ApprovalTotalArr['totalValue'] = $totalContacts;
					$ApprovalTotalArr['type'] = 'Total';
					$ApprovalTotalArr['availableValue'] = $totalContacts; 
					
					$ApprovalApprovedArr['totalValue'] = $totalContacts;
					$ApprovalApprovedArr['type'] = 'Approved';
					$ApprovalApprovedArr['availableValue'] = $approved; 
					
					$ApprovalPendingArr['totalValue'] = $totalContacts;
					$ApprovalPendingArr['type'] = 'Pending';
					$ApprovalPendingArr['availableValue'] = $pending;
					
					$ApprovalrejectedArr['totalValue'] = $totalContacts;
					$ApprovalrejectedArr['type'] = 'Rejected';
					$ApprovalrejectedArr['availableValue'] = $rejected; 
					
					array_push($ApprovalArr['dataModules'],$ApprovalTotalArr);
					array_push($ApprovalArr['dataModules'],$ApprovalrejectedArr);
					array_push($ApprovalArr['dataModules'],$ApprovalApprovedArr);						
					array_push($ApprovalArr['dataModules'],$ApprovalPendingArr);
					
										

					//male analytics
					$MaleAgeArr = array();
					$MaleAgeArr['displayLabel'] = "Male Age Analysis"; 
					$MaleAgeArr['description'] = ""; 
					$MaleAgeArr['dataModules'] = array();
					
					$userMaleAgeArr = array();
					$userMaleAgeArr['totalValue'] = $male;
					$userMaleAgeArr['type'] = 'Total';
					$userMaleAgeArr['availableValue'] = $male; 
					
					$userMaleAgeArr1 = array();
					$userMaleAgeArr1['totalValue'] = $male;
					$userMaleAgeArr1['type'] = '21-25';
					$userMaleAgeArr1['availableValue'] = $maleage1; 
					
					$userMaleAgeArr2 = array();
					$userMaleAgeArr2['totalValue'] = $male;
					$userMaleAgeArr2['type'] = '26-30';
					$userMaleAgeArr2['availableValue'] = $maleage2; 
					
					$userMaleAgeArr3 = array();
					$userMaleAgeArr3['totalValue'] = $male;
					$userMaleAgeArr3['type'] = '31-40';
					$userMaleAgeArr3['availableValue'] = $maleage3; 
					
					$userMaleAgeArr4 = array();
					$userMaleAgeArr4['totalValue'] = $male;
					$userMaleAgeArr4['type'] = 'Above 40';
					$userMaleAgeArr4['availableValue'] = $maleage4; 
					
						 
					array_push($MaleAgeArr['dataModules'],$userMaleAgeArr);
					array_push($MaleAgeArr['dataModules'],$userMaleAgeArr4);
					array_push($MaleAgeArr['dataModules'],$userMaleAgeArr3);
					array_push($MaleAgeArr['dataModules'],$userMaleAgeArr2);
					array_push($MaleAgeArr['dataModules'],$userMaleAgeArr1); 
					
					
					//female age analytics
					$feMaleAgeArr = array();
					$feMaleAgeArr['displayLabel'] = "Female Age Analysis"; 
					$feMaleAgeArr['description'] = ""; 
					$feMaleAgeArr['dataModules'] = array();
					
					$userfeMaleAgeArr = array();
					$userfeMaleAgeArr['totalValue'] = $female;
					$userfeMaleAgeArr['type'] = 'Total';
					$userfeMaleAgeArr['availableValue'] = $female; 
					
					$userfeMaleAgeArr1 = array();
					$userfeMaleAgeArr1['totalValue'] = $female;
					$userfeMaleAgeArr1['type'] = '18-25';
					$userfeMaleAgeArr1['availableValue'] = $femaleage1; 
					
					$userfeMaleAgeArr2 = array();
					$userfeMaleAgeArr2['totalValue'] = $female;
					$userfeMaleAgeArr2['type'] = '26-30';
					$userfeMaleAgeArr2['availableValue'] = $femaleage2; 
					
					$userfeMaleAgeArr3 = array();
					$userfeMaleAgeArr3['totalValue'] = $female;
					$userfeMaleAgeArr3['type'] = '31-40';
					$userfeMaleAgeArr3['availableValue'] = $femaleage3; 
					
					$userfeMaleAgeArr4 = array();
					$userfeMaleAgeArr4['totalValue'] = $female;
					$userfeMaleAgeArr4['type'] = 'Above 40';
					$userfeMaleAgeArr4['availableValue'] = $femaleage4; 
					
					array_push($feMaleAgeArr['dataModules'],$userfeMaleAgeArr); 
					array_push($feMaleAgeArr['dataModules'],$userfeMaleAgeArr4); 
					array_push($feMaleAgeArr['dataModules'],$userfeMaleAgeArr3);
					array_push($feMaleAgeArr['dataModules'],$userfeMaleAgeArr2);
					array_push($feMaleAgeArr['dataModules'],$userfeMaleAgeArr1);
					
					 
					array_push($temp,$ApprovalArr); 
					array_push($temp,$registrationArr); 
					if($row['isRegPaid'] == true){
					// array_push($temp,$PaymentArr);
					}					
					array_push($temp,$MaleAgeArr); 
					array_push($temp,$feMaleAgeArr); 
					unset($row['updated_at']);
					unset($row['created_at']);
					$row['analytics'] = $temp;
					
					$country = Country::find($row['location']['country']);
					$state = State::find($row['location']['state']);
					$city = City::find($row['location']['city']);
					
					$row['location.country'] = $country['name'];
					$row['location.state'] = $state['name'];
					$row['location.city'] = $city['name'];
					 
					array_push($mainData,$row); 
                    		 	
				}
			}else{
					$response_data = array('status'=>200,'message'=>"No Meet Found For Your Locations.");
					return response()->json($response_data,200); 
			}
				if($mainData)
				{  
					$response_data = array('status'=>200,'earliestMeetId'=>$earliestMeetId,'data' => $mainData,'message'=>"success");
					return response()->json($response_data,200); 
				}
				else
				{
					$response_data = array('status' =>300,'message'=>"No Meet Found..");
					return response()->json($response_data,200); 
				}
			}
			else{
				$response_data = array('status' =>300,'message'=>"Please Check your Location..");
				return response()->json($response_data,200); 
			}
		}else{
			$response_data = array('status' =>404,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}			
	}
	
	
	public function masterData(Request $request)
	{
		 
		if($request)
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
				$message['function'] = "masterData";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			$database = $this->connectTenantDatabase($request,$org_id); 
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
			$master = MatrimonyMasterData::get();
			if($master)
				{  
					$response_data = array('status'=>200,'data' => $master,'message'=>"success");
					return response()->json($response_data,200); 
				}
				else
				{
					$response_data = array('status' =>300,'message'=>"No Meet Found..");
					return response()->json($response_data,200); 
				}
			
		}else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}		
	}


	//check profile is available or not 
	public function checkProfile(Request $request,$mobile,$meetId)
	{
		if($request){
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
				$message['function'] = "checkProfile";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			
			DB::setDefaultConnection('bjsCommunity'); 
			$requestJson = ["phone"=>$mobile,"meetId"=>$meetId,"function"=>"checkProfile"]; 
			 
			$this->logData($this->logInfoPath,$requestJson,'DB');
			$userData = CommunityUser::where('phone', $mobile)->first();
			 
			if($userData && $userData!=null){
				if($userData['is_matrimonial_user'] == true)
					{
						$response_data = array('status'=>300,'data'=>$userData,'message'=>'Profile for the provided mobile number already exists.');
						return response()->json($response_data,200);
					}else{
					$response_data = array('status'=>200,'message'=>'No profile exists for the provided mobile number.');
					return response()->json($response_data,200);
				}

			}else{
					$response_data = array('status'=>200,'message'=>'No profile exists for the provided mobile number.');
					return response()->json($response_data,200);
			}
		}else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}
	}
	
	//insert community user to db
	public function insertUser(Request $request)
	{	 
		  if($request)
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
				$message['function'] = "insertUser";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			DB::setDefaultConnection('bjsCommunity'); 
			$requestJson = json_decode(file_get_contents('php://input'), true); 
			$requestJson['function'] = "insertUser"; 
			$this->logData($this->logInfoPath,$requestJson,'DB');
			// $userId = $requestJson['userId'];	
			
				 
			if(array_key_exists('userId',$requestJson)){  
					$userData = CommunityUser::where('_id',$requestJson['userId'])->first();
					
			}else{
				 
				$userData = new CommunityUser;
				
			}
			 
			
						
			//$userData = new User; 
			$userData['matrimonial_profile.personal_details.first_name'] = ($requestJson['personal_details']['first_name'] ?: '' );
			$name = ($requestJson['personal_details']['first_name'] ?: '' ) ." ".($requestJson['personal_details']['last_name'] ?: '' ) ;
			 
			$userData['name'] = $name;
			
			$userData['matrimonial_profile.personal_details.middle_name'] = ($requestJson['personal_details']['middle_name'] ?: '' );
			$userData['matrimonial_profile.personal_details.last_name'] = ($requestJson['personal_details']['last_name'] ?: '' ) ;
			$userData['matrimonial_profile.personal_details.complexion'] = ($requestJson['personal_details']['complexion'] ?: '' );
			$userData['matrimonial_profile.personal_details.birth_city'] = ($requestJson['personal_details']['birth_city'] ?: '' );
			$userData['matrimonial_profile.personal_details.birth_time'] = ($requestJson['personal_details']['birth_time'] ?: '' );
			 $userData['matrimonial_profile.personal_details.birth_date'] = (new \MongoDB\BSON\UTCDateTime($requestJson['personal_details']['birth_date']) ?: '' );
			
			$today = strtotime(date("Y-m-d")); 
			$epoch = $requestJson['personal_details']['birth_date'] / 1000;	
			$birth_date = strtotime(date("Y-m-d",$epoch)); 
			$diff = abs($birth_date - $today);
			$years = floor($diff / (365*60*60*24));  
			 	
			 $userData['matrimonial_profile.personal_details.age'] = ($years ?: '' );
			$userData['matrimonial_profile.personal_details.birthDate'] = ($requestJson['personal_details']['birth_date'] ?: '' );
			$userData['matrimonial_profile.personal_details.sect'] = ($requestJson['personal_details']['sect'] ?: '' );  
			 
			$userData['matrimonial_profile.personal_details.blood_group'] = ($requestJson['personal_details']['blood_group'] ?: '' );
			 $userData['matrimonial_profile.personal_details.height'] = ($requestJson['personal_details']['height'] ?: '' );
			$userData['matrimonial_profile.personal_details.weight'] = ($requestJson['personal_details']['weight'] ?: '' );
			$userData['matrimonial_profile.personal_details.gender'] = ($requestJson['personal_details']['gender'] ?: '' );
			$userData['matrimonial_profile.personal_details.is_manglik'] = ($requestJson['personal_details']['is_manglik'] ?: '' );
			$userData['matrimonial_profile.personal_details.marital_status'] = ($requestJson['personal_details']['marital_status'] ?: '' );
			$userData['matrimonial_profile.personal_details.match_patrika'] = ($requestJson['personal_details']['match_patrika'] ?: '' );
			// $userData['matrimonial_profile.personal_details.aadhar_number'] = ($requestJson['personal_details']['aadhar_number'] ?: '' );
			$userData['matrimonial_profile.personal_details.special_case'] = ($requestJson['personal_details']['special_case'] ?: '' );
			$userData['matrimonial_profile.personal_details.smoke'] = ($requestJson['personal_details']['smoke'] ?: '' );
			$userData['matrimonial_profile.personal_details.drink'] = ($requestJson['personal_details']['drink'] ?: '' );
			$userData['matrimonial_profile.personal_details.own_house'] = ($requestJson['personal_details']['own_house'] ?: '' );
			$userData['matrimonial_profile.educational_details.education_level'] = ($requestJson['educational_details']['education_level'] ?: '' );
			$userData['matrimonial_profile.educational_details.qualification_degree'] = ($requestJson['educational_details']['qualification_degree'] ?: '' );
			$userData['matrimonial_profile.educational_details.income'] = ($requestJson['educational_details']['income'] ?: '' );
			$userData['matrimonial_profile.occupational_details.occupation'] = ($requestJson['occupational_details']['occupation'] ?: '' );
			$userData['matrimonial_profile.occupational_details.employer_company'] = ($requestJson['occupational_details']['employer_company'] ?: '' );
			$userData['matrimonial_profile.occupational_details.business_description'] = ($requestJson['occupational_details']['business_description'] ?: '' );
			$userData['matrimonial_profile.family_details.family_type'] = ($requestJson['family_details']['family_type'] ?: '' );
			$userData['matrimonial_profile.family_details.gotra.self_gotra'] = ($requestJson['family_details']['gotra']['self_gotra'] ?: '' );
			$userData['matrimonial_profile.family_details.gotra.mama_gotra'] = ($requestJson['family_details']['gotra']['mama_gotra'] ?: '' );
			$userData['matrimonial_profile.family_details.gotra.dada_gotra'] = ($requestJson['family_details']['gotra']['dada_gotra'] ?: '' );
			$userData['matrimonial_profile.family_details.gotra.nana_gotra'] = ($requestJson['family_details']['gotra']['nana_gotra'] ?: '' );
			$userData['matrimonial_profile.family_details.father_name'] = ($requestJson['family_details']['father_name'] ?: '' );
			$userData['matrimonial_profile.family_details.father_occupation'] = ($requestJson['family_details']['father_occupation'] ?: '' );
			$userData['matrimonial_profile.family_details.family_income'] = ($requestJson['family_details']['family_income'] ?: '' );
			$userData['matrimonial_profile.family_details.mother_name'] = ($requestJson['family_details']['mother_name'] ?: '' );
			$userData['matrimonial_profile.family_details.mother_occupation'] = ($requestJson['family_details']['mother_occupation'] ?: '' );
			$userData['matrimonial_profile.family_details.brother_count'] = ($requestJson['family_details']['brother_count'] ?: '0' );
			$userData['matrimonial_profile.family_details.sister_count'] = ($requestJson['family_details']['sister_count'] ?: '0' );
			$userData['matrimonial_profile.residential_details.address'] = ($requestJson['residential_details']['address'] ?: '' );
			 
			$userData['matrimonial_profile.residential_details.country'] = ($requestJson['residential_details']['country'] ?: '' );
			$userData['matrimonial_profile.residential_details.state'] = ($requestJson['residential_details']['state'] ?: '' );
			$userData['matrimonial_profile.residential_details.city'] = ($requestJson['residential_details']['city'] ?: '' );
			$userData['matrimonial_profile.residential_details.secondary_phone'] = ($requestJson['residential_details']['secondary_phone'] ?: '' );
			$userData['matrimonial_profile.residential_details.primary_phone'] = ($requestJson['residential_details']['primary_phone'] ?: '' );
			$userData['matrimonial_profile.residential_details.primary_email_address'] = ($requestJson['residential_details']['primary_email_address'] ?: '' );
			$userData['matrimonial_profile.other_marital_information.about_me'] = ($requestJson['other_marital_information']['about_me'] ?: '' );
			$userData['matrimonial_profile.other_marital_information.expectation_from_partner'] = ($requestJson['other_marital_information']['expectation_from_partner'] ?: '' );
			
			if(is_array($requestJson['other_marital_information']['profile_image'])){
				
					$userData['matrimonial_profile.other_marital_information.profile_image'] =  $requestJson['other_marital_information']['profile_image'];	
					$userData['profile_image'] = $requestJson['other_marital_information']['profile_image']; 
				
			}   
			$userData['matrimonial_profile.other_marital_information.activity_achievements'] = ($requestJson['other_marital_information']['activity_achievements'] ?: '' );
			$userData['matrimonial_profile.other_marital_information.other_remarks'] = ($requestJson['other_marital_information']['other_remarks'] ?: '' ) ;
			if(isset($requestJson['other_marital_information']['aadhar_url'])){
			$userData['matrimonial_profile.other_marital_information.aadhar_url'] = ($requestJson['other_marital_information']['aadhar_url'] ?: '' ) ;
			}else{
				$userData['matrimonial_profile.other_marital_information.aadhar_url'] = '';
			}
			if(isset($requestJson['other_marital_information']['educational_url'])){
			$userData['matrimonial_profile.other_marital_information.educational_url'] = ($requestJson['other_marital_information']['educational_url'] ?: '' ) ;
			}else{
				$userData['matrimonial_profile.other_marital_information.educational_url'] ='';
			}
			$userData['matrimonial_meets'] =  array($requestJson['meet_id'],'5e550f0ef9c48377f0597424');  
			$userData['user_status'] = 'Active';
			$userData['isApproved'] = 'Pending';
			$userData['isPremium'] = true;
			$userData['isPaid'] = true;
			$userData['registered'] = true;
			$userData['is_matrimonial_user'] = true;
			$userData['lock'] = false;
			$userData['phone'] = ($requestJson['residential_details']['primary_phone'] ?: '' );
			$userData['password'] = app('hash')->make($requestJson['residential_details']['primary_phone']);
			$userData['email'] = $requestJson['residential_details']['primary_email_address'] ." ".$requestJson['residential_details']['primary_email_address'] ; 
			 
			try{ 
				if(array_key_exists('primary_phone',$requestJson['residential_details'])){				
					$userData1 = CommunityUser::where('phone',$requestJson['residential_details']['primary_phone'])->first();
					if($userData1)
					{
						$response_data = array('status' =>300,'message'=>'Moblie number already exists.');
						return response()->json($response_data,200); 	
					} 		
					} 
					
					
				$success = $userData->save();
				  
				$lastInsertId =  $userData->id;
				 
				if(isset($requestJson['meet_id']))
				{
					unset($userData['matrimonial_meets']);
					$user = $this->request->user();  
					
					
					$database = $this->connectTenantDatabase($request,$org_id); 
						if ($database === null) {
							return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
						}
					$MatrimonyMeets = MatrimonyMeets::where('_id',$requestJson['meet_id'])->first(); 
					$count = count($MatrimonyMeets['contacts']);
					$MatrimonyMeets['contacts.'.$count.'.userId'] = $lastInsertId;
					$MatrimonyMeets['contacts.'.$count.'.markAttendance'] = false;
					$MatrimonyMeets['contacts.'.$count.'.interviewDone'] = false;
					$MatrimonyMeets['contacts.'.$count.'.paymentDone'] = false;
					$MatrimonyMeets['contacts.'.$count.'.isApproved'] = 'pending';
					$MatrimonyMeets['contacts.'.$count.'.age'] = $years;
					$MatrimonyMeets['contacts.'.$count.'.gender'] = $requestJson['personal_details']['gender'];
					$date = date_create();
					$regDate = date_timestamp_get($date);
					$MatrimonyMeets['contacts.'.$count.'.regDate'] = $regDate;
					try{ 
					   $success = $MatrimonyMeets->save();
					   DB::setDefaultConnection('bjsCommunity');  
					   $userData = CommunityUser::find($lastInsertId);
					   $userData['unlock'] = $MatrimonyMeets['schedule.dateTiming'];
					   $userData->save();
								if($success)
								{
								$response_data = array('status' =>'200','message'=>'Candidate profile has been created successfully.');
								return response()->json($response_data,200); 
								}
					}catch(Exception $ex)
					{
					$response_data = array('status'=>200,'message'=>$ex);
					return response()->json($response_data,200);
					}
				}
				// default meet
					$database = $this->connectTenantDatabase($request,$user->org_id); 
						if ($database === null) {
							return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
						}
						$MatrimonyMeetsDefault = MatrimonyMeets::where('_id','5e550f0ef9c48377f0597424')->first(); 
						if($MatrimonyMeetsDefault){
						$count = count((array)$MatrimonyMeetsDefault['contacts']);  
						$MatrimonyMeetsDefault['contacts.'.$count.'.userId'] = $lastInsertId;
						$MatrimonyMeetsDefault['contacts.'.$count.'.markAttendance'] = false;
						$MatrimonyMeetsDefault['contacts.'.$count.'.interviewDone'] = false;
						$MatrimonyMeetsDefault['contacts.'.$count.'.paymentDone'] = false;
						$MatrimonyMeetsDefault['contacts.'.$count.'.isApproved'] = 'pending';
						$MatrimonyMeetsDefault['contacts.'.$count.'.age'] = $years;
						$MatrimonyMeetsDefault['contacts.'.$count.'.gender'] = $requestJson['personal_details']['gender'];
						$date = date_create();
						$regDate = date_timestamp_get($date);
						$MatrimonyMeetsDefault['contacts.'.$count.'.regDate'] = $regDate;
						$MatrimonyMeetsDefault->save(); 
						}
						//
				if($success)
				{  
					$response_data = array('status'=>200,'message'=>"Candidate profile has been created successfully");
					return response()->json($response_data,200); 
				}
				else
				{
					$response_data = array('status' =>300,'message'=>"The meet doesn't exist.");
					return response()->json($response_data,300); 
				}
			
			}catch(Exception $e)
			{
				$response_data = array('status' =>300,'message'=>$e);
			    return response()->json($response_data,300); 
			}
			
		}else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}  	
	}
	 
	//archive meet
	public function archiveMeet(Request $request,$meetId,$type)
	{
		if($request)
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
				$message['function'] = "archiveMeet";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			
			$requestJson = ['meetId'=>$meetId,'type'=>$type,'function'=>'archiveMeet'];
			$this->logData($this->logInfoPath,$requestJson,'DB');
			$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
		    if(isset($meetId))
			{
				$MatrimonyMeets = MatrimonyMeets::find($meetId); 
				if($MatrimonyMeets)
				{
					if($type == 'Deleted')
					
					$MatrimonyMeets['is_deleted'] = true;
					$delete = $MatrimonyMeets->delete();
					
					if($delete)
						 {
							$response_data = array('status' =>'200','message'=>'Meet has been '.$type);
							return response()->json($response_data,200);
						 }
					
				    if($type == 'Archive') 
					$MatrimonyMeets['is_archive'] = true;
				
				    $today = date('Y-m-d',strtotime(Carbon::today()));  
					$scheduleDate = $MatrimonyMeets['schedule']['dateTime'] /1000;
					$dt = new DateTime("@$scheduleDate");  // convert UNIX timestamp to PHP DateTime
					$scheduleDate = $dt->format('Y-m-d');
					 
					$curdate=strtotime($today);
					$scheduledate=strtotime($scheduleDate);

					if($curdate > $scheduledate)
					{ 
						try{
							$success = $MatrimonyMeets->save();
							 if($success)
							 {
								$response_data = array('status' =>'200','message'=>'Meet has been '.$type);
								return response()->json($response_data,200);
							 }
						}catch(Exception $e){
							$response_data = array('status' =>'300','message'=>$e);
							return response()->json($response_data,200);
						}
					}else{
						$response_data = array('status' =>300,'message'=>'Meet Cannot Archive Before Date..');
						return response()->json($response_data,200);
					}
				}else{
					$response_data = array('status' =>400,'message'=>"The meet doesn't exist.");
					return response()->json($response_data,200);
				}	
			}else{
			$response_data = array('status' =>400,'message'=>"The meet doesn't exist");
			return response()->json($response_data,200); 
		}	
				
				
		}else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}			
				
				
	}
	
	//user registeration against meet
	public function registration_meet(Request $request)
	{
		if($request)
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
				$message['function'] = "registration_meet";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
			
			$requestJson = json_decode(file_get_contents('php://input'), true);  
			$requestJson['function'] = "registration_meet";
 			$this->logData($this->logInfoPath,$requestJson,'DB');			
			$MatrimonyMeets = MatrimonyMeets::where('_id',$requestJson['meet_id'])->first();
			$unlock = $MatrimonyMeets['schedule.dateTime'];
			$unlockDate = $MatrimonyMeets['schedule.dateTiming'];
			 if($MatrimonyMeets)
			{
				$count = 1;
				$userCount = count($MatrimonyMeets['contacts']);
				if(count($MatrimonyMeets['contacts']) >0 )
				{ DB::setDefaultConnection('bjsCommunity'); 
					
				$userData = CommunityUser::where('phone',$requestJson['mobile'])->first();  
				 
				if($userData['is_matrimonial_user'] == true){  
				 
				foreach($MatrimonyMeets['contacts'] as $row)
				{ 
				 
					if($row['userId'] == $userData['_id'])
					{
						$response_data = array('status' =>'200','message'=>'You have been already registered in the meet.');
						return response()->json($response_data,200); 
					}
					else{ 
						   if($count == $userCount)
						   {
							    
							$MatrimonyMeets['contacts.'.$count.'.userId'] = $userData['_id'];
							$MatrimonyMeets['contacts.'.$count.'.markAttendance'] = false;
							$MatrimonyMeets['contacts.'.$count.'.interviewDone'] = false;
							$MatrimonyMeets['contacts.'.$count.'.paymentDone'] = false;
							$MatrimonyMeets['contacts.'.$count.'.isApproved'] = 'pending';
							$MatrimonyMeets['contacts.'.$count.'.age'] = $userData['matrimonial_profile']['personal_details']['age'];
							$MatrimonyMeets['contacts.'.$count.'.gender'] = $userData['matrimonial_profile']['personal_details']['gender'];  
							$date = date_create();
							$regDate = date_timestamp_get($date);
							$MatrimonyMeets['contacts.'.$count.'.regDate'] = $regDate;
							try{
								$database = $this->connectTenantDatabase($request,$org_id);
								
								$success = $MatrimonyMeets->save();
								
								$lastInsertIdMeet =  $MatrimonyMeets->_id;
								if(isset($userData['matrimonial_meets'])){
								$countUsers = count($userData['matrimonial_meets']);
								$userData['matrimonial_meets.'.$countUsers] = $lastInsertIdMeet;
								}else{
									$userData['matrimonial_meets.0'] = $lastInsertIdMeet;
								}
								if($userData['unlock'] > $unlock){
								$userData['unlock'] = $unlock;
								$userData['unlockDate'] = $unlockDate;
								}
								DB::setDefaultConnection('bjsCommunity'); 
								
								$success = $userData->save();
								
								if($success)
								{
								$response_data = array('status' =>'200','message'=>'You have been successfully registered in the meet.');
								return response()->json($response_data,200); 
								}else{
							   $response_data = array('status' =>'300','code' =>'300','message'=>"We couldn't process your registration request at the moment, please try after some time.");
							   return response()->json($response_data,200); 
								}
							}catch(Exception $e)
							{
								$response_data = array('status' =>400,'message'=>$e);
								return response()->json($response_data,200); 
							}
						  
						   }
						   
					}
					$count++; 
				}}else{
						$response_data = array('status' =>400,'message'=>"No profile exists for the provided mobile number.");
						return response()->json($response_data,200); 
				   }
				}
				else{
					 
					DB::setDefaultConnection('bjsCommunity'); 
					$userData = CommunityUser::where('phone',$requestJson['mobile'])->first(); 
					if($userData){ 
					$MatrimonyMeets['contacts.0.userId'] = $userData['_id'];
					$MatrimonyMeets['contacts.0.markAttendance'] = false;
					$MatrimonyMeets['contacts.0.interviewDone'] = false;
					$MatrimonyMeets['contacts.0.paymentDone'] = false;
					$MatrimonyMeets['contacts.0.isApproved'] = 'pending';
					$MatrimonyMeets['contacts.0.age'] = $userData['matrimonial_profile']['personal_details']['age'];
					$MatrimonyMeets['contacts.0.gender'] = $userData['matrimonial_profile']['personal_details']['gender'];
					
					try{
						$database = $this->connectTenantDatabase($request,$org_id);
						 
						// $success = $MatrimonyMeets->save();
						$lastInsertIdMeet =  $MatrimonyMeets->_id;
						$countUsers = count($userData['matrimonial_meets']);
						$userData['matrimonial_meets.'.$countUsers] = $lastInsertIdMeet;
						
						// if($userData['unlock'] > $unlock){
						$userData['unlock'] = $unlock;
						$userData['unlockDate'] = $unlockDate;
						// } 
						DB::setDefaultConnection('bjsCommunity'); 
						
						$success1 = $userData->save();
						if($success1)
						{
						$response_data = array('status' =>'200','message'=>'You have been successfully registered in the meet.');
						return response()->json($response_data,200); 
						}else{
							   $response_data = array('status' =>'300','code' =>'300','message'=>"We couldn't process your registration request at the moment, please try after some time.");
							   return response()->json($response_data,200); 
								}
					}catch(Exception $e)
					{
						$response_data = array('status' =>400,'message'=>$e);
						return response()->json($response_data,200); 
					}
				}else{
					$response_data = array('status' =>400,'message'=>"No profile exists for the provided mobile number.");
					return response()->json($response_data,200);
				}
				}				
			}
			else{
				$response_data = array('status' =>400,'message'=>"The meet doesn't exist.");
				return response()->json($response_data,200); 
			} 
				
		}
		else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}		
	} 
	
	//get users from meet
	public function getMeetUsers(Request $request,$meetId)
	{
		if($request)
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
				$message['function'] = "getMeetUsers";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			$requestJson = ['meetId'=>$meetId,'function'=>'getMeetUsers'];
			$this->logData($this->logInfoPath,$requestJson,'DB');
			$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
		
			$MatrimonyMeets = MatrimonyMeets::where('_id',$meetId)
											 
    										 ->first();

			if($MatrimonyMeets)
			{   
				DB::setDefaultConnection('bjsCommunity');
				$responseArr = array();	
				$count = 0;
				foreach($MatrimonyMeets['contacts'] as $row)
				{
					$userData = CommunityUser::find($row['userId']); 
					if($userData ){	

						if($row['isApproved'] == 'pending') {
							$userData['isApproved'] = $row['isApproved']; 
							$userData['markAttendance'] = $row['markAttendance']; 
							$userData['interviewDone'] = $row['interviewDone']; 
							$userData['paymentDone'] = $row['paymentDone']; 
							 
							array_push($responseArr,$userData);
						}
					 
					}
					if(count($responseArr) >= 100)
					{
						break;
					}
					$count ++;
				}
				if($responseArr)
				{
					$response_data = array('status' =>'200','message'=>$responseArr);
					return response()->json($response_data,200);
				}else{
					$response_data = array('status' =>'300','message'=>"No profile found in this meet.");
					return response()->json($response_data,200);
				} 
			}
			else{
				$response_data = array('status' =>'400','message'=>"No profile found in this meet. Be the first to register.");
				return response()->json($response_data,200);
			}
		}
		else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}
	}
	
	//approve users
	public function userApproval(Request $request)
	{ 
		if($request)
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
				$message['function'] = "userApproval";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'300','message'=>$message);
				return response()->json($response_data,200);  
			}			
			$requestJson = json_decode(file_get_contents('php://input'), true); 
			 
			$requestJson['function'] = "userApproval"; 
			$this->logData($this->logInfoPath,$requestJson,'DB');
			if($requestJson['type'] == 'user')
			{
				
				$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
			
				$MatrimonyMeets = MatrimonyMeets::where('_id',$requestJson['meet_id'])->first();
				if($MatrimonyMeets)
				{
					$count =0;
					$badgeCount =0;
					$title = $MatrimonyMeets['title'];
					$maleBadge =1;
					$femaleBadge = 200;

					$maleBadgeArr = [];
					$femaleBadgeArr = [];

					//echo json_encode($MatrimonyMeets['contacts']);
					if($requestJson['approval'] == 'approved')
					  {	
						foreach($MatrimonyMeets['contacts'] as $badgeCnt)
						{
							$userGender = $MatrimonyMeets['contacts.'.$badgeCount.'.gender'];
								if(isset($MatrimonyMeets['contacts.'.$badgeCount.'.badge']))
								{	
									if($userGender == "male" )
									{
										
										array_push($maleBadgeArr, (int)$MatrimonyMeets['contacts.'.$badgeCount.'.badge']);

									}else if($userGender == "female")
									 {
									 	
									 	array_push($femaleBadgeArr, (int)$MatrimonyMeets['contacts.'.$badgeCount.'.badge']);							 	
									 }
								}	 
							$badgeCount = $badgeCount + 1;	 	

						}
					  }	
					
					// $maxMalebadge = max($maleBadgeArr);
					 

					

					foreach($MatrimonyMeets['contacts'] as $row)
					{ 

						if($row['userId'] == $requestJson['user_id'] || $MatrimonyMeets['contacts.'.$count.'.isApproved'] =='pending')
						{  
							 
							$userGender = $MatrimonyMeets['contacts.'.$count.'.gender'];
							  if($requestJson['approval'] == 'approved')
							   {
								if($userGender == "male" )
									 {
									  if(isset($maleBadgeArr) && !empty($maleBadgeArr))
										 {
										 	$maxMalebadge = max($maleBadgeArr);
										 	$maleBadge = $maxMalebadge + 1;
										 }


									 	if($maleBadge <= 9)
									 	{
									 		$appendString = "00";
									 	}
									 	else if ($maleBadge > 9 && $maleBadge < 99) {
									 		$appendString = "0";
									 	}else if($maleContacts >99){
									 		$appendString = "";
									 	}

									 	$userBadge = $appendString.$maleBadge; 
									 	//$maleBadge =$maleBadge+1;
									 }else if($userGender == "female")
									 {
									 	if(isset($femaleBadgeArr) && !empty($femaleBadgeArr) )
											 {
											 	
											 	$maxFemalebadge = max($femaleBadgeArr);
											 	$femaleBadge = $maxFemalebadge + 1;
												

											 }
					

									 	$userBadge = (string)$femaleBadge; 
									 	//$femaleBadge= $femaleBadge+1;
									 }

									$MatrimonyMeets['contacts.'.$count.'.badge'] = $userBadge;	 
								}	 



							

						  	$MatrimonyMeets['contacts.'.$count.'.isApproved'] = $requestJson['approval'];
						  	$MatrimonyMeets['contacts.'.$count.'.rejection_reason'] = $requestJson['rejection_reason'];

						  	 
						  	// exit();

							try{

								$success = $MatrimonyMeets->save();

								if($success)
								{
									// $recordUpdated = 1;
									DB::setDefaultConnection('bjsCommunity');	
				 
									$firebase_id = CommunityUser::where('_id',$requestJson['user_id'])->first(); 
									if($requestJson['approval'] == 'approved')
									{
									if(isset($firebase_id['firebase_id']) && $firebase_id['firebase_id'] !="" && $firebase_id['firebase_id'] !="\n")
										{				
										 
										$this->SendNotification(
										$this->request,
										self::NOTIFICATION_TYPE_USER_APPROVED, 
										$firebase_id['firebase_id'],
										[
											'title'=>$title
										],
										$org_id
										);
										}
									}else{
										$this->SendNotification(
										$this->request,
										self::NOTIFICATION_TYPE_USER_REJECTION, 
										$firebase_id['firebase_id'],
										[
											'title'=>$title
										],
										$org_id
										);
											}
						
									$response_data = array('status' =>'200','message'=>'Profile '.$requestJson['approval'].' Successfully.');
									return response()->json($response_data,200); 
								}
								
							}
							catch(Exception $e)
							{
								$response_data = array('status' =>'200','message'=>$e);
								return response()->json($response_data,200); 
							}
						}

						

					
						$count++;					
					}
				}
				else{
					$response_data = array('status' =>400,'message'=>'Invalid Meet.');
					return response()->json($response_data,200); 
				}

				/* if($recordUpdated === 0)
				{
					$response_data = array('status' =>400,'message'=>'Action already taken on user');
					return response()->json($response_data,200); 
				} */

				
				
			}else{

				DB::setDefaultConnection('bjsCommunity');
				$UserData = CommunityUser::where('_id',$requestJson['user_id'])->first();
				if($UserData)
				{
					$UserData['isApproved'] = $requestJson['approval'];
					try{
						$UserDatasuccess = $UserData->save();
						if($UserDatasuccess)
						{
							
								DB::setDefaultConnection('bjsCommunity');	
				 
								$firebase_id = CommunityUser::where('_id',$requestJson['user_id'])->first(); 
								 
								 
									if(isset($firebase_id['firebase_id']) && $firebase_id['firebase_id'] !="" && $firebase_id['firebase_id'] !="\n")
									{				
									if($requestJson['approval'] == 'approved'){
									$this->sendPushNotification(
									$this->request,
									self::NOTIFICATION_TYPE_USER_APPROVED,
									$firebase_id['firebase_id'],
									[
										'phone' => "9881499768", 
										'update_status' => self::NOTIFICATION_TYPE_USER_APPROVED,
										'model' => "meet", 
										'approval_log_id' => "Testing"
									],
									$org_id
									);
										}else{
											$this->sendPushNotification(
									$this->request,
									self::NOTIFICATION_TYPE_USER_REJECTED,
									$firebase_id['firebase_id'],
									[
										'phone' => "9881499768", 
										'update_status' => self::NOTIFICATION_TYPE_USER_REJECTED,
										'model' => "meet", 
										'approval_log_id' => "Testing"
									],
									$org_id
									);
										}
									}
							
							$response_data = array('status' =>'200','message'=>'Profile '.$requestJson['approval'].' Successfully.');
							return response()->json($response_data,200); 
						}
					}
					catch(Exception $e)
					{
						$response_data = array('status' =>'200','message'=>$e);
						return response()->json($response_data,200); 
					}
				}else{
					$response_data = array('status' =>400,'message'=>'Invalid User.');
					return response()->json($response_data,200); 
				}
				
			}			
		}
		else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}
	} 
 
   //mark attendance and interview for the users in the meet.
   public function markAttendance_interview(Request $request)
   {
	   if($request)
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
				$message['function'] = "markAttendance_interview";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
				$requestJson = json_decode(file_get_contents('php://input'), true); 
				$requestJson['function'] = "markAttendance_interview"; 
				$this->logData($this->logInfoPath,$requestJson,'DB');
				$meetId = $requestJson['meet_id'];
				$userId = $requestJson['user_id'];
				$type = $requestJson['type'];
				 
 				$MatrimonyMeets = MatrimonyMeets::where('_id',$meetId)->first();
				if($MatrimonyMeets)
				{ 
					$today = date('Y-m-d',strtotime(Carbon::today()));  
					$scheduleDate = $MatrimonyMeets['schedule']['dateTime'] /1000;
					$dt = new DateTime("@$scheduleDate");  // convert UNIX timestamp to PHP DateTime
					$scheduleDate = $dt->format('Y-m-d');
					  
					
					$title = $MatrimonyMeets['title']; 
					$temp = $MatrimonyMeets['contacts']; 
					foreach($temp as &$row)
					{  
						if($row['userId'] == $userId)
						{
							if($type == 'Attendance'){  
						  	$row['markAttendance'] = true;  							
						  	$row['attendanceDateTime'] = new \MongoDB\BSON\UTCDateTime(Carbon::now());
							}
							if($type == 'Interview'){
						  	$row['interviewDone'] = true; 
							}
							break;
						} 				
					}
					try{ 
						$MatrimonyMeets['contacts'] = $temp; 
						// echo $today ."---".  $scheduleDate;die();
						if($today == $scheduleDate)
						{
							$success = $MatrimonyMeets->save();
						}else{
							$response_data = array('status' =>'200','message'=> $type." can be Marked only on the Scheduled Meet Date.");
							return response()->json($response_data,200); 
						}
						if($success)
						{
							DB::setDefaultConnection('bjsCommunity');	
				 
					$firebase_id = CommunityUser::where('_id',$userId)->first(); 
					 
					 
						if(isset($firebase_id['firebase_id']) && $firebase_id['firebase_id'] !="" && $firebase_id['firebase_id'] !="\n")
						{				
						if($type == 'Attendance')
						{
							$notitype = self::NOTIFICATION_TYPE_MEET_ATTENDANCE;
							$org_id = '';
						}if($type == 'Interview')
						{
							$notitype = self::NOTIFICATION_TYPE_MEET_INTERVIEW;
						}
						$this->SendNotification(
						$this->request,
						$notitype,
						$firebase_id['firebase_id'],
						[
							'title' => $title, 
							'update_status' => $notitype,
							'model' => "meet", 
							'approval_log_id' => "Testing"
						],
						$org_id
						);
						}
							$response_data = array('status' =>'200','message'=>$type.' Marked Successfully.');
							return response()->json($response_data,200); 
						}
					}
					catch(Exception $e)
					{
						$response_data = array('status' =>'200','message'=>$e);
						return response()->json($response_data,200); 
					}
					 
					
				}
				else{
					$response_data = array('status' =>400,'message'=>'Invalid Meet.');
					return response()->json($response_data,200); 
				}
				
		}
		else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}		
   }

	//Finalize the badges of the meet users.
   public function isFinalize(Request $request,$meetId)
   {
	   if($request)
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
				$message['function'] = "isFinalize";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}			
			$requestJson = ['meetId'=>$meetId,'function'=>'isFinalize'];
			$this->logData($this->logInfoPath,$requestJson,'DB');
			$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
				 
 				$MatrimonyMeets = MatrimonyMeets::where('_id',$meetId)
												  ->where('isBadgeFanlize',false)
												  ->first();
				if($MatrimonyMeets)
				{
					$MatrimonyMeets['isBadgeFanlize'] = true; 
					try{ 
						$success = $MatrimonyMeets->save();
						if($success)
						{ 
							$response_data = array('status' =>'200','message'=>'Badges have been Fanlized Successfully.');
							return response()->json($response_data,200); 
						}
					}
					catch(Exception $e)
					{
						$response_data = array('status' =>'200','message'=>$e);
						return response()->json($response_data,200); 
					}
				}
				else{
					$response_data = array('status' =>400,'message'=>'Badges have already been Finalized.');
					return response()->json($response_data,200); 
				}
				
		}
		else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}		
   }


	//allocate badge number to the registration users in the meet
	public function allocateBadge(Request $request,$meetId,$type)
	{
		
		if($request) 
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
				$message['function'] = "allocateBadge";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			$requestJson = ['meetId'=>$meetId,'type'=>$type,'function'=>'allocateBadge'];
			$this->logData($this->logInfoPath,$requestJson,'DB');
			$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}

				$meetInfo = MatrimonyMeets::where('_id',$meetId)
											->where('is_deleted',false)
											->where('is_archive',false) 
											->where('isBadgeFanlize',false) 
										 	->first();
					

				if($meetInfo)
				{
					
					if(isset($meetInfo['contacts']) && (count($meetInfo['contacts'])>0))
					{	
						$meetcontacts = $meetInfo['contacts'];
						$maleContacts=[];
						$femaleContacts=[];
						$maleBadge = 1;
						$femaleBadge = 201;
						$pendingCount = 0;
						foreach($meetcontacts as &$data)
						{
							if($data['isApproved'] == 'pending'){
								$pendingCount ++;
							}
							if($data['gender'] == "male" && $data['isApproved'] == 'approved')
						     {
							 	if($maleBadge <= 9)
							 	{
							 		$appendString = "00";
							 	}
							 	else if ($maleBadge > 9 && $maleBadge < 99) {
							 		$appendString = "0";
							 	}else if($maleContacts >99){
							 		$appendString = "";
							 	}

							 	$data['badge'] = $appendString.$maleBadge; 
							 	$maleBadge =$maleBadge+1;
							 }else if($data['gender'] == "female" && $data['isApproved'] == 'approved')
							 {
							 	$data['badge'] = (string)$femaleBadge; 
							 	$femaleBadge= $femaleBadge+1;
							 }
						}
					 
						//update meet contacts with allocated badge numbers
							$meetInfo['contacts'] = $meetcontacts;
							
							if($type == 'finalizeBadge')
							{
								$msg = 'Finalized';
								if($meetInfo['is_allocate'] == true){
								$meetInfo['isBadgeFanlize'] = true;
								if($pendingCount > 0)
								{
									$response_data = array('status'=>200,'message'=>"There are ".$pendingCount." profiles in the meet, yet to be verified.");
									return response()->json($response_data,200);
								}
								}else{
									$response_data = array('status'=>300,'message'=>"Please Allocate Badges First.");
									return response()->json($response_data,200);
								}
							}							 
							if($type == 'allocateBadge')
							{
								$msg = 'Allocated';
								$meetInfo['is_allocate'] = true; 
								if($pendingCount > 0)
								{
									$response_data = array('status'=>300,'message'=>"There are ".$pendingCount." profiles in the meet, yet to be verified.");
									return response()->json($response_data,200);
								}
							}
							
							try{
				             $meetInfo->save(); 
				            }catch(Exception $e)
				            {
				              return $e;
				            }  
					          if($meetInfo)
					          {
					            $response_data = array('status'=>200,'data'=>$meetInfo,'message'=>"Badges have been ".$msg."  Successfully.");
					            return response()->json($response_data,200);
					          }
 
					}else
					{
						$response_data = array('status' =>'300','message'=>'This Meet does not have any registered user.');
						return response()->json($response_data,200); 
					}
					

				}else{

						$response_data = array('status' =>'300','message'=>'Invalid meet request');
						return response()->json($response_data,200);

				}
				exit;										 	

		}
		else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}
 
	} 
	
	//badge of 5 male and female
	public function group_batches(Request $request,$meetId)
	{
		if($request)
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
				$message['function'] = "group_batches";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			$requestJson = ['meetId'=>$meetId,'function'=>'group_batches'];
			$this->logData($this->logInfoPath,$requestJson,'DB');			
			$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
		$MatrimonyMeets = MatrimonyMeets::where('_id',$meetId)->get();
		$tempArr = array();
		 
	 	if($MatrimonyMeets[0]['contacts'])
		{		 
	
			DB::setDefaultConnection('bjsCommunity');
			foreach($MatrimonyMeets[0]['contacts'] as $row)
			{ 
				$UserData = CommunityUser::select('matrimonial_profile.personal_details.first_name','matrimonial_profile.personal_details.last_name','matrimonial_profile.other_marital_information.profile_image')->where('_id',$row['userId'])->first();
				
				$row['name'] = $UserData['matrimonial_profile']['personal_details']['first_name'] ." ".$UserData['matrimonial_profile']['personal_details']['last_name'];
				if($UserData['matrimonial_profile']['other_marital_information']['profile_image'] == null )
				{$row['profile_image'] = "";
				}else{
				$row['profile_image'] = $UserData['matrimonial_profile']['other_marital_information']['profile_image'];	
				}
				if($row['markAttendance'] == true)
				{
					array_push($tempArr,$row);
				}
			} 
			
				if($tempArr)
				{	 
					$sortedArray = collect($tempArr)->sortBy('attendanceDateTime');
					$result['title'] = array();
					$result['male'] = array();
					$result['female'] = array(); 
					$group['male'] = array(); 
					$group['female'] = array(); 
					// echo count($sortedArray);die();
					foreach($sortedArray as $gender)
					{  
						if($gender['gender'] == 'male')
						{ 
							array_push($result['male'],$gender);
						}
						if($gender['gender'] == 'female')
						{
							array_push($result['female'],$gender);
						}
						if(count($result['male']) == 5) 
						{
							array_push($group['male'],$result['male']);
							unset($result['male']);
							$result['male'] = array();
						}
						if(count($result['female']) == 5) 
						{
							array_push($group['female'],$result['female']);
							unset($result['female']);
							$result['female'] = array(); 
						}							
					}
					if(count($result['male']) > 0)	
					array_push($group['male'],$result['male']);
					if(count($result['female']) > 0)
					array_push($group['female'],$result['female']);
					
					$max = (count($group['male']) > count($group['female'])) ? count($group['male']) : count($group['female']); 
					$responseGroup['Group'] = array();
					$insideGroup['male'] = array();
					$insideGroup['female'] = array();
					
					for($i=0;$i<$max;$i++)
					{
				     if(isset($group['male'][$i])){
					 array_push($insideGroup['male'],$group['male'][$i]);
					 }if(isset($group['female'][$i])){
					 array_push($insideGroup['female'],$group['female'][$i]);
					 }
					  array_push($responseGroup['Group'],$insideGroup);
					  unset($insideGroup); 
					  $insideGroup['male'] = array();
					  $insideGroup['female'] = array();
					}
					// echo json_encode($responseGroup);exit;
					if($responseGroup){
					$response_data = array('status' =>'200','data'=>$responseGroup);
					return response()->json($response_data,200); 
					}
				}else{
				$response_data = array('status' =>400,'message'=>'Nobody had attended the meet yet.');
				return response()->json($response_data,200); 
				}	
		}
		else{
			$response_data = array('status' =>400,'message'=>'No profile found in this meet.');
			return response()->json($response_data,200); 
			}
		}
		else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}
	}
	
	//search api for meet or user on the basis of type of request
	public function search(Request $request)
	{
		if($request)
		{
			$requestJson = json_decode(file_get_contents('php://input'), true); 
			$requestJson['function'] = "search"; 
			$this->logData($this->logInfoPath,$requestJson,'DB');
			if($requestJson['type'] == 'meet')
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
				$message['function'] = "search";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
				$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
				if($requestJson['for'] == 'detail'){
				$MatrimonyMeets = MatrimonyMeets::where('title','LIKE','%'.$requestJson['search'] .'%')->get();
				}else{
					$MatrimonyMeets = MatrimonyMeets::select('title')->where('title','LIKE','%'.$requestJson['search'] .'%')->get();
				}
				if(!$MatrimonyMeets->isEmpty())
				{
					$response_data = array('status' =>'200','message'=>'Meet List','data'=>$MatrimonyMeets);
					return response()->json($response_data,200); 
				}else{
					$response_data = array('status' =>'300','message'=>'No Result Found for your search.');
					return response()->json($response_data,200); 
				}
			}else if($requestJson['type'] == 'user'){
				DB::setDefaultConnection('bjsCommunity');
				if($requestJson['for'] == 'detail'){
				$userData = CommunityUser::where('matrimonial_profile.personal_details.first_name','LIKE','%'.$requestJson['search'] .'%')->get();
				}else{
					$userData = CommunityUser::select('matrimonial_profile.personal_details.first_name')->where('matrimonial_profile.personal_details.first_name','LIKE','%'.$requestJson['search'] .'%')->get();
				}
				if(!$userData->isEmpty())
				{
					$response_data = array('status' =>'200','message'=>'User List','data'=>$userData);
					return response()->json($response_data,200); 
				}else{
					$response_data = array('status' =>'300','message'=>'No Result Found for your search.');
					return response()->json($response_data,200); 
				}
			}else
			{
				$response_data = array('status' =>'300','message'=>'Please specify the type of search e.g Candidate or Meet.');
				return response()->json($response_data,200); 
			}	
		}
		else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}
	}
	 
	 public function UserToMeet(Request $request)
	{ 
		$org_id = '5ddfbb6bd6e2ef4f78207513';
		$database = $this->connectTenantDatabase($request,$org_id);
		if ($database === null) {
			return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
		}
		
			$MatrimonyMeets = MatrimonyMeets::where('_id','5e550f0ef9c48377f0597424')->first();
			
			  if($MatrimonyMeets)
			{
				DB::setDefaultConnection('bjsCommunity'); 
				 foreach($MatrimonyMeets['contacts'] as $row)
				 {
					$userData = CommunityUser::find($row['userId']);
					 
					$count = count($userData['matrimonial_meets']);
					$userData['matrimonial_meets.'.$count] = '5e550f0ef9c48377f0597424';
					$userData->save();
				 }
			}
				
				else{
					echo "No meet found.<br>";
				}	
	}
		
	

}




?>

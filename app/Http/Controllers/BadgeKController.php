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
class BadgeKController extends Controller
{
    use Helpers;

    protected $request;
	
	public function __construct(Request $request) 
    {
        $this->request = $request;
		$this->logInfoPath = "logs/Meet/DB/Vlogs_".date('Y-m-d').'.log';
		$this->logerrorPath = "logs/Meet/Error/Vlogs_".date('Y-m-d').'.log';
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
	
	
	public function allocateBadgeByAgeGroupK(Request $request, $meetId)
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
				$message['function'] = "removeBadges";
				$this->logData($this->logerrorPath ,$message,'Error');
				$response_data = array('status' =>'404','message'=>$message);
				return response()->json($response_data,200);  
			}
			$requestJson = ['meetId'=>$meetId,'function'=>'removeBadges'];
			$this->logData($this->logInfoPath,$requestJson,'DB');

			$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}

				$meetInfo = MatrimonyMeets::where('_id',$meetId)
											->where('is_deleted',false)
											//->where('is_archive',false) 
											->where('isBadgeFanlize',false) 
										 	->first();
				if($meetInfo)
				{
					
					if(isset($meetInfo['contacts']) && (count($meetInfo['contacts'])>0))
					{	
						$meetcontacts = $meetInfo['contacts'];
						$maleGrpOne=[];
						$maleGrpTwo=[];
						$maleGrpThree=[];
						$maleGrpFour=[];
						$femaleGrpOne=[];
						$femaleGrpTwo=[];
						$femaleGrpThree=[];
						$femaleGrpFour=[];

						$pendingUserGrp=[];
						$unsortedUserGrp=[];

						$updatedMeetcontacts=[];
						
						$maleBadge = 1;
						$femaleBadge = 201;
						$pendingCount = 0;
						$arrayIndex = 0;
						foreach($meetcontacts as $data)
						{
							
							$data['contactIndex'] = $arrayIndex;
							if($data['isApproved'] == 'approved')
							{ 
								if($data['gender'] == "female")
								{
									if($data['age'] >= 21 && $data['age'] <= 24 )
							     	{
							     		array_push($femaleGrpOne,$data);
							     	}else if($data['age'] >= 25 && $data['age'] <= 28)
							     	{
							     		array_push($femaleGrpTwo,$data);
							     	}else if($data['age'] >= 29 && $data['age'] <= 31)
							     	{
							     		array_push($femaleGrpThree,$data);
							     	}else if($data['age'] >= 32)
							     	{
							     		array_push($femaleGrpFour,$data);
							     	}else{

							     		array_push($unsortedUserGrp,$data);
							     	}
								} 
								else if($data['gender'] == "male") 
								{
									if($data['age'] >= 21 && $data['age'] <= 25 )
							     	{
							     		array_push($maleGrpOne,$data);
							     	}else if($data['age'] >= 26 && $data['age'] <= 29)
							     	{
							     		array_push($maleGrpTwo,$data);
							     	}else if($data['age'] >= 30 && $data['age'] <= 32)
							     	{
							     		array_push($maleGrpThree,$data);
							     	}else if($data['age'] >= 33)
							     	{
							     		array_push($maleGrpFour,$data);
							     	}else{

							     		array_push($unsortedUserGrp,$data);
							     	}
							 	}
							} else{
								array_push($pendingUserGrp,$data);
							}	 
						 	$arrayIndex = $arrayIndex + 1;
						}
					 
						//update meet contacts with allocated badge numbers
						//	$meetInfo['contacts'] = $meetcontacts;

						//echo json_encode($femaleGrpOne);
				     	

					 $getBadgeDataOne = $this->assignBadgeToGroup($femaleGrpOne,$maleGrpOne,1);

					 			
					 $twoGrpBadgeIndex = $getBadgeDataOne[2];

					 
					 array_push($updatedMeetcontacts, array_values($getBadgeDataOne[0]));
					 array_push($updatedMeetcontacts,  array_values($getBadgeDataOne[1]));
					 

					 $getBadgeDataTwo = $this->assignBadgeToGroup($femaleGrpTwo,$maleGrpTwo,$twoGrpBadgeIndex);


					 $threeGrpBadgeIndex = $getBadgeDataTwo[2];

					 array_push($updatedMeetcontacts, array_values($getBadgeDataTwo[0]));
					 array_push($updatedMeetcontacts,  array_values($getBadgeDataTwo[1]));
					 



					 $groupThreeRatio = $this->getRatio(count($femaleGrpThree),count($maleGrpThree));
					
					  

					 $getBadgeDataThree = $this->assignBadgeToGroup($femaleGrpThree,$maleGrpThree,$threeGrpBadgeIndex);

					 
					  $fourGrpBadgeIndex = $getBadgeDataThree[2];

					 
					 array_push($updatedMeetcontacts, $getBadgeDataThree[0]);
					 array_push($updatedMeetcontacts, $getBadgeDataThree[1]);


				  	 $getBadgeDataFour = $this->assignBadgeToGroup($femaleGrpFour,$maleGrpFour,$fourGrpBadgeIndex);

				  	 $fifthGrpBadgeIndex = $getBadgeDataFour[2];

				 	 array_push($updatedMeetcontacts, $getBadgeDataFour[0]);
					 array_push($updatedMeetcontacts, $getBadgeDataFour[1]);

					 array_push($updatedMeetcontacts, $pendingUserGrp);	
					 array_push($updatedMeetcontacts, $unsortedUserGrp);	

					 // echo '---Test---'.json_encode($updatedMeetcontacts);
					 // die();


					 	$newContactsArray =[];
					 	$meetInfo['contacts_new'] =[];
						
					 	//$meetInfo['contacts'] = $updatedMeetcontacts;
					 	foreach ($updatedMeetcontacts as $contactKey => $contactValue) {
					 		foreach ($contactValue as $key => $value) { 
					 			$newContactsArray[] = $value;
					 		}
					 		
					 	}
					 	 
						$columns = array_column($newContactsArray, 'contactIndex');
						array_multisort($columns, SORT_ASC, $newContactsArray);
						 
					 	$meetInfo['contacts'] = $newContactsArray;
						
							try{
				             $meetInfo->save();  
				            }catch(Exception $e)
				            {
				              return $e;
				            }  
					          if($meetInfo)
					          {
					            $response_data = array('status'=>200,'data'=>$meetInfo,'message'=>"Badges have been removed  Successfully.");
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

			}else{
			$response_data = array('status' =>400,'message'=>'Undefined Request.');
			return response()->json($response_data,200); 
			}						 	
	}


	

	public function group_batches_age(Request $request, $meetId)
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
				//if($row['markAttendance'] == true)
				//{
					array_push($tempArr,$row);
				//}
			} 
			
				if($tempArr)
				{	 
					$sortedArray = collect($tempArr)->sortBy('attendanceDateTime');
					$result['title'] = array();
					$result['male'] = array();
					$result['female'] = array(); 
					$group['male'] = array(); 
					$group['female'] = array();

					$maleGrp1=[];
					$maleGrp2=[];
					$maleGrp3=[];
					$maleGrp4=[];
					$femaleGrp1=[];
					$femaleGrp2=[];
					$femaleGrp3=[];
					$femaleGrp4=[]; 
					// echo count($sortedArray);die();
					foreach($sortedArray as $data)
					{  
						
						
						if($data['gender'] == "female")
							{
								if($data['age'] >= 21 && $data['age'] <= 24 )
						     	{
						     		array_push($femaleGrp1,$data);
						     	}else if($data['age'] >= 25 && $data['age'] <= 28)
						     	{
						     		array_push($femaleGrp2,$data);
						     	}else if($data['age'] >= 29 && $data['age'] <= 31)
						     	{
						     		array_push($femaleGrp3,$data);
						     	}else if($data['age'] >= 32)
						     	{
						     		array_push($femaleGrp4,$data);
						     	}
							} 
							else if($data['gender'] == "male") 
							{
								if($data['age'] >= 21 && $data['age'] <= 25 )
						     	{
						     		array_push($maleGrp1,$data);
						     	}else if($data['age'] >= 26 && $data['age'] <= 29)
						     	{
						     		array_push($maleGrp2,$data);
						     	}else if($data['age'] >= 30 && $data['age'] <= 32)
						     	{
						     		array_push($maleGrp3,$data);
						     	}else if($data['age'] >= 33)
						     	{
						     		array_push($maleGrp4,$data);
						     	}
						 	}	

						 						
					}
					$responseGroup['Group'] = array();
					// $insideGroup['male'] = array($maleGrpOne,$maleGrpTwo,$maleGrpThree,$maleGrpFour);
					// $insideGroup['female'] = array($femaleGrpOne,$femaleGrpTwo,$femaleGrpThree,$femaleGrpFour);

					$insideGroup['male'] = array();
					$insideGroup['female'] = array();

					

					// array_push($insideGroup['male'],array_values($maleGrpOne));
					// array_push($insideGroup['male'],array_values($maleGrpTwo));
					// array_push($insideGroup['male'],array_values($maleGrpThree));
					// array_push($insideGroup['male'],array_values($maleGrpFour));


					// array_push($insideGroup['female'],array_values($femaleGrpOne));
					// array_push($insideGroup['female'],array_values($femaleGrpTwo));
					// array_push($insideGroup['female'],array_values($femaleGrpThree));
					// array_push($insideGroup['female'],array_values($femaleGrpFour));

					for($i=1;$i<=4;$i++)
					{
						//echo json_encode($maleGrp.$i);

						 $maleGrpVar = 'maleGrp' . $i;
						 $femaleGrpVar = 'femaleGrp' . $i;
    		// 			echo json_encode($$maleGrpVar);

						// die();
						$maleColumns = array_column($$maleGrpVar, 'badge');
						array_multisort($maleColumns, SORT_ASC, $$maleGrpVar); 

						$femaleColumns = array_column($$femaleGrpVar, 'badge');
						array_multisort($femaleColumns, SORT_ASC, $$femaleGrpVar);

						array_push($insideGroup['male'],$$maleGrpVar);
						array_push($insideGroup['female'],$$femaleGrpVar);
						array_push($responseGroup['Group'],$insideGroup);

							unset($insideGroup); 
					 	 $insideGroup['male'] = array();
					 	 $insideGroup['female'] = array();
					}

					//array_push($responseGroup['Group'],$insideGroup);

					// $responseGroup['Group']['female'] = $femaleGrpOne;

					// $responseGroup['Group']['male'] = $maleGrpTwo;
					// $responseGroup['Group']['female'] = $femaleGrpTwo;

					// $responseGroup['Group']['male'] = $maleGrpThree;
					// $responseGroup['Group']['female'] = $femaleGrpThree;

					// $responseGroup['Group']['male'] = $maleGrpFour;
					// $responseGroup['Group']['female'] = $femaleGrpFour;

					//echo json_encode($responseGroup);
					//die();

					
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

	/**
	 * Calculate the ratio between two numbers.
	 * 
	 * @param int $num1 The first number.
	 * @param int $num2 The second number.
	 * @return string A string containing the ratio.
	 */
	 
 

	public function getRatio($num1, $num2){

			if($num1%2 ==0){
				$num1 = $num1;
			}else{
				$num1 = $num1+1;
			}if($num2%2 ==0){
				$num2 = $num2;
			}else{
				$num2 = $num2+1;
			}

		    for($i = $num2; $i > 1; $i--) {
		        if(($num1 % $i) == 0 && ($num2 % $i) == 0) {
		            $num1 = $num1 / $i;
		            $num2 = $num2 / $i;
		        }
		    }
		    return "$num1:$num2";
	}



	public function assignBadgeToGroup($femaleGrp,$maleGrp, $arrayIndex = NULL){


		$groupRatio = $this->getRatio(count($femaleGrp),count($maleGrp));
		$grpCount= explode(':', $groupRatio);

		//echo $arrayIndex.'-------'.json_encode($grpCount);
		
		
		$p=$arrayIndex;	// get max badge number from db and assign here	
		$q=$arrayIndex;
		$checkIndex =  $grpCount[0]+$p;		
				
		foreach ($femaleGrp as &$femaleGrpValue)
		{
			//for($i=0,$i<=$grpFourCount[0])
			$femaleGrpValue['badge']= $p;
			$p++;
			if(  $checkIndex == $p )
			{
				$p = $grpCount[1] + $p;//-1);
				$checkIndex = $p + $grpCount[0];
				//break;
			}

		}
		
		//echo json_encode($femaleGrp);
		// die();
		$q = $grpCount[0] + $q;
		$checkIndexQ =  $grpCount[1] + $grpCount[0]+$arrayIndex;
		foreach ($maleGrp as &$maleGrpValue)
		{
			
			$maleGrpValue['badge']= $q;
			$q++;
			if($checkIndexQ == $q )
			{
				 $q = $grpCount[0] + $q;//-1);
				 $checkIndexQ = $q;// + $grpFourCount[0];
				//break;
			}
		}

		if(($p-1) > $q)
		{
			$nextArrayIndex = $p-1;
		}else
		{
			$nextArrayIndex = $q;
		}

		// echo json_encode($femaleGrp);
		// echo json_encode($maleGrp);
		// die();
		return array($femaleGrp,$maleGrp,$nextArrayIndex);
		

	}




	



		
}




?>

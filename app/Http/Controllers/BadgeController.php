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
class BadgeController extends Controller
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
											->where('is_archive',false) 
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
						$maleBadge = 1;
						$femaleBadge = 201;
						$pendingCount = 0;
						
						foreach($meetcontacts as &$data)
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
						     	}
						 	} 
						}
					 
						//update meet contacts with allocated badge numbers
						//	$meetInfo['contacts'] = $meetcontacts;

						//echo json_encode($femaleGrpOne);

					 $groupOneRatio = $this->getRatio(count($femaleGrpOne), count($maleGrpOne));
					 $groupTwoRatio = $this->getRatio(count($femaleGrpTwo),count($maleGrpTwo));
					 $groupThreeRatio = $this->getRatio(count($femaleGrpThree),count($maleGrpThree));
					 $groupFourRatio = $this->getRatio(count($femaleGrpFour),count($maleGrpFour));
					  
						$grpOneCount= explode(':', $groupOneRatio);
						
						$i=1;		
						$j=1;		
						foreach ($femaleGrpOne as &$femaleGrpOneValue)
						{
							$femaleGrpOneValue['badge']= $i;
							$i++;
							$i = $grpOneCount[1] + $i;
						}
						//echo json_encode($femaleGrpOne);

						foreach ($maleGrpOne as &$maleGrpOneValue)
						{
							$j = $grpOneCount[0] + $j;
							$maleGrpOneValue['badge']= $j;
							$j++;
						}
						//echo json_encode($maleGrpOne);


						$grpFourCount= explode(':', $groupFourRatio);

						//echo json_encode($grpFourCount);
						
						
						$p=1;		
						$q=1;		
						foreach ($femaleGrpFour as &$femaleGrpFourValue)
						{
							//for($i=0,$i<=$grpFourCount[0])
							$femaleGrpFourValue['badge']= $p;
							$p++;
							if((($p-1) % $grpFourCount[0]) == 0 )
							{
								$p = $grpFourCount[1] + ($p-1);
							}
						}
						echo json_encode($femaleGrpFour);
						die();
						foreach ($maleGrpFour as &$maleGrpFourValue)
						{
							$q = $grpFourCount[0] + $q;
							$maleGrpFourValue['badge']= $q;
							$q++;
						}




							die();
												 
						
							try{
				           //  $meetInfo->save(); 
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


	public function allocateBadgeByAgeGroupJ(Request $request, $meetId)
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
											->where('is_archive',false) 
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
						$maleBadge = 1;
						$femaleBadge = 201;
						$pendingCount = 0;
						
						foreach($meetcontacts as &$data)
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
						     	}
						 	} 
						}

					 // $groupOneRatio = $this->getRatio(count($femaleGrpOne), count($maleGrpOne));
					 // $ratioArray=array(4,7);
					 // $groupOneRatio = $this->divide(22, $ratioArray);
					 		 $num1=53;
							 $num2=79;
							  
								if($num1%2 ==0){
									$num1 = $num1;
								}else{
									$num1 = $num1+1;
								}if($num2%2 ==0){
									$num2 = $num2;
								}else{
									$num2 = $num2+1;
								}
								
								$max = max($num1,$num2);
								$min = min($num1,$num2);
								
								echo $this->getRatio($max,$min);
								// while(1)
								// {
							 	  // if($max%$num1==0 && $max%$num2==0)
									// {
									  // echo "LCM of " .$num1. " and " .$num2. " is: ".$max;
									  // break;
									// }
								// $max=$max+1;
								// }
					 // echo $groupOneRatio;
					 die();
					 $groupTwoRatio = $this->getRatio(count($femaleGrpTwo),count($maleGrpTwo));
					 $groupThreeRatio = $this->getRatio(count($femaleGrpThree),count($maleGrpThree));
					 $groupFourRatio = $this->getRatio(count($femaleGrpFour),count($maleGrpFour));
					
						$grpOneCount= explode(':', $groupOneRatio);
						
						$i=1;		
						$j=1;		
						
						foreach ($femaleGrpOne as &$femaleGrpOneValue)
						{
							$femaleGrpOneValue['badge']= $i;
							$i++;
							$i = $grpOneCount[1] + $i;
						}
						echo json_encode($femaleGrpOne);die();

						foreach ($maleGrpOne as &$maleGrpOneValue)
						{
							$j = $grpOneCount[0] + $j;
							$maleGrpOneValue['badge']= $j;
							$j++;
						}
						//echo json_encode($maleGrpOne);


						$grpFourCount= explode(':', $groupFourRatio);

						//echo json_encode($grpFourCount);
						
						
						$p=1;		
						$q=1;		
						foreach ($femaleGrpFour as &$femaleGrpFourValue)
						{
							//for($i=0,$i<=$grpFourCount[0])
							$femaleGrpFourValue['badge']= $p;
							$p++;
							if((($p-1) % $grpFourCount[0]) == 0 )
							{
								$p = $grpFourCount[1] + ($p-1);
							}
						}
						echo json_encode($femaleGrpFour);
						die();
						foreach ($maleGrpFour as &$maleGrpFourValue)
						{
							$q = $grpFourCount[0] + $q;
							$maleGrpFourValue['badge']= $q;
							$q++;
						}

							try{
				           //  $meetInfo->save(); 
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

	/**
	 * Calculate the ratio between two numbers.
	 * 
	 * @param int $num1 The first number.
	 * @param int $num2 The second number.
	 * @return string A string containing the ratio.
	 */
	public function getRatio($num1, $num2){
		    for($i = $num2; $i > 1; $i--) {
		        if(($num1 % $i) == 0 && ($num2 % $i) == 0) {
		            $num1 = $num1 / $i;
		            $num2 = $num2 / $i;
		        }
		    }
		    return "$num1:$num2";
	}


	public function divide($total,$ratioArray)
	{
		$female=[];
		$femalearray=[];
		$male=[];
		$femaleCount = $ratioArray[0];
		$maleCount = $ratioArray[1];
		for($i=1;$i<=$total;$i++)
		{
			if($i<=$femaleCount)
			{
				$female['badgeFemale'] = $i;
				array_push($femalearray,$female);
			}else{
				// $femaleCount = 
			}
				
		}
		print_r($femalearray);die();
	}
	public function gcf()
	{
		 $num1=90;
		 $num2=75;
		  $max=($num1>$num2) ? $num1 : $num2;
			while(1)
			{
			  if($max%$num1==0 && $max%$num2==0)
				{
				  echo "LCM of " .$num1. " and " .$num2. " is: ".$max;
				  break;
				}
			$max=$max+1;
			}
	}
	



		
}




?>

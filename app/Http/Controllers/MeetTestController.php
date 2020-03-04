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
use App\Meet;
use App\MeetTypes;


use Illuminate\Support\Arr;
date_default_timezone_set('Asia/Kolkata'); 
class MeetTestController extends Controller
{
    use Helpers;

    protected $request;
	
	public function __construct(Request $request) 
    {
        $this->request = $request;
    }
	 
    public function allocateBadge(Request $request,$meetId)
	{
		
		if($request)
		{
			$user = $this->request->user();  
			$database = $this->connectTenantDatabase($request,$user->org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}

				$meetInfo = MatrimonyMeets::where('_id',$meetId)
											//->where('is_deleted',false)
											//->where('is_archive',false)
											->orderby('created_at','desc')
										 	->first();
					

				if($meetInfo)
				{
					
					if(isset($meetInfo['contacts']) && (count($meetInfo['contacts'])>0))
					{	
						$meetcontacts = $meetInfo['contacts'];
						$maleContacts=[];
						$femaleContacts=[];
						$maleBadge = 1;
						$femaleBadge = 200;
						foreach($meetcontacts as &$data)
						{
							if($data['gender'] == "male")
							// if($key =='gender' &&  $value == 'male' )
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
							 	//array_push($maleContacts, $data);
							 	$maleBadge =$maleBadge+1;
							 }else if($data['gender'] == "female")
							 {
							 	$data['badge'] = (string)$femaleBadge;
							 	//array_push($femaleContacts, $data);
							 	$femaleBadge= $femaleBadge+1;
							 }
						}
						
						//update meet contacts with allocated badge numbers
							$meetInfo['contacts'] = $meetcontacts;
							//echo json_encode($meetInfo);
							//die();

							try{
				             $meetInfo->save(); 
				            }catch(Exception $e)
				            {
				              return $e;
				            }  
					          if($meetInfo)
					          {
					            $response_data = array('status'=>200,'data'=>$meetInfo,'message'=>"success");
					            return response()->json($response_data,200);
					          }




					}else
					{
						$response_data = array('status' =>'300','message'=>'Meet not have any registered user.');
						return response()->json($response_data,200); 
					}
					

				}else{

						$response_data = array('status' =>'300','message'=>'Invalid meet request');
						return response()->json($response_data,200);

				}
				exit;										 	

		}
		else{
			$response_data = array('status' =>'404','message'=>'Undefined Request.');
			return response()->json($response_data,200); 
		}



	}
}




?>
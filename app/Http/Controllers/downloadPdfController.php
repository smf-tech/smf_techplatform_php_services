<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use DateTimeImmutable;
use DateTime;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;
// use App\MatrimonyMeets;
// use App\MatrimonyMasterData;
// use App\Role;
 use App\User;
 use App\MatrimonyMeets;
use PDF;

//namespace App\Mail;
use Illuminate\Support\Facades\Mail; 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;


use Illuminate\Support\Arr;
date_default_timezone_set('Asia/Kolkata'); 
class downloadPdfController extends Controller
{
	use Helpers;

    protected $request;
	
	public function __construct(Request $request) 
    {
        $this->request = $request;

    }

    public function downloadBooklet(Request $request,$meetId,$gridType)
	{
			
        $user = $this->request->user();
         $to_email_address = $user->email;
        //echo json_encode($user);
        //die();
         $viewGridType = $gridType.'Grid'; 
					
		$responseArr = [];
		$userData = [];

		if($user){
			
			$database = $this->connectTenantDatabase($request,$user->org_id);
			$meetContacts = MatrimonyMeets::where('_id',$meetId)
							->first();	
			$meet_title = $meetContacts->title;
		    $meet_date = date('d F Y', substr($meetContacts->schedule['dateTiming'], 0, 10));
			
			$mail_subject = $meet_title."-".$meet_date;
			//	exit();			
			$male_cnt = 0;				
			$female_cnt = 0;
			
						
						
			if(isset($meetContacts->contacts) && !is_null($meetContacts->contacts) )
			{

				DB::setDefaultConnection('bjs_community'); 
				foreach ($meetContacts->contacts as $profileIds) {
					
					$userDataInfo = User::where('_id',$profileIds['userId'])->get();
					if(count($userDataInfo)>0){
						$userGender = $userDataInfo[0]['matrimonial_profile']['personal_details']['gender'];

						$userBirthDateInfo = $userDataInfo[0]['matrimonial_profile']['personal_details']['birthDate'];

						$userBadge = $profileIds['badge']??'-';
						
						$userBirthDate = date('d F Y', ($userBirthDateInfo/1000));
						
						
							if($userGender == 'male')
							{	
								$userData['male'][$male_cnt] = $userDataInfo[0]['matrimonial_profile'];
								$userData['male'][$male_cnt]['birthDate'] =  $userBirthDate;
								$userData['male'][$male_cnt]['badge'] =  $userBadge;
								$male_cnt = $male_cnt+1;
							}else if($userGender == 'female')
							{
								$userData['female'][$female_cnt] = $userDataInfo[0]['matrimonial_profile'];
								$userData['female'][$female_cnt]['birthDate'] =  $userBirthDate;
								$userData['female'][$female_cnt]['badge'] =  $userBadge;
								$female_cnt = $female_cnt+1;
							}	
					
					
								
					
					}
				}
				
				//echo json_encode($userData);
				//die();
				
				if($userData)
				{
					$response_data = array('code' =>200, 'status' =>'200','message'=>'success', 'data'=>$userData);

					//$summary = $this->getUserSummary($request);
					
					$pdf = app()->make('dompdf.wrapper');
					//return loadView('booklet.oneGrid',compact($userData));
					$pdf->loadView('booklet.'.$viewGridType, compact('userData'))->setPaper('a4', 'portrait');
						
					return $pdf->stream();
					exit;
			

				
				}else{
					$response_data = array('code' =>403, 'status' =>'403','message'=>'Not have any register profile for the meet.');
					return response()->json($response_data,200);
				} 

			}
			else
			{
				$response_data = array('code' =>403, 'status' =>'403','message'=>'Invalid meet');
                return response()->json($response_data,200);
			}	

		} else
		{

			$response_data = array('code' =>403, 'status' =>'403','message'=>'Invalid profile');
            return response()->json($response_data,200);

		}

	}


}

?>

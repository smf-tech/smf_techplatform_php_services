<?php 
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use DateTimeImmutable;
use DateTime;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;
use App\User; 
use App\DownloadPDFRequest;
use App\MatrimonyMeets;
use Illuminate\Support\Facades\Queue;
use App\Jobs\DownloadPDFLink;
 

use Illuminate\Support\Arr;
date_default_timezone_set('Asia/Kolkata'); 
class DownloadPDFRequestController extends Controller
{
    use Helpers;

    protected $request;
    
    public function __construct(Request $request){ 
    
        $this->request = $request;
        $this->logInfoPah = "logs/PDF/DB/logs_".date('Y-m-d').'.log';
        $this->errorPath = "logs/PDF/Error/logs_".date('Y-m-d').'.log';

    }

    public function downloadBookletPDF(Request $request,$meetId,$gridType){

        $user = $this->request->user(); 
       // echo json_encode($user);
        //die();

       if($request)
        { 
            $user = $this->request->user(); 
            if($user!= null)
            { 
                $database = $this->connectTenantDatabase($request,$user->org_id);
                if ($database === null) {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }else
                {

                    $to_email_address = $user->email;
                    $viewGridType = $gridType.'Grid'; 
                    $responseArr = [];
                    $userData = [];

                    $meetContacts = MatrimonyMeets::where('_id',$meetId)
                            ->first();  

                    //echo json_encode($meetContacts);
                    ///die();        

                    $pdfRequest = new DownloadPDFRequest;

                    $pdfRequest['meed_id'] = $meetId;
                    $pdfRequest['meet_title'] = $meetId;//$meetContacts->title;
                    $pdfRequest['grid_type'] = $gridType;
                    $pdfRequest['action_by'] = $user['_id'];  
                    $pdfRequest['status'] = 'inprogress';  
                    $pdfRequest['created_DateTime'] = Carbon::now();  
                    $pdfRequest['created_at'] = (new \MongoDB\BSON\UTCDateTime(Carbon::now()) ?: '' ); 

                    $success = $pdfRequest->save();

                    if($success)
                    {
                        //echo json_encode($pdfRequest);
                        Queue::push(new DownloadPDFLink($pdfRequest));
                    }


                }




            }
            else
            {
                 return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);

            }    

        }    
    
        

    }

 

    
}
?>
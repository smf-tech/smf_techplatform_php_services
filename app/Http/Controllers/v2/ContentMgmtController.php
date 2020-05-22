<?php

//owner:Jitendra
use Illuminate\Support\Facades\Session;
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
use App\SevaContents;
use App\ContentManagement;
use App\ContentCategories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

use Carbon\Carbon;
use App\Category;


use Illuminate\Support\Arr;

class ContentMgmtController extends Controller
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


    public function contentDashboard(Request $request)
    {
            $user = $this->request->user();
            // $all_user=User::select('role_id')->where('approve_status','pending')->get();
            $database = $this->connectTenantDatabase($request,$user->org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            }
                // echo  'Role'.$user->role_id;
                // echo 'project'.$user->projec_id;

                // echo 'org_id'.$user->org_id;
                // exit;
            $content = ContentManagement::select('name','url','category_id')
                           // ->with('ContentCategories')
                            ->get()

                            ->groupBy('category_id') ;

             $contentCategory = ContentCategories::get();
            // print_r($contentCategory);
            // exit;

              $contentData =[];
              $contentTemp =[];
              foreach ($content as $key => $value) {
                        foreach ($contentCategory as  $categoryValue) {                            
                            if($key == $categoryValue['_id']){
                              $contentTemp['title']=   $categoryValue['title'];
                              $contentTemp['data'] = $value;
                              array_push($contentData,  $contentTemp) ; 
                            }
                        }

                }  
     
        
            $data = [
                [
                  "subModule"=> "contentData",
                  "contentData" => $contentData
                ]
                ];
            
            if($data)
        {
            $response_data = array('status' =>'200', 'message' => 'success', 'data' => $data);
            return response()->json($response_data,200); 
        }
        else
        {
            $response_data = array('status' =>'error','data' => 'No rows found please check user id');
            return response()->json($response_data,300); 
        }
    }

    public function getHolidayList(Request $request,$year,$month)
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
              
        $holidayList = PlannerHolidayMaster::select('Name','Date')
                       ->whereBetween('Date',array($startDateMonth,$endDateMonth))
                       //->where('status', ture)
                       ->get();
        $holidayListData = [];
        $i =0;               
            foreach($holidayList as $holidayData)
            {
                $holidayListData[$i]['Name'] = $holidayData['Name'];
                $holidayListData[$i]['Date'] = (array)$holidayData['Date'];
                $i = $i+1;
            }               
                     

         //print_r($holidayListData);
         //exit;                             

        if($holidayList)
             {
                $response_data = array('status'=>200,'data' => $holidayListData,'message'=>"success");
                return response()->json($response_data,200); 
            }
            else
            {
                $response_data = array('status' =>300,'data' => 'No rows found please check user id','message'=>"error");
                return response()->json($response_data,300); 
            }

    }
	
	
	
	public function sevaConsentForm(Request $request){
		return view('sevaConsentForm');
	}
	
	public function savesevaConsentForm(Request $request){ 
		DB::setDefaultConnection('mongodb');  
		
	
		$name = $request->input('name');
		$mobile_no = $request->input('mobile_no');
		$city = $request->input('city');
		$personal_id = $request->input('personal_id');
		$registration_no = $request->input('registration_no');
		
		$sevaContent = new SevaContents();
	    $sevaContent['name'] = $name;
	    $sevaContent['mobile_no'] = $mobile_no;
	    $sevaContent['city'] = $city;
	    $sevaContent['personal_id'] = $personal_id;
	    $sevaContent['registration_no'] = $registration_no;
		$sevaContent->save();
		if(isset($sevaContent->id)){
		$msg = "Form submitted successfully";
		return $msg;
		}else{
		$msg = "Something went wrong...";
		return $msg;	
		}
		
	}
	
	public function sevaConsentlist(Request $request)
	{
	   DB::setDefaultConnection('mongodb');
       $SevaContents = SevaContents::get();       
       return view('sevaConsentDetailsTable',compact('SevaContents'));
	}
	
	public function sevaConsentDetailsList(Request $request){

        

    }
	
}
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
use App\RoleConfig;
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
			$message['function'] = "contentDashboard";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
            // $all_user=User::select('role_id')->where('approve_status','pending')->get();
            $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => '403', 'message' => 'error', 'data' => 'User does not belong to any Organization.'], 403);
            } 
            $content = ContentManagement::select('name','url','category_id')
                           // ->with('ContentCategories')
                            ->get()

                            ->groupBy('category_id') ;

             $contentCategory = ContentCategories::get();
            
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
            $response_data = array('status' =>'300','data' => 'No rows found please check user id');
            return response()->json($response_data,200); 
        } 
    }

    public function getHolidayList(Request $request,$year,$month)
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
			$message['function'] = "getHolidayList";
			$this->logData($this->logerrorPath ,$message,'Error');
			$response_data = array('status' =>'404','message'=>$message);
			return response()->json($response_data,200); 
			// return $message;
		}
        // $all_user=User::select('role_id')->where('approve_status','pending')->get();
        $database = $this->connectTenantDatabase($request,$org_id);
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
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\Organisation;
use App\State;
use App\StateJurisdiction;
use App\Jurisdiction;
use App\District;
use App\Taluka;
use App\Cluster;
use App\Village;
use App\Project;
use App\Location;
use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class LocationController extends Controller
{
    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getstates(Request $request){
  
        $states=State::with('jurisdictions')->select('Name')->get();
        $response_data = [];
        foreach($states as $state){
            
            foreach($state->jurisdictions as $jurisdiction){
                
                $jurisdiction_instance = Jurisdiction::find($jurisdiction->jurisdiction_id);
                $jurisdiction->levelName = $jurisdiction_instance->levelName;
            }
            $response_data[] = $state;
        }
        $response_data = array('status' =>'success','data' => $response_data,'message'=>'');
        return response()->json($response_data); 
    }

    public function getleveldata($state_id,$level,Request $request){
        $jurisdiction = StateJurisdiction::where([['state_id',$state_id],['level',(int)$level]])->get()->first();
        $data = [];
        if($jurisdiction){
            $jurisdiction_instance = Jurisdiction::where('_id',$jurisdiction->jurisdiction_id)->get()->first();
            switch($jurisdiction_instance->levelName){
                case 'District':
                $list = District::where('state_id',$state_id)->get();
                break;
                case 'Taluka':
                $list = Taluka::where('state_id',$state_id)->get();
                break;
                case 'Cluster':
                $list = Cluster::where('state_id',$state_id)->get();
                break;
                case 'Village':
                $list = Village::where('state_id',$state_id)->get();
                break;
                default:
                $list = [];

            }
            $data['levelName'] = $jurisdiction_instance->levelName;
            $data['list'] = $list;
            $response_data = array('status' =>'success','data' => $data,'message'=>'');
            return response()->json($response_data); 
        }else{
            return response()->json([],404); 
        }
        
    }

    public function getLocations()
    {
        // Obtaining all details of the logged-in user
        $user = $this->request->user();
        
        // When given an array, the has method will determine if all of the 
        // specified values are present on the request
        if (!$this->request->has(['jurisdictionTypeId', 'projectId'])) 
        {         
            $role = Role::where('_id',$user->role_id)->get();
        }

        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database);   

        $status = "success";

        // 'filled' method determines if a value is present on the request and is not empty
        if($this->request->filled('jurisdictionTypeId'))
        {
            // 'input' method obtains the value
            $locations = Location::where('jurisdiction_type_id',$this->request->input('jurisdictionTypeId'))->get();

            if($locations->isEmpty())
                $status = "error";
        }
        elseif($this->request->filled('projectId'))
        {
            $project = Project::find($this->request->input('projectId'));

            if( isset($project) )
                $locations = Location::where('jurisdiction_type_id',$project->jurisdiction_type_id)->get();
            else
                $status = "error";      
                
        }
        else
        {
            $locations = Location::where('jurisdiction_type_id',$role[0]->jurisdiction_type_id)->get();
        }
        
            if($status === "success")
            {
                foreach($locations as $location)
                {
                    $location->level = json_decode($location->level,true);
                }
                return response()->json(['status'=>'success','data'=>$locations,'message'=>'']); 
            }
            else
                return response()->json(['status'=>'error','data'=>null,'message'=>'Invalid data entered'],404); 
    }
}
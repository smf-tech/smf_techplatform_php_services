<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\Organisation;
use App\State;
use App\StateJurisdiction;
use App\Jurisdiction;
use App\JurisdictionType;
use App\District;
use App\Taluka;
use App\Cluster;
use App\Village;
use App\Project;
use App\Location;
use App\Role;
use App\RoleConfig;
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

    public function getLevelData(Request $request, $orgId, $jurisdictionTypeId, $jurisdictionLevel)
    {
        $database = $this->setDatabaseConfig($request, $orgId);
        DB::setDefaultConnection($database);
        $jurisdictionType = JurisdictionType::find($jurisdictionTypeId);
        $levels = [];
        if($jurisdictionType !== null){
            $jurisdictions = $jurisdictionType->jurisdictions;
            foreach ($jurisdictions as $jurisdisction) {
                $levels[$jurisdisction] = DB::table($jurisdisction)->get();
                if ($jurisdisction == $jurisdictionLevel) {
                    break;
                }
            }

            $response_data = array('status' =>'success','data' => $levels,'message'=>'');
            return response()->json($response_data); 
        }else{
            return response()->json([],404); 
        }
        
    }

    public function getLocations()
    {
        // Obtaining all details of the logged-in user
        $user = $this->request->user();
        
        
        // $role = Role::find($user->role_id);

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
            // $locations = Location::where('jurisdiction_type_id',$role[0]->jurisdiction_type_id)->get();

            if(isset($user->role_id)) {
                $roleConfig = RoleConfig::where('role_id',$user->role_id)->first();
                $jurisdiction = Jurisdiction::where('_id',$roleConfig->level)->pluck('levelName');
                $jurisdiction[0] = strtolower($jurisdiction[0]);

                $jurisdictions = JurisdictionType::where('_id',$roleConfig->jurisdiction_type_id)->pluck('jurisdictions');
                $jurisdictions[0] = array_map('strtolower', $jurisdictions[0]);

                if($this->request->filled('locations')) {
                    $location = $this->request->input('locations');
                    $data = Location::where('jurisdiction_type_id',$roleConfig->jurisdiction_type_id)->whereIn($jurisdiction[0], $location[$jurisdiction[0]])->get($jurisdictions[0]);
                } else {
                    $data = Location::where('jurisdiction_type_id',$roleConfig->jurisdiction_type_id)->get($jurisdictions[0]);
                }

                return response()->json(['status'=>'success','data'=>$data,'message'=>''],200); 
            }

            return response()->json(['status'=>'error','data'=>'','message'=>'You Do Not Have A Role In The Organisation'],403);                 
        }
        
            if($status === "success")
            {
                foreach($locations as $location)
                {
                    $location->level = json_decode($location->level,true);
                }
                return response()->json(['status'=>'success','data'=>$locations,'message'=>''],200); 
            }
                return response()->json(['status'=>'error','data'=>'','message'=>'Invalid data entered'],404); 
    }
}
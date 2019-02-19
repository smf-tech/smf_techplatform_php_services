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

    protected static $condition = '';

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
            foreach ($jurisdictions as $jurisdiction) {
                $levels[] = strtolower($jurisdiction);
                if ($jurisdiction == $jurisdictionLevel) {
                    break;
                }
            }
            $queryBuilder = Location::where('jurisdiction_type_id', $jurisdictionTypeId);
            $fields = [];
            foreach ($levels as $level) {
                $queryBuilder->with($level);
                $fields[] = $level . '_id';
            }
            $locations = $queryBuilder->get($fields);
            $data = $locations->filter(function(&$value, $key) use ($jurisdictionLevel) {
                if ($value[strtolower($jurisdictionLevel) . '_id'] != self::$condition) {
                    self::$condition = $value[strtolower($jurisdictionLevel) . '_id'];
                    return true;
                }
            })->values();
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
        $userLocation = $user->location;
        
        
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
                
        } else {
            if(isset($user->role_id)) {
                $roleConfig = RoleConfig::where('role_id',$user->role_id)->first();

                $jurisdiction = Jurisdiction::where('_id',$roleConfig->level)->pluck('levelName');
                $level = strtolower($jurisdiction[0]);

                $jurisdictions = JurisdictionType::where('_id',$roleConfig->jurisdiction_type_id)->pluck('jurisdictions')[0];

                if($userLocation !== null && isset($userLocation[$level]) && !empty($userLocation[$level])) {
                    $locations = Location::where('jurisdiction_type_id',$roleConfig->jurisdiction_type_id);
                    foreach ($jurisdictions as $singleLevel) {
                        if (isset($userLocation[strtolower($singleLevel)])) {
                            $locations->whereIn(strtolower($singleLevel) . '_id', $userLocation[strtolower($singleLevel)]);
                        }
                    }
                    $data = $locations->with('state', 'district', 'taluka', 'village')->get();
                } else {
                    $data = Location::where('jurisdiction_type_id',$roleConfig->jurisdiction_type_id)->with('state', 'district', 'taluka', 'village')->get();
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
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

    public function getDistricts()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $districts = District::all();

        if($districts->count() === 0) {
            return response()->json([
            'status' => 'success',
            'data' => '',
            'message' => 'No districts present'
            ],200);
        }

        return response()->json([
            'status' => 'success',
            'data' => $districts,
            'message' => 'Getting a list of all Districts'
        ],200);
    }

    public function getTalukas()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        } 

        $talukas = Taluka::all();

        if($talukas->count() === 0) {
            return response()->json([
            'status' => 'success',
            'data' => '',
            'message' => 'No talukas present'
            ],200);
        }

        return response()->json([
            'status' => 'success',
            'data' => $talukas,
            'message' => 'Getting a list of all Talukas'
        ],200);
    }

    public function getVillages()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $villages = Village::all();

        if($villages->count() === 0) {
            return response()->json([
            'status' => 'success',
            'data' => '',
            'message' => 'No villages present'
            ],200);
        }

        return response()->json([
            'status' => 'success',
            'data' => $villages,
            'message' => 'Getting a list of all Villages'
        ],200);
    }

    public function getClusters()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $clusters = Cluster::all();

        if($clusters->count() === 0) {
            return response()->json([
            'status' => 'success',
            'data' => '',
            'message' => 'No clusters present'
            ],200);
        }

        return response()->json([
                'status' => 'success',
                'data' => $clusters,
                'message' => 'Getting a list of all Clusters'
            ],200);
    }
    public function getLevelData(Request $request, $orgId, $jurisdictionTypeId, $jurisdictionLevel)
    {
        $database = $this->connectTenantDatabase($request, $orgId);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
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
                    unset($value['_id']);
                    self::$condition = $value[strtolower($jurisdictionLevel) . '_id'];
                    return true;
                }
            })->values()->all();
            $response_data = array('status' =>'success','data' => array_values(array_unique($data)),'message'=>'');
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

        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

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
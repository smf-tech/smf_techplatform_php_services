<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Organisation;
use App\Role;
// use Maklad\Permission\Models\Role;
use Maklad\Permission\Models\Permission;
use App\RoleConfig;
use App\Module;
use App\Jurisdiction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\JurisdictionType;
use App\Project;

class RoleController extends Controller
{   
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
	public function __construct(Request $request) 
	{
		$this->request = $request;
		$this->logInfoPath = "logs/Profile/DB/logs_".date('Y-m-d').'.log';
		$this->logerrorPath = "logs/Profile/ERROR/logs_".date('Y-m-d').'.log';
	} 
	 
	 
    public function getOrgRoles(Request $request, $org_id, $project_id)
    {
        // Obtaining all the roles of an organisation from the main database 
		$details = [
		'org_id'=>$org_id,
		'project_id'=>$project_id
		];
		 $this->logData($this->logInfoPath ,$details,'DB'); 
		
		$data=array(); 
		// $input = explode(',',$org_id);

		// $header = getallheaders();
		// $org_id =  $header['orgId'];
		// $project_id =  $header['projectId'];
		
		// foreach($input as $row)
		// { 
			DB::setDefaultConnection('mongodb');
			$roles=Role::where('project_id', $project_id)
			->where('platform', 'app')
			->where('org_id', $org_id)
			->where('is_deleted',0)
			->where('role_code','!=','113')
			->get(['display_name','project_id']);
				  
			if(!$roles->isEmpty() && $roles !== null)
			{
				$database = $this->connectTenantDatabase($request,$org_id);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}

				// For each role we obtain jurisdiction details & project details from the resp. collections
				// in the tenant database
				// echo json_encode($roles);die();
			foreach($roles as $role)
			{
					// Get Jurisdiction level
				$roleConfig = RoleConfig::where('role_id', $role->id)->first();
				$jurisdictionLevel = Jurisdiction::find($roleConfig['level']);
				unset($jurisdictionLevel['created_by'], $jurisdictionLevel['created_at'], $jurisdictionLevel['updated_at']);
				$role->jurisdictionLevel = $jurisdictionLevel;

				$project = Project::find($role->project_id);
					// echo json_encode($role->project_id);exit; 
					// Adding element project to the role object
				$jurisdictionType = JurisdictionType::find($project['jurisdiction_type_id']);
				$levels = [];
			
				foreach ($jurisdictionType->jurisdictions as $level) {
					$jurisdiction = Jurisdiction::where(['levelName' => $level])->first();
					unset($jurisdiction['created_by'], $jurisdiction['created_at'], $jurisdiction['updated_at']);
					$levels[] = $jurisdiction;
					if ($jurisdictionLevel['levelName'] === $level) {
						break;
					}
				}
				 
				$project['jurisdictions'] = $levels;
				 
				unset($project['created_at'], $project['updated_at']);
				$role->project = $project;
					// Removing element project_id from the role object
				unset($role->project_id);
				array_push($data,$role);
				
			}
			 
			}
			 
		// }
		 
		if(count($data) > 0){
			$response_data = array('status' =>'success','data' => $data,'message'=>'');
			return response()->json($response_data,200);
		}
		else
			{
				return response()->json(['status'=>'error','message'=>'Invalid organisation ID entered'],200); 
			}
    }

    public function getroleconfig($org_id,$role_id,Request $request){
        
        $user = $request->user();
        $org = Organisation::find($org_id);
        if($org){
            /*$dbName = $org->name.'_'.$org_id;
            \Illuminate\Support\Facades\Config::set('database.connections.'.$dbName, array(
                'driver'    => 'mongodb',
                'host'      => '127.0.0.1',
                'database'  => $dbName,
                'username'  => '',
                'password'  => '',  
            ));
            DB::setDefaultConnection($dbName); */
            $database = $this->connectTenantDatabase($request,$org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }

            $role_config=RoleConfig::where('role_id', $role_id)->get()->first();
            $default_modules = $on_approve_modules = [];
            if($role_config){
                $default_modules = $this->getmodules($role_config->default_modules);
                $on_approve_modules = $this->getmodules($role_config->on_approve_modules);
            }
            $data = ['default_modules'=>$default_modules,'on_approve_modules'=>$on_approve_modules];
            $response_data = array('status' =>200,'data' => $data,'message'=>'','user_approve_status'=>$user->approve_status);
            return response()->json($response_data,200); 
        }else{
            return response()->json([],404);
        }     


    }

    public function getmodules($module_ids){
    	DB::setDefaultConnection('mongodb');
        $modules =  Module::whereIn('_id', $module_ids)->where('is_active',1)->get();

        return $modules;
    }
}
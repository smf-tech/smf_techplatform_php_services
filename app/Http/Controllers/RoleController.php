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
    public function getOrgRoles(Request $request, $org_id)
    {
        // Obtaining all the roles of an organisation from the main database 
        $roles=Role::where('org_id', $org_id)
        ->get(['display_name','project_id']);

        if(!$roles->isEmpty() && $roles !== null)
        {
            $database = $this->connectTenantDatabase($request,$org_id);

            // For each role we obtain jurisdiction details & project details from the resp. collections
            // in the tenant database
        foreach($roles as $role)
        {
                // Get Jurisdiction level
            $roleConfig = RoleConfig::where('role_id', $role->id)->first();
            $jurisdictionLevel = Jurisdiction::find($roleConfig->level);
            unset($jurisdictionLevel['created_by'], $jurisdictionLevel['created_at'], $jurisdictionLevel['updated_at']);
            $role->jurisdictionLevel = $jurisdictionLevel;

            $project = Project::find($role->project_id);

                // Adding element project to the role object
            $jurisdictionType = JurisdictionType::find($project->jurisdiction_type_id);
            $levels = [];
            foreach ($jurisdictionType->jurisdictions as $level) {
                $jurisdiction = Jurisdiction::where(['levelName' => $level])->first();
                unset($jurisdiction['created_by'], $jurisdiction['created_at'], $jurisdiction['updated_at']);
                $levels[] = $jurisdiction;
                if ($jurisdictionLevel->levelName === $level) {
                    break;
                }
            }
            $project->jurisdictions = $levels;
            unset($project['created_at'], $project['updated_at']);
            $role->project = $project;
                // Removing element project_id from the role object
            unset($role->project_id);
        }

        $response_data = array('status' =>'success','data' => $roles,'message'=>'');
        return response()->json($response_data);
        }
        else
        {
            return response()->json(['status'=>'error','data'=>null,'message'=>'Invalid organisation ID entered'],404); 
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
            $databaseName = $this->connectTenantDatabase($request,$org_id);

            $role_config=RoleConfig::where('role_id', $role_id)->get()->first();
            $default_modules = $on_approve_modules = [];
            if($role_config){
                $default_modules = $this->getmodules($role_config->default_modules);
                $on_approve_modules = $this->getmodules($role_config->on_approve_modules);
            }
            $data = ['default_modules'=>$default_modules,'on_approve_modules'=>$on_approve_modules];
            $response_data = array('status' =>'success','data' => $data,'message'=>'','user_approve_status'=>$user->approve_status);
            return response()->json($response_data,200); 
        }else{
            return response()->json([],404);
        }     


    }

    public function getmodules($module_ids){
        $modules =  Module::whereIn('_id', $module_ids)->get();
        return $modules;
    }
}
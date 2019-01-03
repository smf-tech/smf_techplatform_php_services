<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Organisation;

use Maklad\Permission\Models\Role;
use Maklad\Permission\Models\Permission;
use App\RoleConfig;
use App\Module;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
   
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getorgroles($org_id,Request $request){
        $roles=Role::where('org_id', $org_id)->get(['name', 'display_name','org_id','jurisdiction']);
        $response_data = array('status' =>'success','data' => $roles,'message'=>'');
        return response()->json($response_data); 
    }

    public function getroleconfig($org_id,$role_id,Request $request){
        $user = $request->user();
        $org = Organisation::find($org_id);
        if($org){
            $dbName = $org->name.'_'.$org_id;
            \Illuminate\Support\Facades\Config::set('database.connections.'.$dbName, array(
                'driver'    => 'mongodb',
                'host'      => '127.0.0.1',
                'database'  => $dbName,
                'username'  => '',
                'password'  => '',  
            ));
            DB::setDefaultConnection($dbName); 
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
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Organisation;
use App\Project;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;

class OrganisationController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function show()
    {
        $organisations = Organisation::all();
        return $organisations;
    }

    public function getOrganisation($org_id)
    {
        $organisation = Organisation::where('id',$org_id)->get();
        return $organisation;
    }

    public function listorgs(){
        $organisations = Organisation::where('orgshow','<>',0)->get();
        $response_data = array('status' =>'success','data' => $organisations,'message'=>'');
        return response()->json($response_data);      
    }

    public function getorgprojects($org_id){
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
            
            $projects = Project::get(['name']); 
            $response_data = array('status' =>'success','data' => $projects,'message'=>'');
            return response()->json($response_data,200); 
        }else{
            return response()->json(['status'=>'error', 'data'=>'', 'message'=>'Invalid Organisation Id'],404);
        }     
    }
}

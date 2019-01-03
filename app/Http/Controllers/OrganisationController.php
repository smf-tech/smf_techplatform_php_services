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

    public function getSurveys()
    {
	
        $user = $this->request->user();
      
        $organisation = Organisation::where('_id',$user->org_id)->get();
        
        $database = strtolower($organisation[0]->name).'_'.$user->org_id; 

        \Illuminate\Support\Facades\Config::set('database.connections.'.$database, array(
            'driver'    => 'mongodb',
            'host'      => '127.0.0.1',
            'database'  => $database,
            'username'  => '',
            'password'  => '',  
        )); 

        DB::setDefaultConnection($database); 
        $data = DB::collection('surveys')->select('_id','name')->get();
		//var_dump(sizeof($data));exit;
        //DB::disconnect($database);

        //DB::connection('mongodb');

        return response()->json($data);
    }


    public function getSurveyDetails($survey_id)
    {
        $user = $this->request->user();
      
        $organisation = Organisation::where('_id',$user->org_id)->get();
        
        $database = strtolower($organisation[0]->name).'_'.$user->org_id; 

        \Illuminate\Support\Facades\Config::set('database.connections.'.$database, array(
            'driver'    => 'mongodb',
            'host'      => '127.0.0.1',
            'database'  => $database,
            'username'  => '',
            'password'  => '',  
        )); 
        DB::setDefaultConnection($database); 
        $data = DB::collection('surveys')->select('name','json')->where('_id',$survey_id)->get();
		//var_dump($data);exit;
        //DB::disconnect($database);

        //DB::connection('mysql');

        return response()->json($data);
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
            return response()->json([],404);
        }     
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Organisation;
use App\Project;
use App\Associate;
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

    public function listOrgs()
    {
        $organisations = Organisation::where('orgshow','<>',0)->get();

        foreach ($organisations as &$organisation) {
            $organisation['type'] = 'organisation';
            $organisation['associateOrgId'] = $organisation->id;

            DB::setDefaultConnection('mongodb');
            $databaseName = $this->connectTenantDatabase($this->request, $organisation->id);
            if (empty($databaseName)) {
                continue;
            }

            $associates = Associate::all();

            if ($associates->count()) {
                foreach ($associates as $associate) {
                    $organisations[] = [
                        '_id' => $associate->id,
                        'name' => $associate->name,
                        'service' => '',
                        'type' => $associate->type,
                        'associateOrgId' => $organisation->id
                    ];
                }
            }
        }
        $response_data = array('status' =>'success','data' => $organisations,'message'=>'');
        return response()->json($response_data);
    }

    public function getorgprojects($org_id){
        $org = Organisation::find($org_id);
        if($org){
            //$dbName = $org->name.'_'.$org_id;
            $databaseName = $this->connectTenantDatabase($this->request,$org_id);
            /*\Illuminate\Support\Facades\Config::set('database.connections.'.$dbName, array(
                'driver'    => 'mongodb',
                'host'      => '127.0.0.1',
                'database'  => $dbName,
                'username'  => '',
                'password'  => '',  
            ));
            DB::setDefaultConnection($dbName);*/ 
            
            $projects = Project::get(['name']); 
            $response_data = array('status' =>'success','data' => $projects,'message'=>'');
            return response()->json($response_data,200); 
        }else{
            return response()->json(['status'=>'error', 'data'=>'', 'message'=>'Invalid Organisation Id'],404);
        }     
    }
}

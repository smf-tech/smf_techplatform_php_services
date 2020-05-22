<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Organisation; 
use App\User;
use App\AppConfig;
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
        $organisations = Organisation::where('orgshow','<>',0)->where('is_deleted',0)->get();
		
        foreach ($organisations as &$organisation) { 
            $organisation['type'] = 'organisation';
            $organisation['associateOrgId'] = $organisation->id;
            $organisation['name'] = strtoupper($organisation['name']);

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
        $response_data = array('status' =>200,'data' => $organisations,'message'=>'success');
        return response()->json($response_data);
    }

    public function getorgprojects($org_id){
        
        $org = Organisation::find($org_id);

        if($org){
            $databaseName = $this->connectTenantDatabase($this->request,$org_id);
            if ($databaseName === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }

            $projects = Project::where('is_active',1)->select('name')->get(); 
            $response_data = array('status' =>'success','data' => $projects,'message'=>'');
            return response()->json($response_data,200); 
        }else{
            return response()->json(['status'=>'error', 'data'=>'', 'message'=>'Invalid Organisation Id'],404);
        }     
    }
	
	//api for configuration
	public function configuration(Request $request)
	{ 
			$appConfig = AppConfig::select('appUpdate')->first();  
			if($appConfig){
				$response_data = array('code' =>200, 'status' =>'200','message'=>'success','data'=>$appConfig);

              $header = getallheaders();
              if(isset($header['orgId']) && ($header['orgId']!='') 
                && isset($header['projectId']) && ($header['projectId']!='')
                && isset($header['roleId']) && ($header['roleId']!='')
                && isset($header['versionName']) && ($header['versionName']!='')
				&& isset($header['Authorization']) && ($header['Authorization']!='')
                )
              { 
                $org_id =  $header['versionName'];
                $project_id =  $header['projectId'];
                $role_id =  $header['roleId'];
                $versionName =  $header['versionName'];

                
                $user = $this->request->user(); 
				 
                if($user !=null)
                {
					 
                    $userData = User::find($user->_id); 
                    if($userData){
                    $userData->octopusAppVersion = $versionName;
                    $userData->save();
                    }
                }
                
                
              }  

				$response_data = array('code' =>200, 'status' =>'200','message'=>'success','data'=>$appConfig);
                return response()->json($response_data,200);
            }else{
                $response_data = array('code' =>403, 'status' =>'403','message'=>'Invalid request');
                return response()->json($response_data,200);
            }
    }
}

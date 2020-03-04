<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use App\Report;

class ReportController extends Controller
{
    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
         $this->logInfoPath = "logs/Reports/DB/logs_".date('Y-m-d').'.log';
        $this->logerrorPath = "logs/Reports/ERROR/logs_".date('Y-m-d').'.log';
    }

    /**
     * 
     * @param string $id
     */
    public function index($id = null)
    {
        $header = getallheaders();
          if(isset($header['orgId']) && ($header['orgId']!='') 
            && isset($header['projectId']) && ($header['projectId']!='')
            && isset($header['roleId']) && ($header['roleId']!='')
            )
          { 
            $org_id =  $header['orgId'];
            $project_id =  $header['projectId'];
            $role_id =  $header['roleId'];
          }else{

            
            $message['message'] = "insufficent header info";
            $message['function'] = 'index'; 
            $this->logData($this->logerrorPath ,$message,'Error');
            $response_data = array('status' =>'404','message'=>$message);
            return response()->json($response_data,200); 
            // return $message;
          }

        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);


        }
        if($id === null){
            $message['data'] = $id; 
            $message['function'] = 'index'; 
            $this->logData($this->logerrorPath ,$message,'Error');
        }
        if ($id !== null) {
            try {
                return response()->json(
                    [
                        'status' => 'success',
                        'data' => Report::where('_id', $id)
                                        ->where('org_id',$org_id)
                                        ->where('project_id',$project_id)    
                                        ->with('category')->first(),
                        'message' => 'Report found with id ' . $id
                    ],
                    200
                );
            } catch(\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => null,
                        'message' => $exception->getMessage()
                    ],
                    404
                );
            }
        }

        return response()->json(
            [
                'status' => 'success',
                'data' => Report::with('category')->get(),
                'message' => 'Jurisdiction Type list'
            ],
            200
        );
    }

}

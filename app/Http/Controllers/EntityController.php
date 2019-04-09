<?php

namespace App\Http\Controllers;

use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Entity;
//use App\Survey;
use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EntityController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function createEntityInfo($entity)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $record = [];
        $data = $this->request->all();
        $username = $this->request->user()->id;
        $updatedDateTime = Carbon::now()->getTimestamp();
        $createdDateTime = Carbon::now()->getTimestamp();
        
            $id = DB::table($entity)->insertGetId([
                'username'=>$username,
                'response'=>json_encode($data['response']),
                'updatedDateTime'=>$updatedDateTime,
                'createdDateTime'=>$createdDateTime,
                'survey_id'=>$data['survey_id'],
                'isDeleted'=> false]);

        $record['_id'] = $id;
        $record['response'] = $data['response'];
        $record['username'] = $username;
        $record['survey_id'] = $data['survey_id'];
        $record['updatedDateTime'] = $updatedDateTime;
        $record['createdDateTime'] = $createdDateTime;
        $record['isDeleted'] = false;
        
        return response()->json(['status'=>'success','data'=>$record,'message'=>'Data Inserted Sucessfully'],200);
    }

    public function getEntityInfo($entity,$column)
    {
        if (!$this->request->filled('value')) {
            return response()->json(
                    [
                    'status' => 'error',
                    'data' => null,
                    'message' => 'Value parameter is missing'],
                400);
        }
        $validColumnNames = ['updatedDateTime','createdDateTime'];
        if (!in_array($column,$validColumnNames)) {
            return response()->json(
                [
                'status' => 'error',
                'data' => null,
                'message' => 'Please enter valid column name'],
            400);     
        }
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'Entity does not belong to any Organization.'], 403);
        }
        $responseData = [];
        $filterVal = $this->request->input('value');
        if ($filterVal == 'max') {
            $responseData = DB::table($entity)->orderBy($column, 'desc')->first();
            $responseData['response'] = json_decode($responseData['response']);
            

        } elseif ($filterVal == 'min') {
            $responseData = DB::table($entity)->orderBy($column, 'asc')->first();
            $responseData['response'] = json_decode($responseData['response']);
            

        }
        
        return response()->json(['status'=>'success','data'=>$responseData ,'message'=>''],200);
    }

    public function updateEntityInfo($recordId, $entity)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $username = $this->request->user()->id;
        $data = $this->request->all();
        //echo 'see entity='.$entity;
        //echo 'see record='.$recordId;
        //exit;
        $entityRec = DB::table($entity)->where('_id', $recordId)->first();
       
        if(empty($entityRec)) {

            return response()->json(
                [
                    'status' => 'error',
                    'data' => '',
                    'message' => "Record does not exist"
                ],
                404
            );

        } else {

            if($entityRec['isDeleted'] === true) {
				return response()->json([
					'status' => 'error',
					'data' => '',
					'message' => 'Entity cannot be updated as the record has been deleted!'
                ],404);
                
			} else {

                $updateRecord = [];
                $result = [];
                
                $updateRecord['response'] = $this->request->has('response') ? json_encode($this->request->input('response')):$entityRec['response'];
                $updateRecord['survey_id'] = $this->request->has('survey_id') ? $this->request->input('survey_id'):$entityRec['survey_id'];
                $updateRecord['updatedDateTime'] = Carbon::now()->getTimestamp();
                
                DB::table($entity)->where('_id', $recordId)->update($updateRecord);

                $result = [
					'_id' => ['$oid' => $recordId],
                    'response' => json_decode($updateRecord['response']),
                    'username' => $username,
                    'survey_id' => stripslashes($updateRecord['survey_id']),
                    'updatedDateTime' => $updateRecord['updatedDateTime'],
                    'createdDateTime' => $entityRec['createdDateTime'],
                ];
                
                return response()->json(['status'=>'success','data'=>$result,'message'=>'Record Updated Sucessfully'],200);
            }
        }
    }

    public function deleteEntityInfo($recordId, $entity)
    {
        try {

            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
    
            $entityRec = DB::table($entity)->where('_id', $recordId)->first();
       
            if(empty($entityRec)) {

                return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => "Record does not exist"
                    ],
                    404
                );

            } else {

                DB::table($entity)
                    ->where('_id', $recordId)
                    ->update(['isDeleted' => true]);
        
                return response()->json(
                    [
                        'status' => 'success',
                        'data' => '',
                        'message' => "Record deleted successfully"
                    ],
                    200
                );
            }
            
        } catch(\Exception $exception) {
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => $exception->getMessage()
                    ],
                    404
                );
            }
    }
    

}

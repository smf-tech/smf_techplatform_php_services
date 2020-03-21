<?php

namespace App\Http\Controllers;

use App\Survey;
use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\SurveyResult;
use App\Entity;
use App\Microservice;
use App\Category;
use App\StructureTracking;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Validator;
use Illuminate\Support\Facades\Input;
use \DateTime;
use App\RoleConfig;
use App\ApprovalLog;
use App\ApprovalsPending;

class SurveyController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->logInfoPath = "logs/survey_form_data/DB/logs_".date('Y-m-d').'.log';
    }


    public function updateSurvey($survey_id,$responseId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        $survey = Survey::find($survey_id);
        $primaryKeys = $survey->form_keys;

        $fields = array();
        
        $fields['userName']=$user->id;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }        

        // Gives current date and time in the format :  2019-01-24 10:30:46
        $date = Carbon::now();
        
        $fields['updatedDateTime'] = $date->getTimestamp();

        // Selecting the collection to use depending on whether the survey has an entity_id or not
        $collection_name = isset($survey->entity_id)?'entity_'.$survey->entity_id:'survey_results';

        $formExists = DB::collection($collection_name)->where(function($q) use ($survey_id){
            $q->where('form_id','=',$survey_id)
              ->orWhere('survey_id','=',$survey_id);
        })
                            ->where('userName','=',$user->id)
                            ->where(function($q) use ($primaryValues)
                            {
                                foreach($primaryValues as $key => $value)
            {
                $q->where($key, '=', $value);
            }
        })
        ->where('_id','!=',$responseId)
        ->get()->first();

        if (!empty($formExists)) {
            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Update Failure!!! Entry already exists with the same values.'],400);
        }
       

        $user_submitted = DB::collection($collection_name)
                            ->where('_id',$responseId)
                            ->where(function($q) use ($survey_id){
                                $q->where('form_id','=',$survey_id)
                                  ->orWhere('survey_id','=',$survey_id);
                            })
                            ->where('userName','=',$user->id);

        if($user_submitted->first()['isDeleted'] === true) {
            return response()->json([
                'status' => 'error',
                'data' => '',
                'message' => 'Response cannot be updated as it has been deleted!'
            ]);
        }

        // Function defined below, it queries the collection $collection_name using the parameters
        if(!isset($survey->entity_id)) {
            
            $fields['form_id']=$survey_id;
            // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
            
            if(isset($user_submitted->first()['submit_count'])) {

                $fields['submit_count']= $user_submitted->first()['submit_count']+1;   
            } 
            
            $user_submitted->update($fields);
            $data['form_title'] = $this->generateFormTitle($survey_id,$responseId,'survey_results');
        } else {

            $fields['survey_id']=$survey_id;

            $user_submitted->update($fields);
                            
            $data['form_title'] = $this->generateFormTitle($survey_id,$responseId,'entity_'.$survey->entity_id);
        }

        $data['_id']['$oid'] = $responseId;
        $data['createdDateTime'] = $user_submitted->first()['createdDateTime'];
        $data['updatedDateTime'] = $fields['updatedDateTime'];

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }

    public function getSurveys()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        // Obtaining '_id','name','active','editable','multiple_entry','category_id','microservice_id','project_id','entity_id','assigned_roles' of Surveys
        // alongwith corresponding details of 'microservice','project','category','entity'
        $data = Survey::select('_id','name','active','approve_required','editable','multiple_entry','category_id','entity_collection_name','api_url','project_id','assigned_roles','created_at', 'entity_collection_name')
        ->with('project','category')
       // ->where('assigned_roles','=',$user->role_id)
		->orderBy('created_at')->get();
        
       // echo json_encode($data);
        //die;   
        foreach($data as $row)
        {
            // unset() removes the element from the 'row' object
            unset($row->category_id); 
            unset($row->project_id);
            unset($row->entity_id);
            unset($row->assigned_roles);

           /*  if (is_object($row['microservice'])) {
                $microService = clone $row['microservice'];
                $microService->route = $microService->route . '/' . $row->id;
                unset($row['microservice']);
                $row['microservice'] = $microService;
            } */
        }

        return response()->json(['status'=>'success','data' => $data,'message'=>'']);
    }


    public function getSurveyDetails($survey_id)
    {
         $user = $this->request->user();
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
 
        // Obtaining '_id','name','json', active','editable','multiple_entry','category_id','microservice_id','project_id','entity_id','assigned_roles','form_keys' of a Survey
        // alongwith corresponding details of 'microservice','project','category','entity'
        $entity_id = Survey::where('_id',$survey_id)->select('entity_id')->get();
        $data = Survey:: with('project')
		//->with('microservice')
        ->with('category')
        //->with('entity')        
        ->select('category_id','project_id','entity_id','assigned_roles','_id','name','json','active','approve_required','editable','multiple_entry','form_keys','api_url','entity_collection_name','location_required','location_required_level')
        ->find($survey_id); 
        

        $jurisdictions = \App\JurisdictionType::
                            select('jurisdictions')
                            ->where('project_id',$data->project_id)->first();


        if($jurisdictions && !empty($jurisdictions['jurisdictions']))
            {                       
                $data['jurisdictions'] = $jurisdictions['jurisdictions'];
            }
        //array_merge($data,$jurisdictions);
        unset($data->category_id);
        unset($data->microservice_id);
        unset($data->project_id);
        unset($data->entity_id);
        //var_dump($data);
        //die();
       /*  if (isset($data['microservice'])) {
            $data['microservice']->route = $data['microservice']->route . '/' . $survey_id;
        } */
        
        // json_decode function takes a JSON string and converts it into a PHP variable
        $data->json = json_decode($data->json,true);
        return response()->json(['status'=>'success','data' => $data,'message'=>'']);
    }

    public function createResponse($survey_id)
    {
        $header = getallheaders();
        //$user = $this->request->user();
        if(isset($header['orgId']) && ($header['orgId']!='') 
            && isset($header['projectId']) && ($header['projectId']!='')
            && isset($header['roleId']) && ($header['roleId']!='')
        )
        { 
            $org_id =  $header['orgId'];
            $project_id =  $header['projectId'];
            $role_id =  $header['roleId'];
        }

        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();
        $userLocation = $this->request->user()->location;  
        
        $userRole = $this->request->user()->role_id;  
        $userRoleLocation = ['role_id' => $userRole];
        $userRoleLocation = array_merge($userRoleLocation,$userLocation);
         

        $roleConfig = RoleConfig::where('role_id',$userRole)->first();

        $survey = Survey::find($survey_id);
       // echo  $survey->json;
        $formData = json_decode($survey->json, true);
        // echo json_encode($formData['pages'][0]['elements']);
        // die();
        $surveyOptions =  $survey['options'];
        $surveyQuestions =  $survey['questions'];
        //echo var_dump($surveyOptions);
        // echo json_encode($surveyOptions);
        //die();
        $primaryKeys = isset($survey->form_keys)?$survey->form_keys:[];

        $fields = array();
        
        $fields['userName'] = $user->id;
        $fields['isDeleted'] = false;
        $fields['jurisdiction_type_id'] = $roleConfig->jurisdiction_type_id;
        $fields['user_role_location'] = $userRoleLocation;

        $this->logData($this->logInfoPath,$this->request->all(),'DB');
        $primaryValues = array();
        $resultsArr = [];
        $cnt= 0;
        $itemArrCnt=0;
        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                
                $primaryValues[$key] = 1;
            }
           
            $valueChecK  =  $key;
            $item = $value;
            $arraySearchQuestion = $this->arraySearch($surveyQuestions, $valueChecK, $item);
          
            if($arraySearchQuestion)
            {
                
                $resultArrsearchOption = $this->arraySearch($surveyOptions, $valueChecK, $item);

                if($resultArrsearchOption)
                {
                  
                    $itemValueArray  = json_decode($item);
                     
                  
                     if(is_array($itemValueArray))
                     {
                     
                         foreach ($itemValueArray as $itemKkey => $itemValue) {

                           
                           $results['result_id'] = $itemValue;
                           
                           $results['question_id'] = $valueChecK;
                          
                            if($itemValue != 'other')
                               { 
                                 $resultArrsearchItem = $this->arraySearch($resultArrsearchOption, $itemValue, $item);
                                if(isset($resultArrsearchItem) && isset($resultArrsearchItem[0]['option_title']) )
                                    {
                                    $option_title = $resultArrsearchItem[0]['option_title'];
                         
                                    
                                    $results['result_title'] = $option_title;
                                    }else
                                    {
                     
                                    $results['result_title'] = $itemValue;                               
                                    }
                                     array_push($resultsArr, $results);
                               }

                               
                            //$itemArrCnt = $itemArrCnt+1;
                         }
                        
                    } else if(is_object($itemValueArray))
                    {
                        foreach($itemValueArray as $key => $objecData){
                            
                            foreach ($objecData as $rowKey => $rowValue) {
                               
                                foreach ($rowValue as $valueKey => $valueData) {
                                     $results['question_id'] = $key;
                                     $results['result_id'] = $rowKey;
                                     $results['result_title'] =  $valueKey;
                                     $results['result_flag'] =$valueData;
                                    array_push($resultsArr, $results);
                                    unset($results['result_flag']);
                                }
                               
                            }

                        }
                       
                    }  
                    //$cnt = $cnt +1;
                }else {

                        $results['result_id'] = 'text';
                        $results['question_id'] = $key;
                        $results['result_title'] = $value;
                        array_push($resultsArr, $results);
                    }
            }else
            {
                $checkValue = explode("-", $key);
               
                if(is_array($checkValue) && count($checkValue) == 2){
                    $results['result_id'] = $checkValue[1];
                    $results['question_id'] = $checkValue[0];
                    $results['result_title'] = $value;
                    array_push($resultsArr, $results);
                  

                }else{
                  
                     $fields[$key] = $value;
                }
            }
            //$cnt = $cnt +1;
            
        }    
       
       //die();
        // Gives current date and time in the format :  2019-01-24 10:30:46
        $date = Carbon::now();
        $fields['results'] = $resultsArr;
        

        $fields['submit_count'] = 1;
        $fields['updatedDateTime'] = $date->getTimestamp();
        $fields['createdDateTime'] = $date->getTimestamp();
        
        $fields['created_at'] = new \MongoDB\BSON\UTCDateTime();
        $fields['updatd_at'] = new \MongoDB\BSON\UTCDateTime();

        
         $fields['status'] = 'approved';


        /* if($survey['entity_id'] == null) {
          
            $collection_name = 'survey_results';
            $fields['form_id'] = $survey_id;

                // 'getUserResponse' function defined below, it queries the collection $collection_name using the parameters
                // $user->id,$survey_id,$primaryValues and returns the results
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);

                 // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
                // else an insert occurs and 'submit_count' gets value 1
                if(!empty($user_submitted)){
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this structure, please change values and try again.'],400);
                } else {

                    $approverUsers = array();
                    $timestamp = Date('Y-m-d H:i:s');
                    $approverList = $this->getApprovers($this->request, $user['role_id'], $user['location'], $user['org_id']);
                    $approverIds =array();
                    foreach($approverList as $approver) { 
                    $approverIds = $approver['id'];  
                    array_push($approverUsers,$approverIds);
                    }
                    $database = $this->connectTenantDatabase($this->request);
                    if ($database === null) {
                        return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                    }
                    $form = DB::collection('survey_results')->insertGetId($fields);
                    $ApprovalLog = new ApprovalLog;
                    $ApprovalLog['entity_id']=(string)$form;
                    $ApprovalLog['category_id']=" ";

                    $ApprovalLog['entity_type']='form';
                    $ApprovalLog['approver_ids']= $approverUsers;
                    $ApprovalLog['status'] = 'pending';
                    $ApprovalLog['userName']= $user->_id;
                    $ApprovalLog['reason'] = " ";
                    $ApprovalLog['form_id'] = (string)$form;
                    $ApprovalLog['default.org_id'] = $user->org_id;
                    $ApprovalLog['default.updated_by'] = "";
                    $ApprovalLog['default.created_by'] = $user->_id;
                    $ApprovalLog['default.created_on'] = $timestamp;    
                    $ApprovalLog['default.updated_on'] = "";
                    $ApprovalLog['default.project_id'] = $user->project_id;
                    $ApprovalLog['createdDateTime'] = $date->getTimestamp();
                    $ApprovalLog['updatedDateTime'] = $date->getTimestamp();
                    $ApprovalLog['createdDateTime'] = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
                    $ApprovalLog['updatedDateTime'] = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
                    $ApprovalLog->save();
                    
                    $ApprovalsPending = new ApprovalsPending;
                    $ApprovalsPending['entity_id']=(string)$form;
                    $ApprovalsPending['category_id']="";
                    $ApprovalsPending['entity_type']='form';
                    $ApprovalsPending['approver_ids']= $approverUsers;
                    $ApprovalsPending['status'] = 'pending';
                    $ApprovalsPending['userName']= $user->_id;
                    $ApprovalsPending['reason'] = " ";
                    $ApprovalsPending['form_id'] = (string)$form;
                    $ApprovalsPending['default.org_id'] = $user->org_id;
                    $ApprovalsPending['default.updated_by'] = "";
                    $ApprovalsPending['default.created_by'] = $user->_id;
                    $ApprovalsPending['default.created_on'] = $timestamp;    
                    $ApprovalsPending['default.updated_on'] = "";
                    $ApprovalsPending['default.project_id'] = $user->project_id;
                    $ApprovalsPending['createdDateTime'] = $date->getTimestamp();
                    $ApprovalsPending['updatedDateTime'] = $date->getTimestamp();
                    $ApprovalsPending['createdDateTime'] = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
                    $ApprovalsPending['updatedDateTime'] = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
                    $ApprovalsPending->save();
                    $data['_id'] = $form;
                }
        } else { */
           
            $collection_name = $survey->entity_collection_name;
            $fields['survey_id'] = $survey_id;

           /*  $entity = Entity::find($survey->entity_id);

            if($entity->Name == 'machinenonutilization'){
                $validate_filled_form = $this->validateMachineNonUtilization($user->id,$fields);
                if(!$validate_filled_form){
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine already utilized for the date'],400);
                }
            } */

            unset($fields['submit_count']);

            $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
            //echo json_encode($user_submitted);die();
           if(!empty($user_submitted) && $survey['multiple_entry'] == "false"){
                    // echo json_encode($user_submitted);
                    //die();
                if($project_id == '5e6f661cab7a197863606a74')
                    {
                        return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Form already submitted.'],400);
                    }
                    else
                    {
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this structure, please change values and try again.'],400);
                    }
            } 

            else {     
                $approverUsers = array();
                 $timestamp = Date('Y-m-d H:i:s');
                    $approverList = $this->getApprovers($this->request, $user['role_id'], $user['location'], $user['org_id']);
                    $approverIds =array();
                    foreach($approverList as $approver) { 
                    $approverIds = $approver['id'];  
                    array_push($approverUsers,$approverIds);
                    } 
                $database = $this->connectTenantDatabase($this->request);
                        if ($database === null) {
                            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                        }   

                $form = DB::collection($survey->entity_collection_name)->insertGetId($fields);

                $ApprovalLog = new ApprovalLog;
                // $ApprovalLog['entity_id']=(string)$form;
                $ApprovalLog['category_id']=(string)$survey->category_id;
                $ApprovalLog['entity_type']='form';
                $ApprovalLog['approver_ids']= $approverUsers;
                $ApprovalLog['status'] = 'pending';
                $ApprovalLog['userName']= $user->_id;
                $ApprovalLog['reason'] = "";
                $ApprovalLog['form_id'] = (string)$form;
                $ApprovalLog['default.org_id'] = $user->org_id;
                $ApprovalLog['default.updated_by'] = "";
                $ApprovalLog['default.created_by'] = $user->_id;
                $ApprovalLog['default.created_on'] = $timestamp;    
                $ApprovalLog['default.updated_on'] = "";
                $ApprovalLog['default.project_id'] = $user->project_id;
                $ApprovalLog['createdDateTimeing'] = $date->getTimestamp();
                $ApprovalLog['updatedDateTimeing'] = $date->getTimestamp();
                $ApprovalLog['createdDateTime'] = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
                $ApprovalLog['updatedDateTime'] = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
               
                $ApprovalLog->save();
                
                
                
                $ApprovalsPending = new ApprovalsPending;
                // $ApprovalsPending['entity_id']=(string)$form;
                $ApprovalsPending['category_id']=(string)$survey->category_id;
                $ApprovalsPending['entity_type']='form';
                $ApprovalsPending['approver_ids']= $approverUsers;
                $ApprovalsPending['status'] = 'pending';
                $ApprovalsPending['userName']= $user->_id;
                $ApprovalsPending['reason'] = "";
                $ApprovalsPending['form_id'] = (string)$form;
                $ApprovalsPending['default.org_id'] = $user->org_id;
                $ApprovalsPending['default.updated_by'] = "";
                $ApprovalsPending['default.created_by'] = $user->_id;
                $ApprovalsPending['default.created_on'] = $timestamp;    
                $ApprovalsPending['default.updated_on'] = "";
                $ApprovalsPending['default.project_id'] = $user->project_id;
                $ApprovalsPending['createdDateTimeing'] = $date->getTimestamp();
                $ApprovalsPending['updatedDateTimeing'] = $date->getTimestamp();
                $ApprovalsPending['createdDateTime'] = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
                $ApprovalsPending['updatedDateTime'] = new \MongoDB\BSON\UTCDateTime($date->getTimestamp()*1000);
                $ApprovalsPending->save();
                $data['_id'] = $form;
            }

       // }    

        $data['form_title'] = $this->generateFormTitle($org_id,$survey_id,$data['_id'],$collection_name);
        $data['form_title'] = " Form ";
         // echo json_encode($data['form_title']);
        // die();
        $data['createdDateTime'] = $fields['createdDateTime'];
        $data['updatedDateTime'] = $fields['updatedDateTime'];

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }

    public function validateMachineNonUtilization($user_id,$fields){
        $entity = Entity::where('Name', '=', 'machineworkhourrecord')->first();
        $collection_name = 'entity_'.$entity->id;
        $response = DB::collection($collection_name)->where('userName','=',$user_id)
                                                  ->where('isDeleted','=',false)
                                                  ->where('machine_code','=',$fields['machine_code'])
                                                  ->where('structure_code','=',$fields['structure_code'])
                                                  ->where('work_date','=',$fields['reporting_date'])
                                                  ->get()->first(); 
        if(!empty($response)){
            return false;
        }   
        
        $entity = Entity::where('Name', '=', 'silttransportationrecord')->first();
        $response = DB::collection($collection_name)->where('userName','=',$user_id)
                                                    ->where('isDeleted','=',false)
                                                    ->where('machine_code','=',$fields['machine_code'])
                                                    ->where('structure_code','=',$fields['structure_code'])
                                                    ->where('register_silt_transportation_date','=',$fields['reporting_date'])
                                                    ->get()->first(); 

        if(!empty($response)){
            return false;
        }   

        $entity = Entity::where('Name', '=', 'MachineMeterReadingPhotos')->first();
        $response = DB::collection($collection_name)->where('userName','=',$user_id)
                                                    ->where('isDeleted','=',false)
                                                    ->where('machine_code','=',$fields['machine_code'])
                                                    ->where('structure_code','=',$fields['structure_code'])
                                                    ->where('reporting_date','=',$fields['reporting_date'])
                                                    ->get()->first(); 

        if(!empty($response)){
            return false;
        } 

        $entity = Entity::where('Name', '=', 'farmersilttransportationrecord')->first();
        $response = DB::collection($collection_name)->where('userName','=',$user_id)
                                                    ->where('isDeleted','=',false)
                                                    ->where('machine_code','=',$fields['machine_code'])
                                                    ->where('structure_code','=',$fields['structure_code'])
                                                    ->where('reporting_date','=',$fields['reporting_date'])
                                                    ->get()->first(); 

        if(!empty($response)){
            return false;
        } 
        return true;
    } 

    public function getUserResponse($user_id,$survey_id,$primaryValues,$collection_name){
        $formKey = $collection_name == 'survey_results' ? 'form_id' : 'survey_id'; 
		
        $response = DB::collection($collection_name)->where($formKey,'=',$survey_id)
                                                  ->where('userName','=',$user_id)
                                                  ->where('isDeleted','=',false)
                                                  ->where(function($q) use ($primaryValues)
                                                  {
                                                      foreach($primaryValues as $key => $value)
                                                      {
                                                          $q->where($key, '=', $value);
                                                      }
                                                  })
                                                  ->get()->first();
		//echo json_encode($collection_name);die();										 
        return $response;   
    }

    public function showResponse($survey_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user(); 

        $survey = Survey::find($survey_id);
        
        $limit = (int)$this->request->input('limit') ?:50;
        $offset = $this->request->input('offset') ?:0;
        $order = $this->request->input('order') ?:'desc';
        $field = $this->request->input('field') ?:'createdDateTime';
        $page = $this->request->input('page') ?:1;
        $endDate = $this->request->input('start_date') ?:Carbon::now('Asia/Calcutta')->getTimestamp();
        $startDate = $this->request->input('end_date') ?:Carbon::now('Asia/Calcutta')->subMonth()->getTimestamp();
    
        $role = $this->request->user()->role_id;
        $roleConfig = \App\RoleConfig::where('role_id', $role)->first();
        $jurisdictionTypeId = $roleConfig->jurisdiction_type_id;

        $userLocation = $this->getFullHierarchyUserLocation($this->request->user()->location, $jurisdictionTypeId);
        $locationKeys = $this->getFormSchemaKeys($survey_id);


            
       /*  if(!isset($survey->entity_id)) {
            $collection_name = 'survey_results';
            $surveyResults = DB::collection('survey_results')
                                ->where('form_id','=',$survey_id)
                                ->where('userName','=',$user->id)
                                ->where('isDeleted','!=',true)
                                ->whereBetween('createdDateTime',array($startDate,$endDate))
                                ->where(function($q) use ($userLocation, $locationKeys) {
                                    if (!empty($locationKeys)) {
                                        foreach ($locationKeys as $locationKey) {
                                            if (isset($userLocation[$locationKey]) && !empty($userLocation[$locationKey])) {
                                                $q->whereIn($locationKey, $userLocation[$locationKey]);
                                            }
                                        }
                                    } else {
                                        foreach ($this->request->user()->location as $level => $location) {
                                            $q->whereIn('user_role_location.' . $level, $location);
                                        }
                                    }
                                })
                                ->orderBy($field,$order)
                                ->paginate($limit);
        } else { */ 
        
            $collection_name = $survey->entity_collection_name;           
            $surveyResults = DB::collection($survey->entity_collection_name)
                                ->where('survey_id','=',$survey_id)
                                ->where('userName','=',$user->id)
                                ->where('isDeleted','!=',true)
                                ->whereBetween('createdDateTime',array($startDate,$endDate))
                                ->where(function($q) use ($userLocation, $locationKeys) {
                                    if (!empty($locationKeys)) {
                                        foreach ($locationKeys as $locationKey) {
                                            if (isset($userLocation[$locationKey]) && !empty($userLocation[$locationKey])) {
                                                $q->whereIn($locationKey, $userLocation[$locationKey]);
                                            }
                                        }
                                    } else {
                                        foreach ($this->request->user()->location as $level => $location) {
                                            $q->OrwhereIn('user_role_location.' . $level, $location);
                                        }
                                    }
                                }) 
                                ->orderBy($field,$order)
                                ->paginate($limit);

        /* } */
		  
        if ($surveyResults->count() === 0) {
            return response()->json(['status'=>'success','metadata'=>[],'values'=>[],'message'=>'']);
        }
            
        $createdDateTime = $surveyResults[0]['createdDateTime'];
        $responseCount = $surveyResults->count();
       
        $result = ['form'=>['form_id'=>$survey_id,'userName'=>$surveyResults[0]['userName'],'createdDateTime'=>$createdDateTime, 'submit_count'=>$responseCount]];

        $values = [];
       
        foreach($surveyResults as &$surveyResult)
        {
            if (!isset($surveyResult['form_id'])) {
                $surveyResult['form_id'] = $survey_id;
            }
            $form_title =$this->generateFormTitle($survey,$surveyResult['_id'],$collection_name);
            $surveyResult['form_title'] = $form_title;
            $status= ApprovalsPending::where('entity_id',$survey->entity_id)->where('userName',$user->id)->select('status')->where('entity_type','form')->get();
            if(count($status) > 0){ 
            $surveyResult['status']= $status[0]->status;
            }
            // Excludes values 'form_id','user_id','created_at','updated_at' from the $surveyResult array
            //  and stores it in values
            $values[] = Arr::except($surveyResult,['survey_id','userName','createdDateTime', 'user_role_location', 'jurisdiction_type_id']);
        }


        $result['Current page'] = 'Page '.$surveyResults->currentPage().' of '.$surveyResults->lastPage();
        $result['Total number of records'] = $surveyResults->total();
        
        return response()->json(['status'=>'success','metadata'=>[$result],'values'=>$values,'message'=>'']);

    }

    public function deleteFormResponse($formId,$recordId)
    {
        try {

            $database = $this->connectTenantDatabase($this->request);
                if ($database === null) {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }
    
            $form = Survey::find($formId);
    
            if(empty($form)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => "Form does not exist"
                    ],
                    404
                );
            }
    
        if(empty($form->entity_id))
            $record = SurveyResult::find($recordId);
        else
            $record = DB::collection('entity_'.$form->entity_id)->where('_id',$recordId);

            if((!isset($record->userName) && $this->request->user()->id !== $record->first()['userName']) || (isset($record->userName) && $this->request->user()->id !== $record->userName ) ){
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => "Responses cannot be deleted as you have not created the form"
                    ],
                    403
                );
            }

        $record->update(['isDeleted' => true]);

            return response()->json(
                [
                    'status' => 'success',
                    'data' => '',
                    'message' => "Record deleted successfully"
                ],
                200
            );
    
            } catch(\Exception $exception) {
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

    public function createAggregateResponse($survey_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        $survey = Survey::find($survey_id);
        $primaryKeys = isset($survey->form_keys)?$survey->form_keys:[];

        $fields = array();
        
        $fields['userName'] = $user->id;
        $fields['isDeleted'] = false;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        } 

        // Gives current date and time in the format :  2019-01-24 10:30:46
        $date = Carbon::now();
        
        $fields['updatedDateTime'] = $date->getTimestamp();
        $fields['createdDateTime'] = $date->getTimestamp();


        if($survey->entity_id == null)
        {
            $collection_name = 'survey_results';
            $fields['form_id'] = $survey_id;

            list($matrix_field_label, $matrix_fields) = $this->getMatrixdynamicFields($survey);      
            
            if(isset($matrix_field_label)){
                $matrix_request_data = $this->request->input($matrix_field_label);
                foreach($matrix_request_data as $matrix_data){
                    foreach($matrix_data as $key=>$value){
                        if(in_array($key,$primaryKeys)){
                            $primaryValues[$key] = $value; 
                        }
                    }
                    if(!empty($primaryValues)){
                        $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                        if(!empty($user_submitted)){
                            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this structure, please change values and try again.'],400);
                        }
                    }
                }
 
                unset($fields[$matrix_field_label]);
                $group_arr = [];
                foreach($matrix_request_data as $matrix_data){
                    foreach($matrix_data as $key=>$value){
                        $fields[$key] = $value;
                    }
                    $form = DB::collection('survey_results')->insertGetId($fields);
                    $form_insert_id = $form->__toString();
                    array_push($group_arr,$form_insert_id);
                }
                $assoc_data = array('userName'=>$user->id,'children'=>$group_arr,'form_id'=>$survey_id,'createdDateTime'=>$date->getTimestamp(),'updatedDateTime'=>$date->getTimestamp(),'isDeleted'=>false);
                $aggregate_assoc = DB::collection('aggregate_associations')->insertGetId($assoc_data);
                $data['_id'] = $aggregate_assoc;
                }

        } else {
            $collection_name = 'entity_'.$survey->entity_id;
            $fields['survey_id'] = $survey_id;
            $entity = Entity::find($survey->entity_id);

            list($matrix_field_label, $matrix_fields) = $this->getMatrixdynamicFields($survey);      
            
            if(isset($matrix_field_label)){
                //loop and handle validations before saving records
                $matrix_request_data = $this->request->input($matrix_field_label);
                foreach($matrix_request_data as $matrix_data){
                    foreach($matrix_data as $key=>$value){
                        if(in_array($key,$primaryKeys)){
                            $primaryValues[$key] = $value; 
                        }
                    }
                    if(!empty($primaryValues)){
                        $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                        if(!empty($user_submitted)){
                            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this structure, please change values and try again.'],400);
                        }
                    }
                    
                    if($entity->Name == 'dieselfilledrecord'){
                        $machine_nonutilized  = $this->checkMachineNonUtilized($user->id,$fields['machine_code'],$matrix_data['work_date'],'non_availability_of_diesel');  
                        if ($machine_nonutilized){
                            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter diesel filled record for this machine.'],400);
                        }
                    }

                }

                //loop and save the records after above validations are passed
                unset($fields[$matrix_field_label]);
                $group_arr = [];
                foreach($matrix_request_data as $matrix_data){
                    foreach($matrix_data as $key=>$value){
                        $fields[$key] = $value;
                    }

                    $form = DB::collection('entity_'.$survey->entity_id)->insertGetId($fields);
                    $form_insert_id = $form->__toString();
                    array_push($group_arr,$form_insert_id);
                }

                $assoc_data = array('userName'=>$user->id,'children'=>$group_arr,'form_id'=>$survey_id,'createdDateTime'=>$date->getTimestamp(),'updatedDateTime'=>$date->getTimestamp(),'isDeleted'=>false);
                $userRoleLocation = $user->location;
                $userRoleLocation['role_id'] = $user->role_id;
                $assoc_data['user_role_location'] = $userRoleLocation;
                $roleConfig = \App\RoleConfig::where('role_id', $user->role_id)->first();
                $assoc_data['jurisdiction_type_id'] = $roleConfig->jurisdiction_type_id;

                $aggregate_assoc = DB::collection('aggregate_associations')->insertGetId($assoc_data);
                $data['_id'] = $aggregate_assoc;
            }                   


        }    

        $data['form_title'] = $this->generateFormTitle($survey_id,$form,$collection_name);
        $data['createdDateTime'] = $fields['createdDateTime'];
        $data['updatedDateTime'] = $fields['updatedDateTime'];

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }  
    
    public function getMatrixdynamicFields($survey){
        $data = json_decode($survey->json,true); 

        $pages = $data['pages'];

        $matrix_name = null;
        foreach($pages as $page)
        {
            // Accessing the value of key elements to obtain the names of the questions
            foreach($page['elements'] as $element)
            {
                if($element['type'] == 'matrixdynamic'){
                    $matrix_name = $element['name'];
                    $columns = array_key_exists('columns',$element)? $element['columns']: [];
                    foreach($columns as $column){
                        $matrix_fields[] = $column['name']; 
                    }
                    break;
                }
            }
        }
        return [$matrix_name,$matrix_fields];
    }

    public function showAggregateResponse($survey_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();
        $userLocation = $user->location;
        
        $survey = Survey::find($survey_id);

        $limit = (int)$this->request->input('limit') ?:50;
        $offset = $this->request->input('offset') ?:0;
        $order = $this->request->input('order') ?:'desc';
        $field = $this->request->input('field') ?:'createdDateTime';
        $page = $this->request->input('page') ?:1;
        $endDate = $this->request->input('start_date') ?:Carbon::now('Asia/Calcutta')->getTimestamp();
        $startDate = $this->request->input('end_date') ?:Carbon::now('Asia/Calcutta')->subMonth()->getTimestamp();

        $role = $this->request->user()->role_id;
        $roleConfig = \App\RoleConfig::where('role_id', $role)->first();
        $jurisdictionTypeId = $roleConfig->jurisdiction_type_id;

        $userLocation = $this->getFullHierarchyUserLocation($this->request->user()->location, $jurisdictionTypeId);
        $locationKeys = $this->getFormSchemaKeys($survey_id);

        $aggregateResults = DB::collection('aggregate_associations')
        ->where('form_id','=',$survey_id)
        ->where(function($q) use ($userLocation) {
            foreach ($this->request->user()->location as $level => $location) {
                    $q->whereIn('user_role_location.' . $level, $location);
                }
        })
        ->where('userName','=',$user->id)
        ->where('isDeleted','=',false)
        ->whereBetween('createdDateTime',array($startDate,$endDate))
        ->orderBy($field,$order)
        ->paginate($limit);    
        
       // var_dump($aggregateResults);exit;
        
        if($survey->entity_id == null)
            $collection_name = 'survey_results';
        else
            $collection_name = 'entity_'.$survey->entity_id;           
 

        if ($aggregateResults->count() === 0) {
            return response()->json(['status'=>'success','metadata'=>[],'values'=>[],'message'=>'']);
        }
        

        $responseCount = $aggregateResults->count();
        $result = ['form'=>['form_id'=>$survey_id,'userName'=>$aggregateResults[0]['userName'],'submit_count'=>$responseCount]];

        $values = [];
        list($matrix_field_label, $matrix_fields) = $this->getMatrixdynamicFields($survey);
        print_R($aggregateResults);die();
        foreach($aggregateResults as &$aggregateResult)
        {
            $associated_results = $this->getAssociatedDocuments($aggregateResult['children'],$collection_name,$user->id, $userLocation, $locationKeys);
            $record_id = $aggregateResult['_id'];
            $first_iteration_flag = false;
            $matrix_fields_data =array();
            $matrix_obj = array();
            foreach ($associated_results as &$associated_result){
                foreach (array_map('strtolower', $this->getLevels()->toArray()) as $singleJurisdiction) {
                    if (isset($associated_result[$singleJurisdiction . '_id'])) {
                        $associated_result[$singleJurisdiction] = $associated_result[$singleJurisdiction . '_id'];
                        unset($associated_result[$singleJurisdiction . '_id']);
                    }
                }
                if($first_iteration_flag){
                    foreach($matrix_fields as $matrix_field){
                            $matrix_obj[$matrix_field] = isset($associated_result[$matrix_field]) ? $associated_result[$matrix_field] : '';
                        }
                    array_push($matrix_fields_data ,$matrix_obj);

                }else{
                    $aggregateResult = $associated_result;
                    $aggregateResult['_id']=$record_id;
                    foreach($matrix_fields as $matrix_field){
                            $matrix_obj[$matrix_field] = isset($associated_result[$matrix_field]) ? $associated_result[$matrix_field] : '';
                            unset($aggregateResult[$matrix_field]);
                        }
                    array_push($matrix_fields_data ,$matrix_obj);
                    $first_iteration_flag = true;
                    $form_title =$this->generateFormTitle($survey,$associated_result['_id'],$collection_name);
                    $aggregateResult['form_title'] = $form_title;
                }


            }
            $aggregateResult[$matrix_field_label] = $matrix_fields_data;
            // Excludes values 'form_id','user_id','created_at','updated_at','group_id' from the $surveyResult array
            //  and stores it in values
            $values[] = Arr::except($aggregateResult,['survey_id','userName']);
        }

        $result['Current page'] = 'Page '.$aggregateResults->currentPage().' of '.$aggregateResults->lastPage();
        $result['Total number of records'] = $aggregateResults->total();
        // $result['Total number of pages'] = $surveyResults->lastPage();
        return response()->json(['status'=>'success','metadata'=>[$result],'values'=>$values,'message'=>'']);

    }

    public function getAssociatedDocuments($children,$collection_name,$user_id, $userLocation, $locationKeys){
        $results = DB::collection($collection_name)
                                ->where('userName','=',$user_id)
                                ->where('isDeleted','!=',true)
                                ->whereIn('_id',$children)
                                ->where(function($q) use ($userLocation, $locationKeys) {
                                    foreach ($locationKeys as $locationKey) {
                                        if (isset($userLocation[$locationKey]) && !empty($userLocation[$locationKey])) {
                                            $q->whereIn($locationKey, $userLocation[$locationKey]);
                                        }
                                    }
                                })
                                ->get();
        return $results;
    }


    public function updateAggregateResponse($survey_id,$groupId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        $survey = Survey::find($survey_id);

        // Selecting the collection to use depending on whether the survey has an entity_id or not
        $collection_name = isset($survey->entity_id)?'entity_'.$survey->entity_id:'survey_results';

        $primaryKeys = $survey->form_keys;

        $entity = isset($survey->entity_id)?Entity::find($survey->entity_id):null;

        $fields = array();
        // $responseId = $this->request->input('responseId');
        
        $fields['userName']=$user->id;

        $primaryValues = array();

        $group_record = DB::collection('aggregate_associations')
        ->where('form_id','=',$survey_id)
        ->where('userName','=',$user->id)
        ->where('_id','=',$groupId);
        
        $children = $group_record->first()['children'];

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }        

        list($matrix_field_label, $matrix_fields) = $this->getMatrixdynamicFields($survey); 
            
        if($matrix_field_label != null){
            $matrix_request_data = $this->request->input($matrix_field_label);
            unset($fields[$matrix_field_label]);
            foreach ($matrix_request_data as $matrix_request_data_entry){
                $update_id = null;
                //validate the matrix dynamic PUT request
                foreach($matrix_request_data_entry  as $key=>$value){
                    if(in_array($key,$primaryKeys)){
                        $primaryValues[$key] = $value; 
                    }

                    if($key == '_id'){
                        $update_id = $matrix_request_data_entry[$key];
                    }
                }

                if($update_id !== null){
                    $formExists = DB::collection($collection_name)->where(function($q) use ($survey_id){
                        $q->where('form_id','=',$survey_id)
                        ->orWhere('survey_id','=',$survey_id);
                    })
                                        ->where('userName','=',$user->id)
                                        ->where(function($q) use ($primaryValues)
                                        {
                                            foreach($primaryValues as $key => $value)
                        {
                            $q->where($key, '=', $value);
                        }
                    })
                    ->where('_id','!=',$update_id)
                    ->get()->first();
            
                }else{
                    $formExists = [];
                    if(!empty($primaryValues)){
                        $formExists = DB::collection($collection_name)->where(function($q) use ($survey_id){
                            $q->where('form_id','=',$survey_id)
                            ->orWhere('survey_id','=',$survey_id);
                        })
                                            ->where('userName','=',$user->id)
                                            ->where(function($q) use ($primaryValues)
                                            {
                                                foreach($primaryValues as $key => $value)
                            {
                                $q->where($key, '=', $value);
                            }
                        })
                        ->get()->first();
                    }
                    
                    if(isset($entity) && $entity->Name == 'dieselfilledrecord'){
                        $machine_nonutilized  = $this->checkMachineNonUtilized($user->id,$fields['machine_code'],$matrix_request_data_entry['work_date'],'non_availability_of_diesel');  
                        if ($machine_nonutilized){
                            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter diesel filled record for this machine.'],400);
                        }
                    }
                    
                }
                if (!empty($formExists)) {
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Update Failure!!! Entry already exists with the same values.'],400);
                }
            }

            // Gives current date and time in the format :  2019-01-24 10:30:46
            $date = Carbon::now();
            $fields['updatedDateTime'] = $date->getTimestamp();       

            //loop through the validated data and Update or create Records
            $group_arr = array();
            foreach ($matrix_request_data as $matrix_request_data_entry){
                $update_id = null;
                foreach($matrix_request_data_entry as $key=>$value){
                    if($key == '_id'){
                        $update_id = $matrix_request_data_entry[$key];
                    }else{
                    $fields[$key] = $value;
                    }
                }

                if($update_id !== null){
                    $update_rec =  DB::collection($collection_name)
                    ->where('_id',$update_id);
                    $update_rec->update($fields);
                    array_push($group_arr,$update_id);
                }else{
                    $fields['createdDateTime'] = $date->getTimestamp(); 
                    $fields['isDeleted'] = false;
                    $fields['survey_id'] = $survey_id;
                    $form = DB::collection($collection_name)->insertGetId($fields);
                    unset($fields['createdDateTime']);
                    unset($fields['isDeleted']);
                    unset($fields['survey_id']);
                    $form_insert_id = $form->__toString();
                    array_push($group_arr,$form_insert_id);
                }
            }
            
            $deleted_entries = array_diff($children,$group_arr);
            if(!empty($deleted_entries)){
                DB::collection($collection_name)->whereIn('_id', $deleted_entries)->update(['isDeleted' => true]);
            }
            
            $group_record->update(array('children'=>$group_arr,'updatedDateTime'=>$fields['updatedDateTime']));
            
        }

        // Function defined below, it queries the collection $collection_name using the parameters
        if($survey->entity_id == null)
        {
            $data['form_title'] = $this->generateFormTitle($survey_id,$group_arr[0],'survey_results');
        }
        else
        {         
            $data['form_title'] = $this->generateFormTitle($survey_id,$group_arr[0],'entity_'.$survey->entity_id);
        }

        $data['_id']['$oid'] = $groupId;
        $data['createdDateTime'] = $group_record->first()['createdDateTime'];
        $data['updatedDateTime'] = $group_record->first()['updatedDateTime'];
        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }

    public function deleteAggregateResponse($survey_id,$groupId){
        try {
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }

            $group_record = DB::collection('aggregate_associations')
                            ->where('_id',$groupId)
                            ->where('isDeleted','=',false);

            $record_data = $group_record->first();
            
            if($record_data != null){
                if((!isset($record_data['userName'])) || (isset($record_data['userName']) && $this->request->user()->id !== $record_data['userName'] ) ){
                    return response()->json(
                        [
                            'status' => 'error',
                            'data' => '',
                            'message' => "Responses cannot be deleted as you have not created the form"
                        ],
                        403
                    );
                }

            $form = Survey::find($survey_id);
        
            if(empty($form)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => "Form does not exist"
                    ],
                    404
                );
            }
            // Selecting the collection to use depending on whether the survey has an entity_id or not
            $collection_name = isset($form->entity_id)?'entity_'.$form->entity_id:'survey_results';

            foreach ($record_data['children'] as $child_id){
                $record = DB::collection($collection_name)->where('_id',$child_id);
                $record->update(array('isDeleted'=>true));
            }

            $group_record->update(array('isDeleted'=>true,'children'=>[]));
            return response()->json(
                [
                    'status' => 'success',
                    'data' => '',
                    'message' => "Record deleted successfully"
                ],
                200
            );
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => "Resource not found"
                    ],
                    404
                );         
            }
        } catch(\Exception $exception) {
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

    public function machineAggregateWorkhours($survey_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        $survey = Survey::find($survey_id);
        $primaryKeys = isset($survey->form_keys)?$survey->form_keys:[];

        $fields = array();
        
        $fields['userName'] = $user->id;
        $fields['isDeleted'] = false;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        } 

        // Gives current date and time in the format :  2019-01-24 10:30:46
        $date = Carbon::now();
        
        $fields['updatedDateTime'] = $date->getTimestamp();
        $fields['createdDateTime'] = $date->getTimestamp();


        if($survey->entity_id == null)
        {
            $collection_name = 'survey_results';
            $fields['form_id'] = $survey_id;

            list($matrix_field_label, $matrix_fields) = $this->getMatrixdynamicFields($survey);      
            
            if(isset($matrix_field_label)){
                $machine_code = $this->request->input('machine_code');
                $matrix_request_data = $this->request->input($matrix_field_label);
                foreach($matrix_request_data as $matrix_data){
                    foreach($matrix_data as $key=>$value){
                        if(in_array($key,$primaryKeys)){
                            $primaryValues[$key] = $value; 
                        }
                        if($key== 'work_date'){
                            $work_date = $value;
                        }
                    }
                    if(!empty($primaryValues)){
                        $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                        if(!empty($user_submitted)){
                            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Some Entries already exists with the same values.'],400);
                        }
                    }
                    //validation check to see if record exists in machine non_utilization
                    $machine_non_utilized= $this->checkMachineNonUtilized($user->id,$machine_code,$work_date);
                    if($machine_non_utilized){
                        return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter work hour record for this machine.'],400); 
                    }
                }
 
                unset($fields[$matrix_field_label]);
                $group_arr = [];
                foreach($matrix_request_data as $matrix_data){
                    foreach($matrix_data as $key=>$value){
                        $fields[$key] = $value;
                    }
                    $form = DB::collection('survey_results')->insertGetId($fields);
                    $form_insert_id = $form->__toString();
                    array_push($group_arr,$form_insert_id);
                }
                $assoc_data = array('userName'=>$user->id,'children'=>$group_arr,'form_id'=>$survey_id,'createdDateTime'=>$date->getTimestamp(),'updatedDateTime'=>$date->getTimestamp(),'isDeleted'=>false);
                $userRoleLocation = $user->location;
                $userRoleLocation['role_id'] = $user->role_id;
                $assoc_data['user_role_location'] = $userRoleLocation;
                $roleConfig = \App\RoleConfig::where('role_id', $user->role_id)->first();
                $assoc_data['jurisdiction_type_id'] = $roleConfig->jurisdiction_type_id;
                $aggregate_assoc = DB::collection('aggregate_associations')->insertGetId($assoc_data);
                $data['_id'] = $aggregate_assoc;
                }

        } else {
            $collection_name = 'entity_'.$survey->entity_id;
            $fields['survey_id'] = $survey_id;

            list($matrix_field_label, $matrix_fields) = $this->getMatrixdynamicFields($survey);      
            
            if(isset($matrix_field_label)){
                //loop and handle validations before saving records
                $machine_code = $this->request->input('machine_code');
                $matrix_request_data = $this->request->input($matrix_field_label);
                foreach($matrix_request_data as $matrix_data){
                    $machine_non_utilized = false;
                    foreach($matrix_data as $key=>$value){
                        if(in_array($key,$primaryKeys)){
                            $primaryValues[$key] = $value; 
                        }
                        if($key== 'work_date'){
                            $work_date = $value;
                        }
                    }
                    //validation check to see if record exists in machine non_utilization
                    $machine_non_utilized= $this->checkMachineNonUtilized($user->id,$machine_code,$work_date);
                    if($machine_non_utilized){
                        return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter work hour record for this machine.'],400); 
                    }

                    if(!empty($primaryValues)){
                        $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                        if(!empty($user_submitted)){
                            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this structure, please change values and try again.'],400);
                        }
                    }

                    
                }

                //loop and save the records after above validations are passed
                unset($fields[$matrix_field_label]);
                $group_arr = [];
                foreach($matrix_request_data as $matrix_data){
                    foreach($matrix_data as $key=>$value){
                        $fields[$key] = $value;
                    }

                    $form = DB::collection('entity_'.$survey->entity_id)->insertGetId($fields);
                    $form_insert_id = $form->__toString();
                    array_push($group_arr,$form_insert_id);
                }
                $assoc_data = array('userName'=>$user->id,'children'=>$group_arr,'form_id'=>$survey_id,'createdDateTime'=>$date->getTimestamp(),'updatedDateTime'=>$date->getTimestamp(),'isDeleted'=>false);
                $userRoleLocation = $user->location;
                $userRoleLocation['role_id'] = $user->role_id;
                $assoc_data['user_role_location'] = $userRoleLocation;
                $roleConfig = \App\RoleConfig::where('role_id', $user->role_id)->first();
                $assoc_data['jurisdiction_type_id'] = $roleConfig->jurisdiction_type_id;
                $aggregate_assoc = DB::collection('aggregate_associations')->insertGetId($assoc_data);
                $data['_id'] = $aggregate_assoc;
            }                   


        }    

        $data['form_title'] = $this->generateFormTitle($survey_id,$form,$collection_name);
        $data['createdDateTime'] = $fields['createdDateTime'];
        $data['updatedDateTime'] = $fields['updatedDateTime'];

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }
    
    public function checkMachineNonUtilized($user_id,$machine_code,$work_date,$reason=null){
        $entity = Entity::where('Name', '=', 'machinenonutilization')->first();
        $collection_name = 'entity_'.$entity->id;
        $response = DB::collection($collection_name)->where('isDeleted','=',false)
                                                  ->where('machine_code','=',$machine_code)
                                                  ->where('reporting_date','=',$work_date)
                                                  ->get()->first();
        if(empty($response)){
            return false;
        }else{
            if(isset($reason) && $reason == $response['non_utilization_reasons'] ){
                return false;
            }
            return true;
        }
    }

    public function updateAggregateWorkhours($survey_id,$groupId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        $survey = Survey::find($survey_id);

        // Selecting the collection to use depending on whether the survey has an entity_id or not
        $collection_name = isset($survey->entity_id)?'entity_'.$survey->entity_id:'survey_results';

        $primaryKeys = $survey->form_keys;

        $fields = array();
        // $responseId = $this->request->input('responseId');
        
        $fields['userName']=$user->id;

        $primaryValues = array();

        $group_record = DB::collection('aggregate_associations')
        ->where('form_id','=',$survey_id)
        ->where('userName','=',$user->id)
        ->where('_id','=',$groupId);
        
        $children = $group_record->first()['children'];

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }        

        list($matrix_field_label, $matrix_fields) = $this->getMatrixdynamicFields($survey); 
            
        if($matrix_field_label != null){
            $machine_code = $this->request->input('machine_code');
            $matrix_request_data = $this->request->input($matrix_field_label);
            unset($fields[$matrix_field_label]);
            foreach ($matrix_request_data as $matrix_request_data_entry){
                $update_id = null;
                //validate the matrix dynamic PUT request
                foreach($matrix_request_data_entry  as $key=>$value){
                    if(in_array($key,$primaryKeys)){
                        $primaryValues[$key] = $value; 
                    }

                    if($key == '_id'){
                        $update_id = $value;
                    }
                    if($key== 'work_date'){
                        $work_date = $value;
                    }
                }

                //validation check to see if record exists in machine non_utilization
                $machine_non_utilized= $this->checkMachineNonUtilized($user->id,$machine_code,$work_date);
                if($machine_non_utilized){
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter work hour record for this machine.'],400); 
                }

                if($update_id !== null){
                    $formExists = DB::collection($collection_name)->where(function($q) use ($survey_id){
                        $q->where('form_id','=',$survey_id)
                        ->orWhere('survey_id','=',$survey_id);
                    })
                                        ->where('userName','=',$user->id)
                                        ->where(function($q) use ($primaryValues)
                                        {
                                            foreach($primaryValues as $key => $value)
                        {
                            $q->where($key, '=', $value);
                        }
                    })
                    ->where('_id','!=',$update_id)
                    ->get()->first();
            
                }else{
                    $formExists = [];
                    if(!empty($primaryValues)){
                        $formExists = DB::collection($collection_name)->where(function($q) use ($survey_id){
                            $q->where('form_id','=',$survey_id)
                            ->orWhere('survey_id','=',$survey_id);
                        })
                                            ->where('userName','=',$user->id)
                                            ->where(function($q) use ($primaryValues)
                                            {
                                                foreach($primaryValues as $key => $value)
                            {
                                $q->where($key, '=', $value);
                            }
                        })
                        ->get()->first();
                    }
                }
                if (!empty($formExists)) {
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Update Failure!!! Entry already exists with the same values.'],400);
                }


            }

            // Gives current date and time in the format :  2019-01-24 10:30:46
            $date = Carbon::now();
            $fields['updatedDateTime'] = $date->getTimestamp();       

            //loop through the validated data and Update or create Records
            $group_arr = array();
            foreach ($matrix_request_data as $matrix_request_data_entry){
                $update_id = null;
                foreach($matrix_request_data_entry as $key=>$value){
                    if($key == '_id'){
                        $update_id = $matrix_request_data_entry[$key];
                    }else{
                    $fields[$key] = $value;
                    }
                }

                if($update_id !== null){
                    $update_rec =  DB::collection($collection_name)
                    ->where('_id',$update_id);
                    $update_rec->update($fields);
                    array_push($group_arr,$update_id);
                }else{
                    $fields['createdDateTime'] = $date->getTimestamp(); 
                    $fields['isDeleted'] = false;
                    $fields['survey_id'] = $survey_id;
                    $form = DB::collection($collection_name)->insertGetId($fields);
                    unset($fields['createdDateTime']);
                    unset($fields['isDeleted']);
                    unset($fields['survey_id']);
                    $form_insert_id = $form->__toString();
                    array_push($group_arr,$form_insert_id);
                }
            }
            
            $deleted_entries = array_diff($children,$group_arr);
            if(!empty($deleted_entries)){
                DB::collection($collection_name)->whereIn('_id', $deleted_entries)->update(['isDeleted' => true]);
            }
            
            $group_record->update(array('children'=>$group_arr,'updatedDateTime'=>$fields['updatedDateTime']));
            
        }

        // Function defined below, it queries the collection $collection_name using the parameters
        if($survey->entity_id == null)
        {
            $data['form_title'] = $this->generateFormTitle($survey_id,$group_arr[0],'survey_results');
        }
        else
        {         
            $data['form_title'] = $this->generateFormTitle($survey_id,$group_arr[0],'entity_'.$survey->entity_id);
        }

        $data['_id']['$oid'] = $groupId;
        $data['createdDateTime'] = $group_record->first()['createdDateTime'];
        $data['updatedDateTime'] = $group_record->first()['updatedDateTime'];
        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }

    public function siltTransportation($survey_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();
        //validation check to see if structure is completed
        $structure_code = $this->request->input('structure_code');
        if($this->isStructureCompleted($user->id,$structure_code)){
            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Structure status is completed.'],400);
        }

        $machine_code = $this->request->input('machine_code');
        $work_date = $this->request->input('register_silt_transportation_date');
        //validation check to see if record exists in machine non_utilization
         $machine_non_utilized= $this->checkMachineNonUtilized($user->id,$machine_code,$work_date);
         if($machine_non_utilized){
             return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you cannot enter Silt transportation record for this machine.'],400); 
         }

        $userLocation = $this->request->user()->location;  
        
        $userRole = $this->request->user()->role_id;  
        $userRoleLocation = ['role_id' => $userRole];
        $userRoleLocation = array_merge($userRoleLocation,$userLocation);

        $roleConfig = RoleConfig::where('role_id',$userRole)->first();

        $survey = Survey::find($survey_id);
        $primaryKeys = isset($survey->form_keys)?$survey->form_keys:[];

        $fields = array();
        
        $fields['userName'] = $user->id;
        $fields['isDeleted'] = false;
        $fields['jurisdiction_type_id'] = $roleConfig->jurisdiction_type_id;
        $fields['user_role_location'] = $userRoleLocation;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }       

        // Gives current date and time in the format :  2019-01-24 10:30:46
        $date = Carbon::now();
        
        $fields['submit_count'] = 1;
        $fields['updatedDateTime'] = $date->getTimestamp();
        $fields['createdDateTime'] = $date->getTimestamp();


        if($survey->entity_id == null) {
            $collection_name = 'survey_results';
            $fields['form_id'] = $survey_id;

                // 'getUserResponse' function defined below, it queries the collection $collection_name using the parameters
                // $user->id,$survey_id,$primaryValues and returns the results
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);

                 // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
                // else an insert occurs and 'submit_count' gets value 1
                if(!empty($user_submitted)){
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this structure, please change values and try again.'],400);
                } else {
                    $form = DB::collection('survey_results')->insertGetId($fields);
                    $data['_id'] = $form;
                }
        } else {
            $collection_name = 'entity_'.$survey->entity_id;
            $fields['survey_id'] = $survey_id;

            $entity = Entity::find($survey->entity_id);

                unset($fields['submit_count']);
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                
                if(!empty($user_submitted)){
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this structure, please change values and try again.'],400);
                } else {                    
                    $form = DB::collection('entity_'.$survey->entity_id)->insertGetId($fields);
                    $data['_id'] = $form;
                }

        }    

        $data['form_title'] = $this->generateFormTitle($survey_id,$data['_id'],$collection_name);
        $data['createdDateTime'] = $fields['createdDateTime'];
        $data['updatedDateTime'] = $fields['updatedDateTime'];

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }

    public function updateSiltTransportation($survey_id,$responseId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        //validation check to see if structure is completed
        $structure_code = $this->request->input('structure_code');
        if($this->isStructureCompleted($user->id,$structure_code)){
            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Structure status is completed.'],400);
        }


        $machine_code = $this->request->input('machine_code');
        $work_date = $this->request->input('register_silt_transportation_date');
        //validation check to see if record exists in machine non_utilization
        $machine_non_utilized= $this->checkMachineNonUtilized($user->id,$machine_code,$work_date);
        if($machine_non_utilized){
            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter work hour record for this machine.'],400); 
        }

        $survey = Survey::find($survey_id);
        $primaryKeys = $survey->form_keys;

        $fields = array();
        
        $fields['userName']=$user->id;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }        

        // Gives current date and time in the format :  2019-01-24 10:30:46
        $date = Carbon::now();
        
        $fields['updatedDateTime'] = $date->getTimestamp();

        // Selecting the collection to use depending on whether the survey has an entity_id or not
        $collection_name = isset($survey->entity_id)?'entity_'.$survey->entity_id:'survey_results';

        $formExists = DB::collection($collection_name)->where(function($q) use ($survey_id){
            $q->where('form_id','=',$survey_id)
              ->orWhere('survey_id','=',$survey_id);
        })
                            ->where('userName','=',$user->id)
                            ->where(function($q) use ($primaryValues)
                            {
                                foreach($primaryValues as $key => $value)
            {
                $q->where($key, '=', $value);
            }
        })
        ->where('_id','!=',$responseId)
        ->get()->first();

        if (!empty($formExists)) {
            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Update Failure!!! Entry already exists with the same values.'],400);
        }
       

        $user_submitted = DB::collection($collection_name)
                            ->where('_id',$responseId)
                            ->where(function($q) use ($survey_id){
                                $q->where('form_id','=',$survey_id)
                                  ->orWhere('survey_id','=',$survey_id);
                            })
                            ->where('userName','=',$user->id);

        if($user_submitted->first()['isDeleted'] === true) {
            return response()->json([
                'status' => 'error',
                'data' => '',
                'message' => 'Response cannot be updated as it has been deleted!'
            ]);
        }

        // Function defined below, it queries the collection $collection_name using the parameters
        if(!isset($survey->entity_id)) {
            
            $fields['form_id']=$survey_id;
            // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
            
            if(isset($user_submitted->first()['submit_count'])) {

                $fields['submit_count']= $user_submitted->first()['submit_count']+1;   
            } 
            
            $user_submitted->update($fields);
            $data['form_title'] = $this->generateFormTitle($survey_id,$responseId,'survey_results');
        } else {

            $fields['survey_id']=$survey_id;

            $user_submitted->update($fields);
                            
            $data['form_title'] = $this->generateFormTitle($survey_id,$responseId,'entity_'.$survey->entity_id);
        }

        $data['_id']['$oid'] = $responseId;
        $data['createdDateTime'] = $user_submitted->first()['createdDateTime'];
        $data['updatedDateTime'] = $fields['updatedDateTime'];

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);
    }


    public function isStructureCompleted($user_id,$structure_code){
        $structure = StructureTracking::where('userName', $user_id)
                        ->where('structure_code', $structure_code)
                        ->where('isDeleted','!=',true)->first();
        if($structure !== null){
            return $structure->status == 'completed' ? true: false;
        }else{
            return false;
        }  
    }


    public function machineMeterReading($survey_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        $machine_code = $this->request->input('machine_code');
        $work_date = $this->request->input('reporting_date');
        //validation check to see if record exists in machine non_utilization
         $machine_non_utilized= $this->checkMachineNonUtilized($user->id,$machine_code,$work_date);
         if($machine_non_utilized){
             return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter meter reading.'],400); 
         }

        $userLocation = $this->request->user()->location;  
        
        $userRole = $this->request->user()->role_id;  
        $userRoleLocation = ['role_id' => $userRole];
        $userRoleLocation = array_merge($userRoleLocation,$userLocation);

        $roleConfig = RoleConfig::where('role_id',$userRole)->first();

        $survey = Survey::find($survey_id);
        $primaryKeys = isset($survey->form_keys)?$survey->form_keys:[];

        $fields = array();
        
        $fields['userName'] = $user->id;
        $fields['isDeleted'] = false;
        $fields['jurisdiction_type_id'] = $roleConfig->jurisdiction_type_id;
        $fields['user_role_location'] = $userRoleLocation;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }       

        // Gives current date and time in the format :  2019-01-24 10:30:46
        $date = Carbon::now();
        
        $fields['submit_count'] = 1;
        $fields['updatedDateTime'] = $date->getTimestamp();
        $fields['createdDateTime'] = $date->getTimestamp();


        if($survey->entity_id == null) {
            $collection_name = 'survey_results';
            $fields['form_id'] = $survey_id;

                // 'getUserResponse' function defined below, it queries the collection $collection_name using the parameters
                // $user->id,$survey_id,$primaryValues and returns the results
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);

                 // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
                // else an insert occurs and 'submit_count' gets value 1
                if(!empty($user_submitted)){
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this machine, please change values and try again.'],400);
                } else {
                    $form = DB::collection('survey_results')->insertGetId($fields);
                    $data['_id'] = $form;
                }
        } else {
            $collection_name = 'entity_'.$survey->entity_id;
            $fields['survey_id'] = $survey_id;

            $entity = Entity::find($survey->entity_id);

                unset($fields['submit_count']);
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                
                if(!empty($user_submitted)){
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this machine, please change values and try again.'],400);
                } else {                    
                    $form = DB::collection('entity_'.$survey->entity_id)->insertGetId($fields);
                    $data['_id'] = $form;
                }

        }    

        $data['form_title'] = $this->generateFormTitle($survey_id,$data['_id'],$collection_name);
        $data['createdDateTime'] = $fields['createdDateTime'];
        $data['updatedDateTime'] = $fields['updatedDateTime'];

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }

    public function updateMachineMeterReading($survey_id,$responseId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        //validation check to see if structure is completed
        $structure_code = $this->request->input('structure_code');
        if($this->isStructureCompleted($user->id,$structure_code)){
            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Structure status is completed.'],400);
        }


        $machine_code = $this->request->input('machine_code');
        $work_date = $this->request->input('reporting_date');
        //validation check to see if record exists in machine non_utilization
        $machine_non_utilized= $this->checkMachineNonUtilized($user->id,$machine_code,$work_date);
        if($machine_non_utilized){
            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter meter reading.'],400); 
        }

        $survey = Survey::find($survey_id);
        $primaryKeys = $survey->form_keys;

        $fields = array();
        
        $fields['userName']=$user->id;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }        

        // Gives current date and time in the format :  2019-01-24 10:30:46
        $date = Carbon::now();
        
        $fields['updatedDateTime'] = $date->getTimestamp();

        // Selecting the collection to use depending on whether the survey has an entity_id or not
        $collection_name = isset($survey->entity_id)?'entity_'.$survey->entity_id:'survey_results';

        $formExists = DB::collection($collection_name)->where(function($q) use ($survey_id){
            $q->where('form_id','=',$survey_id)
              ->orWhere('survey_id','=',$survey_id);
        })
                            ->where('userName','=',$user->id)
                            ->where(function($q) use ($primaryValues)
                            {
                                foreach($primaryValues as $key => $value)
            {
                $q->where($key, '=', $value);
            }
        })
        ->where('_id','!=',$responseId)
        ->get()->first();

        if (!empty($formExists)) {
            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Update Failure!!! Entry already exists with the same values.'],400);
        }
       

        $user_submitted = DB::collection($collection_name)
                            ->where('_id',$responseId)
                            ->where(function($q) use ($survey_id){
                                $q->where('form_id','=',$survey_id)
                                  ->orWhere('survey_id','=',$survey_id);
                            })
                            ->where('userName','=',$user->id);

        if($user_submitted->first()['isDeleted'] === true) {
            return response()->json([
                'status' => 'error',
                'data' => '',
                'message' => 'Response cannot be updated as it has been deleted!'
            ]);
        }

        // Function defined below, it queries the collection $collection_name using the parameters
        if(!isset($survey->entity_id)) {
            
            $fields['form_id']=$survey_id;
            // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
            
            if(isset($user_submitted->first()['submit_count'])) {

                $fields['submit_count']= $user_submitted->first()['submit_count']+1;   
            } 
            
            $user_submitted->update($fields);
            $data['form_title'] = $this->generateFormTitle($survey_id,$responseId,'survey_results');
        } else {

            $fields['survey_id']=$survey_id;

            $user_submitted->update($fields);
                            
            $data['form_title'] = $this->generateFormTitle($survey_id,$responseId,'entity_'.$survey->entity_id);
        }

        $data['_id']['$oid'] = $responseId;
        $data['createdDateTime'] = $user_submitted->first()['createdDateTime'];
        $data['updatedDateTime'] = $fields['updatedDateTime'];

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);
    }


    
    public function farmerSiltTransportation($survey_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        $machine_code = $this->request->input('machine_code');
        $work_date = $this->request->input('reporting_date');
        //validation check to see if record exists in machine non_utilization
         $machine_non_utilized= $this->checkMachineNonUtilized($user->id,$machine_code,$work_date);
         if($machine_non_utilized){
             return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter silt transportation.'],400); 
         }

        $userLocation = $this->request->user()->location;  
        
        $userRole = $this->request->user()->role_id;  
        $userRoleLocation = ['role_id' => $userRole];
        $userRoleLocation = array_merge($userRoleLocation,$userLocation);

        $roleConfig = RoleConfig::where('role_id',$userRole)->first();

        $survey = Survey::find($survey_id);
        $primaryKeys = isset($survey->form_keys)?$survey->form_keys:[];

        $fields = array();
        
        $fields['userName'] = $user->id;
        $fields['isDeleted'] = false;
        $fields['jurisdiction_type_id'] = $roleConfig->jurisdiction_type_id;
        $fields['user_role_location'] = $userRoleLocation;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }       

        // Gives current date and time in the format :  2019-01-24 10:30:46
        $date = Carbon::now();
        
        $fields['submit_count'] = 1;
        $fields['updatedDateTime'] = $date->getTimestamp();
        $fields['createdDateTime'] = $date->getTimestamp();


        if($survey->entity_id == null) {
            $collection_name = 'survey_results';
            $fields['form_id'] = $survey_id;

                // 'getUserResponse' function defined below, it queries the collection $collection_name using the parameters
                // $user->id,$survey_id,$primaryValues and returns the results
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);

                 // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
                // else an insert occurs and 'submit_count' gets value 1
                if(!empty($user_submitted)){
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this structure, please change values and try again.'],400);
                } else {
                    $form = DB::collection('survey_results')->insertGetId($fields);
                    $data['_id'] = $form;
                }
        } else {
            $collection_name = 'entity_'.$survey->entity_id;
            $fields['survey_id'] = $survey_id;

            $entity = Entity::find($survey->entity_id);

                unset($fields['submit_count']);
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                
                if(!empty($user_submitted)){
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Data already have been created for this structure, please change values and try again.'],400);
                } else {                    
                    $form = DB::collection('entity_'.$survey->entity_id)->insertGetId($fields);
                    $data['_id'] = $form;
                }

        }    

        $data['form_title'] = $this->generateFormTitle($survey_id,$data['_id'],$collection_name);
        $data['createdDateTime'] = $fields['createdDateTime'];
        $data['updatedDateTime'] = $fields['updatedDateTime'];

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }

    public function updateFarmerSiltTransportation($survey_id,$responseId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();



        $machine_code = $this->request->input('machine_code');
        $work_date = $this->request->input('reporting_date');
        //validation check to see if record exists in machine non_utilization
        $machine_non_utilized= $this->checkMachineNonUtilized($user->id,$machine_code,$work_date);
        if($machine_non_utilized){
            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter silt transportation.'],400); 
        }

        $survey = Survey::find($survey_id);
        $primaryKeys = $survey->form_keys;

        $fields = array();
        
        $fields['userName']=$user->id;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }        

        // Gives current date and time in the format :  2019-01-24 10:30:46
        $date = Carbon::now();
        
        $fields['updatedDateTime'] = $date->getTimestamp();

        // Selecting the collection to use depending on whether the survey has an entity_id or not
        $collection_name = isset($survey->entity_id)?'entity_'.$survey->entity_id:'survey_results';

        $formExists = DB::collection($collection_name)->where(function($q) use ($survey_id){
            $q->where('form_id','=',$survey_id)
              ->orWhere('survey_id','=',$survey_id);
        })
                            ->where('userName','=',$user->id)
                            ->where(function($q) use ($primaryValues)
                            {
                                foreach($primaryValues as $key => $value)
            {
                $q->where($key, '=', $value);
            }
        })
        ->where('_id','!=',$responseId)
        ->get()->first();

        if (!empty($formExists)) {
            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Update Failure!!! Entry already exists with the same values.'],400);
        }
       

        $user_submitted = DB::collection($collection_name)
                            ->where('_id',$responseId)
                            ->where(function($q) use ($survey_id){
                                $q->where('form_id','=',$survey_id)
                                  ->orWhere('survey_id','=',$survey_id);
                            })
                            ->where('userName','=',$user->id);

        if($user_submitted->first()['isDeleted'] === true) {
            return response()->json([
                'status' => 'error',
                'data' => '',
                'message' => 'Response cannot be updated as it has been deleted!'
            ]);
        }

        // Function defined below, it queries the collection $collection_name using the parameters
        if(!isset($survey->entity_id)) {
            
            $fields['form_id']=$survey_id;
            // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
            
            if(isset($user_submitted->first()['submit_count'])) {

                $fields['submit_count']= $user_submitted->first()['submit_count']+1;   
            } 
            
            $user_submitted->update($fields);
            $data['form_title'] = $this->generateFormTitle($survey_id,$responseId,'survey_results');
        } else {

            $fields['survey_id']=$survey_id;

            $user_submitted->update($fields);
                            
            $data['form_title'] = $this->generateFormTitle($survey_id,$responseId,'entity_'.$survey->entity_id);
        }

        $data['_id']['$oid'] = $responseId;
        $data['createdDateTime'] = $user_submitted->first()['createdDateTime'];
        $data['updatedDateTime'] = $fields['updatedDateTime'];

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);
    }
	
    public function arraySearch($array, $key, $value)
    {
        
        $results = array();
        $innerLopCnt= 0;
        if (is_array($array)) {
           
            foreach ($array as $levelOne) {
                if(is_array($levelOne))
                {
                    
                    foreach ($levelOne as $loopkey =>$loopvaule) {
               
                        if($loopvaule == $key )
                        {
                           
                            $results[$innerLopCnt] = $levelOne;
                            $innerLopCnt = $innerLopCnt+1;
                        }
                    }
                }
            }
        }
       
        return $results;
    }
	public function staticJson()
	{
		$static = '{"pages":[{"name":"page1","elements":[{"type":"checkbox","name":"question1","title": "Types of TEA",
     "isRequired": true,
     "validators": [
      {
       "type": "expression",
       "expression": "{question1} notempty"
      }
     ],
     "hasOther": true,
     "otherPlaceHolder": "Your favorite Tea",
     "choices": [
      {
       "value": "item1",
       "text": "Dark Tea"
      },
      {
       "value": "item2",
       "text": "Oolong Tea"
      },
      {
       "value": "item3",
       "text": "Green Tea"
      },
      {
       "value": "item4",
       "text": "White Tea"
      }
     ],
     "hasNone": true,
     "noneText": "None"
    },
    {
     "type": "checkbox",
     "name": "question2",
     "useDisplayValuesInTitle": false,
     "title": "Select only 2 options",
     "isRequired": true,
     "validators": [
      {
       "type": "answercount",
       "minCount": 2,
       "maxCount": 2
      }
     ],
     "choices": [
      {
       "value": "item1",
       "text": "A"
      },
      {
       "value": "item2",
       "text": "B"
      },
      {
       "value": "item3",
       "text": "C"
      },
      {
       "value": "item4",
       "text": "D"
      }
     ],
     "otherErrorText": "Please select any 2 option"
    },
    {
     "type": "radiogroup",
     "name": "question3",
     "title": "Select Gender",
     "choices": [
      {
       "value": "item1",
       "text": "MALE"
      },
      {
       "value": "item2",
       "text": "FEMALE"
      },
      {
       "value": "item3",
       "text": "OTHER"
      }
     ]
    },
    {
     "type": "dropdown",
     "name": "question4",
     "title": "Select options from drop-down & give the ratting",
     "choices": [
      {
       "value": "item1",
       "text": "SMF"
      },
      {
       "value": "item2",
       "text": "MV"
      },
      {
       "value": "item3",
       "text": "BJS"
      }
     ]
    },
    {
     "type": "rating",
     "name": "question5",
     "title": "Rating option",
     "correctAnswer": 3,
     "isRequired": true,
     "validators": [
      {
       "type": "expression"
      }
     ],
     "rateMin": 2
    },
    {
     "type": "matrix",
     "name": "question6",
     "columns": [
      "Column 1",
      "Column 2",
      "Column 3"
     ],
     "rows": [
      "Row 1",
      "Row 2"
     ]
    },
    {
     "type": "matrixdropdown",
     "name": "question7",
     "columns": [
      {
       "name": "Column 1"
      },
      {
       "name": "Column 2"
      },
      {
       "name": "Column 3"
      }
     ],
     "choices": [
      1,
      2,
      3,
      4,
      5
     ],
     "rows": [
      "Row 1",
      "Row 2"
     ]
    }
   ],
   "title": "Which Tea do you like?",
   "description": "if other please specify"
  }
 ]
}';
// return $static;
$arr = array(
'status'=>'200',
'data' => $static, 
'message'=>'success'
);
 return response()->json($arr);
	}
	

}

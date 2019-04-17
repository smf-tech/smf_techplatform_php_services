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

class SurveyController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
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
        $data = Survey::select('_id','name','active','editable','multiple_entry','category_id','microservice_id','project_id','entity_id','assigned_roles','created_at')
        ->with('microservice','project','category','entity')
        ->where('assigned_roles','=',$user->role_id)->orderBy('created_at')->get();

        foreach($data as $row)
        {
            // unset() removes the element from the 'row' object
            unset($row->category_id);
            unset($row->microservice_id);
            unset($row->project_id);
            unset($row->entity_id);
            unset($row->assigned_roles);

			if (is_object($row['microservice'])) {
				$microService = clone $row['microservice'];
				$microService->route = $microService->route . '/' . $row->id;
				unset($row['microservice']);
				$row['microservice'] = $microService;
			}
        }

        return response()->json(['status'=>'success','data' => $data,'message'=>'']);
    }


    public function getSurveyDetails($survey_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        // Obtaining '_id','name','json', active','editable','multiple_entry','category_id','microservice_id','project_id','entity_id','assigned_roles','form_keys' of a Survey
        // alongwith corresponding details of 'microservice','project','category','entity'
        $data = Survey::with('microservice')->with('project')
        ->with('category')->with('entity')        
        ->select('category_id','microservice_id','project_id','entity_id','assigned_roles','_id','name','json','active','editable','multiple_entry','form_keys')
        ->find($survey_id);

        // unset() removes the element from the 'row' object
        unset($data->category_id);
        unset($data->microservice_id);
        unset($data->project_id);
        unset($data->entity_id);

        if (isset($data['microservice'])) {
            $data['microservice']->route = $data['microservice']->route . '/' . $survey_id;
        }
        
        // json_decode function takes a JSON string and converts it into a PHP variable
        $data->json = json_decode($data->json,true);
        return response()->json(['status'=>'success','data' => $data,'message'=>'']);
    }

    public function createResponse($survey_id)
    {
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
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Entry already exists with the same values.'],400);
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
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Entry already exists with the same values.'],400);
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
        return $response;   
    }

    public function showResponse($survey_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();
        $userLocation = $this->request->user()->location;
        $survey = Survey::find($survey_id);

        $limit = (int)$this->request->input('limit') ?:50;
        $offset = $this->request->input('offset') ?:0;
        $order = $this->request->input('order') ?:'desc';
        $field = $this->request->input('field') ?:'createdDateTime';
        $page = $this->request->input('page') ?:1;
        $endDate = $this->request->input('start_date') ?:Carbon::now('Asia/Calcutta')->getTimestamp();
        $startDate = $this->request->input('end_date') ?:Carbon::now('Asia/Calcutta')->subMonth()->getTimestamp();

        if(!isset($survey->entity_id)) {
            $collection_name = 'survey_results';
            $surveyResults = DB::collection('survey_results')
                                ->where('form_id','=',$survey_id)
                                ->where('userName','=',$user->id)
                                ->where('isDeleted','!=',true)
                                ->whereBetween('createdDateTime',array($startDate,$endDate))
                                ->where(function ($q) use ($userLocation) {
                                    foreach ($userLocation as $level => $value) {
                                        $q->whereIn('user_role_location.'.$level,$value);
                                    }
                                })
                                ->orderBy($field,$order)
                                ->paginate($limit);
        } else {    
            $collection_name = 'entity_'.$survey->entity_id;           
            $surveyResults = DB::collection('entity_'.$survey->entity_id)
                                ->where('survey_id','=',$survey_id)
                                ->where('userName','=',$user->id)
                                ->where('isDeleted','!=',true)
                                ->whereBetween('createdDateTime',array($startDate,$endDate))
                                ->where(function ($q) use ($userLocation) {
                                    foreach ($userLocation as $level => $value) {
                                        $q->whereIn('user_role_location.'.$level,$value);
                                    }
                                })
                                ->orderBy($field,$order)
                                ->paginate($limit);
        }           

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
                            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Some Entries already exists with the same values.'],400);
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
                            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Entry already exists with the same values.'],400);
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

        $aggregateResults = DB::collection('aggregate_associations')
        ->where('form_id','=',$survey_id)
        ->where(function($q) use ($userLocation) {
            foreach ($userLocation as $level => $location) {
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
        foreach($aggregateResults as &$aggregateResult)
        {
            $associated_results = $this->getAssociatedDocuments($aggregateResult['children'],$collection_name,$user->id);
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

    public function getAssociatedDocuments($children,$collection_name,$user_id){
        $results = DB::collection($collection_name)
                                ->where('userName','=',$user_id)
                                ->where('isDeleted','!=',true)
                                ->whereIn('_id',$children)
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
                            return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Entry already exists with the same values.'],400);
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
    
    public function checkMachineNonUtilized($user_id,$machine_code,$work_date){
        $entity = Entity::where('Name', '=', 'machinenonutilization')->first();
        $collection_name = 'entity_'.$entity->id;
        $response = DB::collection($collection_name)->where('userName','=',$user_id)
                                                  ->where('isDeleted','=',false)
                                                  ->where('machine_code','=',$machine_code)
                                                  ->where('reporting_date','=',$work_date)
                                                  ->get()->first();
        if(empty($response)){
            return false;
        }else{
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
             return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Machine is not utilized and you can’t enter work hour record for this machine.'],400); 
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
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Entry already exists with the same values.'],400);
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
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Entry already exists with the same values.'],400);
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

    public function updateSiltTransportation($survey_id,$groupId)
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

}

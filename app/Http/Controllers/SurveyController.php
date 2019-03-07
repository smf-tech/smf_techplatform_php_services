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
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Validator;
use Illuminate\Support\Facades\Input;
use \DateTime;

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
        // $responseId = $this->request->input('responseId');
        
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
        
        // foreach($date as $key=>$value)
        // {
        //     if($key == 'date')
        //         $dateTime = $value;
        // }
                
        $fields['updatedDateTime'] = $date->format('Y-m-d H:i:s.u'); 

        // Selecting the collection to use depending on whether the survey has an entity_id or not
        $collection_name = isset($survey->entity_id)?'entity_'.$survey->entity_id:'survey_results';

        $formExists = DB::collection($collection_name)->where('form_id','=',$survey_id)
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
                            ->where('form_id','=',$survey_id)
                            ->where('userName','=',$user->id);


        // Function defined below, it queries the collection $collection_name using the parameters
        if($survey->entity_id == null)
        {
            $fields['form_id']=$survey_id;
            // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
            if(isset($user_submitted->first()['submit_count'])){
                $fields['submit_count']= $user_submitted->first()['submit_count']+1;   
            } 
            $user_submitted->update($fields);

            $data['form_title'] = $this->generateFormTitle($survey_id,$responseId,'survey_results');
        }
        else
        {
            $fields['survey_id']=$survey_id;

            $user_submitted->update($fields);
                            
            $data['form_title'] = $this->generateFormTitle($survey_id,$responseId,'entity_'.$survey->entity_id);
        }        
        
        $dtime = DateTime::createFromFormat('Y-m-d H:i:s.u',$user_submitted->first()['createdDateTime']);
        // $timestamp = $dtime->getTimestamp();

        $data['_id']['$oid'] = $responseId;
        $data['createdDateTime'] = $dtime->getTimestamp();
        $data['updatedDateTime'] = $date->getTimestamp();        

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

        $survey = Survey::find($survey_id);
        $primaryKeys = isset($survey->form_keys)?$survey->form_keys:[];

        $fields = array();
        
        $fields['userName'] = $user->id;

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
        
        // foreach($date as $key=>$value)
        // {
        //     if($key == 'date')
        //         $dateTime = $value;
        // }
        $fields['submit_count'] = 1;
        $fields['updatedDateTime'] = $date->format('Y-m-d H:i:s.u'); 
        $fields['createdDateTime'] = $date->format('Y-m-d H:i:s.u'); 


        if($survey->entity_id == null)
        {
            $collection_name = 'survey_results';
            $fields['form_id'] = $survey_id;

            // if(!empty($primaryValues)){
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
            // } else {
            //     $form = DB::collection('survey_results')->insertGetId($fields);
			//     $data['_id'] = $form;
            // }
        } else {
            $collection_name = 'entity_'.$survey->entity_id;
            $fields['survey_id'] = $survey_id;

            $entity = Entity::find($survey->entity_id);
            if ($entity !== null) {
                if (in_array(strtolower($entity->Name), ['structure', 'structure master', 'structuremaster'])) {
                    $collection_name = 'structure_masters';
                } elseif (in_array(strtolower($entity->Name), ['machine', 'machine master', 'machinemaster'])) {
                    $collection_name = 'machine_masters';
                }
            }

            // if(!empty($primaryValues)) {
                unset($fields['submit_count']);
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                
                if(!empty($user_submitted)){
                    return response()->json(['status'=>'error','metadata'=>[],'values'=>[],'message'=>'Insertion Failure!!! Entry already exists with the same values.'],400);
                } else {                    
                    $form = DB::collection('entity_'.$survey->entity_id)->insertGetId($fields);
					$data['_id'] = $form;
                }

            // } else {         
            //     $form = DB::collection('entity_'.$survey->entity_id)->insertGetId($fields);
			//     $data['_id'] = $form;
            // }

        }    

        $data['form_title'] = $this->generateFormTitle($survey_id,$data['_id'],$collection_name);
        $data['createdDateTime'] = $date->getTimestamp(); 
        $data['updatedDateTime'] = $date->getTimestamp();  

        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }

    public function getUserResponse($user_id,$survey_id,$primaryValues,$collection_name){
        $formKey = $collection_name == 'survey_results' ? 'form_id' : 'survey_id';
        $response = DB::collection($collection_name)->where($formKey,'=',$survey_id)
                                                  ->where('userName','=',$user_id)
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
        
        $survey = Survey::find($survey_id);

        $limit = (int)$this->request->input('limit') ?:50;
        $offset = $this->request->input('offset') ?:0;
        $order = $this->request->input('order') ?:'desc';
        $field = $this->request->input('field') ?:'createdDateTime';
        $page = $this->request->input('page') ?:1;

        // $eDate = $this->request->input('start_date') ?:Carbon::now('Asia/Calcutta');
        // // return $endDate->modify('-1 months');
        // $sDate = $this->request->input('end_date') ?:Carbon::now('Asia/Calcutta')->subMonth();

        if($this->request->filled('start_date')) {
            $startDate = $this->request->input('start_date');
        }
        else {
            $eDate = Carbon::now('Asia/Calcutta');
            foreach($eDate as $key=>$value)
            {
                if($key == 'date')
                    $endDate = $value;
            }
        }
        
        if($this->request->filled('end_date')) {
            $endDate = $this->request->input('end_date');
        }
        else {
            $sDate = Carbon::now('Asia/Calcutta')->subMonth();
            foreach($sDate as $key=>$value)
            {
                if($key == 'date')
                    $startDate = $value;
            }
        }

        if($survey->entity_id == null)
        {
            // $surveyResults = DB::collection('survey_results')
            //                     ->where('form_id','=',$survey_id)
            //                     ->where('user_id','=',$user->id)
            //                     ->orderBy($field,$order)
            //                     ->skip(1)->get();

            
            // $surveyResults = $surveyResults->toArray();

            // $currentItems = array_slice($surveyResults, $limit * ($page - 1), $limit);
            // $abc = new LengthAwarePaginator($currentItems, count($surveyResults), $limit, $page);
            // $abc = new LengthAwarePaginator($surveyResults, count($surveyResults), $limit, $page);
            // return $abc;
            $collection_name = 'survey_results';
            $surveyResults = DB::collection('survey_results')
                                ->where('form_id','=',$survey_id)
                                ->where('userName','=',$user->id)
                                ->whereBetween('createdDateTime',array($startDate,$endDate))
                                ->orderBy($field,$order)
                                ->paginate($limit);
        }
        else
        {    
            $collection_name = 'entity_'.$survey->entity_id;           
            $surveyResults = DB::collection('entity_'.$survey->entity_id)
                                ->where('survey_id','=',$survey_id)
                                ->where('userName','=',$user->id)
                                ->whereBetween('createdDateTime',array($startDate,$endDate))
                                ->orderBy($field,$order)
                                ->paginate($limit);
        }           

        //  $surveyResults->total();
        // return $surveyResults->lastPage();
        if ($surveyResults->count() === 0) {
            return response()->json(['status'=>'success','metadata'=>[],'values'=>[],'message'=>'']);
        }
        
        $createdDateTime = DateTime::createFromFormat('Y-m-d H:i:s.u',$surveyResults[0]['createdDateTime']);
        $updatedDateTime = DateTime::createFromFormat('Y-m-d H:i:s.u',$surveyResults[0]['updatedDateTime']);
        $responseCount = $surveyResults->count();
        $result = ['form'=>['form_id'=>$survey_id,'userName'=>$surveyResults[0]['userName'],'createdDateTime'=>$createdDateTime->getTimestamp(), 'updatedDateTime'=>$updatedDateTime->getTimestamp(),'submit_count'=>$responseCount]];

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
            $values[] = Arr::except($surveyResult,['survey_id','userName','createdDateTime']);
        }

        $result['Current page'] = 'Page '.$surveyResults->currentPage().' of '.$surveyResults->lastPage();
        $result['Total number of records'] = $surveyResults->total();
        // $result['Total number of pages'] = $surveyResults->lastPage();
        return response()->json(['status'=>'success','metadata'=>[$result],'values'=>$values,'message'=>'']);

    }
}

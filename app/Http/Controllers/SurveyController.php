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

class SurveyController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    public function updateSurvey($survey_id)
    {
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 

        $user = $this->request->user();

        $survey = Survey::find($survey_id);
        $primaryKeys = $survey->form_keys;

        $fields = array();           
       
        $fields['user_id']=$user->id;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->response as $key=>$value)
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
        
        // $date contains the key => value pairs:
        // date => 2019-01-24 10:33:00.851100
        // timezone_type => 3
        // timezone => UTC
        foreach($date as $key=>$value)
        {
            if($key == 'date')
                $dateTime = $value;
        }
                
        $fields['updated_at'] = $dateTime; 

        // Selecting the collection to use depending on whether the survey has an entity_id or not
        $collection_name = isset($survey->entity_id)?'entity_'.$survey->entity_id:'survey_results'; 

        // Function defined below, it queries the collection $collection_name using the parameters
        // $user->id,$survey_id,$primaryValues and returns the results
        $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
        
        if($survey->entity_id == null)
        {
            $fields['form_id']=$survey_id;
            // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
            if(isset($user_submitted)){
                $fields['submit_count']= $user_submitted['submit_count']+1;   
            } 
            DB::collection('survey_results')->where('form_id','=',$survey_id)
                                            ->where('user_id','=',$user->id)
                                            ->where(function($q) use ($primaryValues)
                                            {
                                                foreach($primaryValues as $key => $value)
                                                {
                                                    $q->where($key, '=', $value);
                                                }
                                            })
                                            ->update($fields,['upsert'=>true]); 
        }
        else
        {
            $fields['survey_id']=$survey_id;
            DB::collection('entity_'.$survey->entity_id)->where('survey_id','=',$survey_id)
                                                ->where('user_id','=',$user->id)
                                                ->where(function($q) use ($primaryValues)
                                                {
                                                    foreach($primaryValues as $key => $value)
                                                   {
                                                        $q->where($key, '=', $value);
                                                   }
                                                })
                                                ->update($fields,['upsert'=>true]);                     
        }

        return response()->json(['status'=>'success','message'=>'']);

    }

    public function getSurveys()
    {
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 

        $user = $this->request->user();

        // Obtaining '_id','name','active','editable','multiple_entry','category_id','microservice_id','project_id','entity_id','assigned_roles' of Surveys
        // alongwith corresponding details of 'microservice','project','category','entity'
        $data = Survey::select('_id','name','active','editable','multiple_entry','category_id','microservice_id','project_id','entity_id','assigned_roles')
        ->with('microservice','project','category','entity')
        ->where('assigned_roles','=',$user->role_id)->get(); 

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
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 

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
        
        // json_decode function takes a JSON string and converts it into a PHP variable
        $data->json = json_decode($data->json,true);
        return response()->json(['status'=>'success','data' => $data,'message'=>'']);
    }

    public function createResponse($survey_id)
    {
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 

        $user = $this->request->user();

        $survey = Survey::find($survey_id);
        $primaryKeys = isset($survey->form_keys)?$survey->form_keys:[];

        $fields = array();
        
        $fields['user_id'] = $user->id;

        $primaryValues = array();

        // Looping through the response object from the body
        foreach($this->request->response as $key=>$value)
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
        
        // $date contains the key => value pairs:
        // date => 2019-01-24 10:33:00.851100
        // timezone_type => 3
        // timezone => UTC
        foreach($date as $key=>$value)
        {
            if($key == 'date')
                $dateTime = $value;
        }
        $fields['submit_count'] = 1;
        $fields['updated_at'] = $dateTime;
        $fields['created_at'] = $dateTime;          


        if($survey->entity_id == null)
        {
            $collection_name = 'survey_results';
            $fields['form_id'] = $survey_id;

            if(!empty($primaryValues)){
                // 'getUserResponse' function defined below, it queries the collection $collection_name using the parameters
                // $user->id,$survey_id,$primaryValues and returns the results
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);

                 // If the set of values are present in the collection then an update occurs and 'submit_count' gets incremented
                // else an insert occurs and 'submit_count' gets value 1
                if(isset($user_submitted)){
                    $fields['submit_count']= $user_submitted['submit_count']+1;
                    DB::collection('survey_results')->where('form_id','=',$survey_id)
                    ->where('user_id','=',$user->id)
                    ->where(function($q) use ($primaryValues)
                    {
                        foreach($primaryValues as $key => $value)
                       {
                            $q->where($key, '=', $value);
                       }
                    })
                    ->update($fields);
                }else{
                    DB::collection('survey_results')->insert($fields);
                }
            }else{
            DB::collection('survey_results')->insert($fields);
            }
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

            if(!empty($primaryValues)){
                unset($fields['submit_count']);
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                if(isset($user_submitted)){
                    DB::collection('entity_'.$survey->entity_id)->where('survey_id','=',$survey_id)
                    ->where('user_id','=',$user->id)
                    ->where(function($q) use ($primaryValues)
                    {
                        foreach($primaryValues as $key => $value)
                       {
                            $q->where($key, '=', $value);
                       }
                    })
                    ->update($fields);
                }else{
                    DB::collection('entity_'.$survey->entity_id)->insert($fields);
                }

            }else{         
            DB::collection('entity_'.$survey->entity_id)->insert($fields);
            }
        }    
        
       
        return response()->json(['status'=>'success','message'=>'']);

    }

    public function getUserResponse($user_id,$survey_id,$primaryValues,$collection_name){
        $formKey = $collection_name == 'survey_results' ? 'form_id' : 'survey_id';
        $response = DB::collection($collection_name)->where($formKey,'=',$survey_id)
                                                  ->where('user_id','=',$user_id)
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
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 

        $user = $this->request->user();
        
        $survey = Survey::find($survey_id);

        if($survey->entity_id == null)
        {
            $surveyResults = DB::collection('survey_results')->where('form_id','=',$survey_id)->where('user_id','=',$user->id)->get();
        }
        else
        {               
            $surveyResults = DB::collection('entity_'.$survey->entity_id)->where('survey_id','=',$survey_id)->where('user_id','=',$user->id)->get();
        }           

        if ($surveyResults->count() === 0) {
            return response()->json(['status'=>'success','metadata'=>[],'values'=>[],'message'=>'']);
        }

        $result = ['form'=>['form_id'=>$survey_id,'user_id'=>$surveyResults[0]['user_id'],'created_at'=>$surveyResults[0]['created_at']]];

        $values = [];

        foreach($surveyResults as $surveyResult)
        {
            // Excludes values 'form_id','user_id','created_at','updated_at' from the $surveyResult array
            //  and stores it in values
            $values[] = Arr::except($surveyResult,['survey_id','user_id','created_at']);
        }

        return response()->json(['status'=>'success','metadata'=>$result,'values'=>$values,'message'=>'']);

    }
}

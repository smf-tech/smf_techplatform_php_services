<?php

namespace App\Http\Controllers;
use App\Survey;
use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\SurveyResult;
use App\Entity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
#use Teapot\StatusCode;
use Carbon\Carbon;
use MongoDB\BSON\UTCDateTime;
use DateTime;

//class SurveyController extends Controller implements StatusCode
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
        $user = $this->request->user();
      
        $organisation = Organisation::where('_id',$user->org_id)->get();
        
        $database = $organisation[0]->name.'_'.$user->org_id; 

        \Illuminate\Support\Facades\Config::set('database.connections.'.$database, array(
            'driver'    => 'mongodb',
            'host'      => '127.0.0.1',
            'database'  => $database,
            'username'  => '',
            'password'  => '',  
        ));

        DB::setDefaultConnection($database); 

        $survey = Survey::find($survey_id);
        $primaryKeys = $survey->form_keys;

        $fields = array();           
        $fields['survey_id']=$survey_id;
        $fields['user_id']=$user->id;

        $primaryValues = array();
        foreach($this->request->response as $key=>$value)
        {
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }        

        $date = Carbon::now();
        
        foreach($date as $key=>$value)
        {
            if($key == 'date')
                $dateTime = $value;
        }
        
        $fields['updated_at'] = $dateTime; 
        $collection_name = isset($survey->entity_id)?'entity_'.$survey->entity_id:'survey_results'; 
        $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
        //return $user_submitted['submit_count'];
        if(isset($user_submitted)){
            $fields['submit_count']= $user_submitted['submit_count']+1;   
        }  
        
        
        if($survey->entity_id == null)
        {
            DB::collection('survey_results')->where('survey_id','=',$survey_id)
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

    public function deleteSurvey($survey_id)
    {
        $user = $this->request->user();
      
        $organisation = Organisation::where('_id',$user->org_id)->get();
        
        $database = $organisation[0]->name.'_'.$user->org_id; 

        \Illuminate\Support\Facades\Config::set('database.connections.'.$database, array(
            'driver'    => 'mongodb',
            'host'      => '127.0.0.1',
            'database'  => $database,
            'username'  => '',
            'password'  => '',  
        ));
        DB::setDefaultConnection($database); 
        
        $data = DB::collection('surveys')->where('_id',$survey_id)->delete(); 
        return "success";
    }

    public function getSurveys()
    {
        $user = $this->request->user();
      
        $organisation = Organisation::where('_id',$user->org_id)->get();
        //return $user->role_id;
        $database = $organisation[0]->name.'_'.$user->org_id; 

        \Illuminate\Support\Facades\Config::set('database.connections.'.$database, array(
            'driver'    => 'mongodb',
            'host'      => '127.0.0.1',
            'database'  => $database,
            'username'  => '',
            'password'  => '',  
        ));
        DB::setDefaultConnection($database); 
        
        // $data = Survey::select('_id as formId','name as formName')->get(); 
        $data = Survey::select('_id','name','category_id','microservice_id','active','editable','multiple_entry','project_id','assigned_roles')->where('assigned_roles','=',$user->role_id)->get(); 
        return response()->json(['status'=>'success','data' => $data,'message'=>'']);
    }


    public function getSurveyDetails($survey_id)
    {
        $user = $this->request->user();
      
        $organisation = Organisation::where('_id',$user->org_id)->get();
        
        $database = $organisation[0]->name.'_'.$user->org_id; 

        \Illuminate\Support\Facades\Config::set('database.connections.'.$database, array(
            'driver'    => 'mongodb',
            'host'      => '127.0.0.1',
            'database'  => $database,
            'username'  => '',
            'password'  => '',  
        ));
        DB::setDefaultConnection($database); 
        
        $data = Survey::where('_id',$survey_id)->get();
        return response()->json(['status'=>'success','data' => $data,'message'=>'']);
    }

    public function createResponse($survey_id)
    {
        $user = $this->request->user();
              
        $organisation = Organisation::where('_id',$user->org_id)->get();
        
        $database = $organisation[0]->name.'_'.$user->org_id; 

        \Illuminate\Support\Facades\Config::set('database.connections.'.$database, array(
            'driver'    => 'mongodb',
            'host'      => '127.0.0.1',
            'database'  => $database,
            'username'  => '',
            'password'  => '',  
        ));

        DB::setDefaultConnection($database); 

        $survey = Survey::find($survey_id);
        $primaryKeys = isset($survey->form_keys)?$survey->form_keys:[];

        $fields = array();
        $fields['survey_id'] = $survey_id;
        $fields['user_id'] = $user->id;

        $primaryValues = array();
        foreach($this->request->response as $key=>$value)
        {
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $fields[$key] = $value;
        }       

        $date = Carbon::now();    
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
            if(!empty($primaryValues)){
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                //return $user_submitted['submit_count'];
                if(isset($user_submitted)){
                    $fields['submit_count']= $user_submitted['submit_count']+1;
                    DB::collection('survey_results')->where('survey_id','=',$survey_id)
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
        }
        else
        { 
            $collection_name = 'entity_'.$survey->entity_id;
            if(!empty($primaryValues)){
                $user_submitted = $this->getUserResponse($user->id,$survey_id,$primaryValues,$collection_name);
                if(isset($user_submitted)){
                    $fields['submit_count']= $user_submitted['submit_count']+1;
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
        $response = DB::collection($collection_name)->where('survey_id','=',$survey_id)
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
        $user = $this->request->user();
              
        $organisation = Organisation::where('_id',$user->org_id)->get();
        
        $database = $organisation[0]->name.'_'.$user->org_id; 

        \Illuminate\Support\Facades\Config::set('database.connections.'.$database, array(
            'driver'    => 'mongodb',
            'host'      => '127.0.0.1',
            'database'  => $database,
            'username'  => '',
            'password'  => '',  
        ));

        DB::setDefaultConnection($database); 

        $survey = Survey::find($survey_id);

        // $fields = array();

        if($survey->entity_id == null)
        {
            $results = DB::collection('survey_results')->where('survey_id','=',$survey_id)->where('user_id','=',$user->id)->get();
        }
        else
        {               
            $results = DB::collection('entity_'.$survey->entity_id)->where('survey_id','=',$survey_id)->where('user_id','=',$user->id)->get();
            //$data = DB::collection('entity_'.$survey->entity_id)->where('survey_id','=',$survey_id)->select('')->get();
        }           
        #return $results[0];
        return response()->json(['status'=>'success','data' => $results,'message'=>'']);

    }
}

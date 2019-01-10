<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Organisation;
use App\State;
use App\StateJurisdiction;
use App\Jurisdiction;
use App\District;
use App\Taluka;
use App\Cluster;
use App\Village;

class LocationController extends Controller
{
   
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getstates(Request $request){
  
        $states=State::with('jurisdictions')->select('Name')->get();
        $response_data = [];
        foreach($states as $state){
            
            foreach($state->jurisdictions as $jurisdiction){
                
                $jurisdiction_instance = Jurisdiction::find($jurisdiction->jurisdiction_id);
                $jurisdiction->levelName = $jurisdiction_instance->levelName;
            }
            $response_data[] = $state;
        }
        $response_data = array('status' =>'success','data' => $response_data,'message'=>'');
        return response()->json($response_data); 
    }

    public function getleveldata($state_id,$level,Request $request){
        $jurisdiction = StateJurisdiction::where([['state_id',$state_id],['level',(int)$level]])->get()->first();
        $data = [];
        if($jurisdiction){
            $jurisdiction_instance = Jurisdiction::where('_id',$jurisdiction->jurisdiction_id)->get()->first();
            switch($jurisdiction_instance->levelName){
                case 'District':
                $list = District::where('state_id',$state_id)->get();
                break;
                case 'Taluka':
                $list = Taluka::where('state_id',$state_id)->get();
                break;
                case 'Cluster':
                $list = Cluster::where('state_id',$state_id)->get();
                break;
                case 'Village':
                $list = Village::where('state_id',$state_id)->get();
                break;
                default:
                $list = [];

            }
            $data['levelName'] = $jurisdiction_instance->levelName;
            $data['list'] = $list;
            $response_data = array('status' =>'success','data' => $data,'message'=>'');
            return response()->json($response_data); 
        }else{
            return response()->json([],404); 
        }
        
    }

}
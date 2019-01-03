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
  
        $states=State::with('jurisdictions')->get(['Name']);
        $response_data = array('status' =>'success','data' => $states,'message'=>'');
        return response()->json($response_data); 
    }

    public function getleveldata($state_id,$level,Request $request){
        $jurisdiction = StateJurisdiction::where([['state_id',$state_id],['level',(int)$level]])->get()->first();
        if($jurisdiction){
            $jurisdiction_instance = Jurisdiction::where('_id',$jurisdiction->jurisdiction_id)->get()->first();
            switch($jurisdiction_instance->levelName){
                case 'District':
                $data = District::where('state_id',$state_id)->get();
                break;
                case 'Taluka':
                $data = Taluka::where('state_id',$state_id)->get();
                break;
                case 'Cluster':
                $data = Cluster::where('state_id',$state_id)->get();
                break;
                case 'Village':
                $data = Village::where('state_id',$state_id)->get();
                break;
                default:
                $data = [];

            }
            $response_data = array('status' =>'success','data' => $data,'message'=>'');
            return response()->json($response_data); 
        }else{
            return response()->json([],404); 
        }
        
    }

}
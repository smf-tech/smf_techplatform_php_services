<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use App\StructureMaster;
use App\District;
use App\Taluka;
use App\Village;

class StructureMasterController extends Controller
{
    use Helpers;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get()
    {
        try {
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
            return response()->json([
                'status' => 'success',
                'data' => StructureMaster::all('structure_code'),
                'message' => 'List of Structure codes.'
            ]);
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

    public function structureCreate(){
        try {
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }

            $department_abbr = array('water_resources_department'=>'WRD','forest'=>'FST',
                                     'agriculture'=>'AGR','minor_irrigation(ZP)'=>'MIZP',
                                     'soil_and_water_conservation'=>'SWC',
                                     'irrigation_department'=>'IRG'
                                    );
            $struct_abbr = array('cct'=>'CCT','deep_cct'=>'DCCT','nala'=>'NALA',
                                 'talav'=>'TLAV','dam'=>'DAM','canal'=>'CANL','mnb'=>'MNB','cnb'=>'CNB');
            
            $district = District::find($this->request->input('district_id'));
            $taluka= Taluka::find($this->request->input('taluka_id'));
            $village= Village::find($this->request->input('village_id'));
            $department_code = $department_abbr[$this->request->input('structure_owner_department')];
            $structuretype_code = $struct_abbr[$this->request->input('type')];
          
            $structures = StructureMaster::where('structure_code','LIKE',$district->abbr.'/'.$taluka->abbr.'/'.$village->name.'/'.$department_code.'/'.$structuretype_code.'%')->max('structure_code');
            if($structures){
                $numberoffset = strlen($district->abbr.'/'.$taluka->abbr.'/'.$village->name.'/'.$department_code.'/'.$structuretype_code);
                $queueValue = substr($structures ,$numberoffset)+1;
            }else{
                $queueValue = 1;  
            }
            $structure_code = $district->abbr.'/'.$taluka->abbr.'/'.$village->name.'/'.$department_code.'/'.$structuretype_code.$queueValue;
            return $structure_code ;
            return response()->json([
                'status' => 'success',
                'data' => StructureMaster::all('structure_code'),
                'message' => 'List of Structure codes.'
            ]);
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
}

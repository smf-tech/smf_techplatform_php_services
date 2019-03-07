<?php

namespace App\Http\Controllers;

use App\MachineTracking;
use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Entity;
use App\Microservice;
use App\ShiftingRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\MachineMou;
use App\Survey;


use App\Images;

class MachineTrackingController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    public function machineDeploy($form_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        try {
            $primaryKeys = \App\Survey::find($form_id)->form_keys;
            $data = $this->request->all();
            $primaryValues = array();
            
            if(count($primaryKeys)> 0){
                // Looping through the response object from the body
                foreach($this->request->all() as $key=>$value)
                {
                    // Checking if the key is marked as a primary key and storing the value 
                    // in primaryValues if it is
                    if(in_array($key,$primaryKeys))
                    {
                        if($key == 'village' ){
                            $primaryValues[$key.'_id'] = $value;
                        }else{
                        $primaryValues[$key] = $value;
                        }
                    }

                }       

                $machine_deployed = DB::collection('machine_tracking')
                ->where('form_id','=',$form_id)
                ->where('userName','=',$this->request->user()->id)
                ->where(function($q) use ($primaryValues)
                {
                    foreach($primaryValues as $key => $value)
                {
                        $q->where($key, '=', $value);
                }
                })->get()->first();
                if($machine_deployed != null){
                    return response()->json(
                        [
                        'status' => 'error',
                        'data' => null,
                        'message' => 'Machine already deployed please change parameters'
                    ],
                    400
                    );
                }
            }

            $deployedMachine = new MachineTracking;
            $deployedMachine->village()->associate(\App\Village::find($this->request->village));
            
            $deployedMachine->date_deployed = $this->request->date_deployed;
            $deployedMachine->structure_code = $this->request->structure_code;
            $deployedMachine->machine_code = $this->request->machine_code;
            $deployedMachine->deployed = true;
            $deployedMachine->status = $this->request->status;
            
            if($this->request->filled('last_deployed')) {
                $deployedMachine->last_deployed = $this->request->last_deployed;
            }
            
            $deployedMachine->userName = $this->request->user()->id;
            $deployedMachine->form_id = $form_id;
            $deployedMachine->save();

            return $deployedMachine;
            
            $result = [
                '_id' => [
                    '$oid' => $deployedMachine->id
                ],
                'form_title' => $this->generateFormTitle($form_id,$deployedMachine->id,'machine_tracking'),
                'createdDateTime' => isset($deployedMachine->createdDateTime)? $deployedMachine->createdDateTime->getTimeStamp(): $deployedMachine->created_at->getTimeStamp(),
                'udpatedDateTime' => isset($deployedMachine->udpatedDateTime)? $deployedMachine->udpatedDateTime->getTimeStamp(): $deployedMachine->updated_at->getTimeStamp() 
            ]; 
            
            return response()->json(['status'=>'success','data'=>$result,'message'=>'']);
        }catch(\Exception $exception) {
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

    public function updateDeployedMachine($machine_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        try {
            $deployedMachine = MachineTracking::find($machine_id);

            if ($deployedMachine !== null) {
                if($this->request->village != $deployedMachine->village_id){
                    $deployedMachine->village()->dissociate();
                    $deployedMachine->village()->associate(\App\Village::find($this->request->village));
                }
                $deployedMachine->date_deployed = $this->request->date_deployed;
                $deployedMachine->structure_code = $this->request->structure_code;
                $deployedMachine->machine_code = $this->request->machine_code;
                $deployedMachine->deployed = true;
                $deployedMachine->status = $this->request->status;
                $deployedMachine->last_deployed = $this->request->last_deployed;
                $deployedMachine->userName = $this->request->user()->id;
                $deployedMachine->save();
    
                $result = [
                    '_id' => [
                        '$oid' => $deployedMachine->id
                    ],
                    'form_title' => $this->generateFormTitle($deployedMachine->form_id,$deployedMachine->id,'machine_tracking'),
                    'createdDateTime' => isset($deployedMachine->createdDateTime)? $deployedMachine->createdDateTime->getTimeStamp(): $deployedMachine->created_at->getTimeStamp(),
                    'udpatedDateTime' => isset($deployedMachine->udpatedDateTime)? $deployedMachine->udpatedDateTime->getTimeStamp(): $deployedMachine->updated_at->getTimeStamp() 
                ]; 
                
                return response()->json(['status'=>'success','data'=>$result,'message'=>'']);

            }
            return response()->json(
                [
                    'status' => 'error',
                    'data' => null,
                    'message' => 'Record not found'
                ],
                404
            );

        }catch(\Exception $exception) {
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

    public function getDeploymentInfo()
    {
		try {
			if (!$this->request->filled('deployed')) {
                return response()->json(
                        [
                        'status' => 'error',
                        'data' => null,
                        'message' => 'deployed parameter is missing'
                    ],
                    400
                );
            }
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$userLocation = $this->request->user()->location;
			$machines = [];
			if (isset($userLocation['village']) && !empty($userLocation['village'])) {
				if ($this->request->deployed === 'true') {
					$machines = MachineTracking::where('deployed',true)->whereIn('village_id', $userLocation['village'])->with('village')->get();
				} else {
					$machineCodes = [];
					$machineLevels = ['state', 'district', 'taluka'];
					$machineTrackingRecords = MachineTracking::whereIn('village_id', $userLocation['village'])->get();
					$machineTrackingRecords->each(function($machineTracking, $key) {
						$machineCodes[] = $machineTracking->machine_code;
					});
					$machineRecords = \App\MachineMaster::whereNotIn('machine_code', $machineCodes);
					foreach ($userLocation as $level => $location) {
						if (in_array($level, $machineLevels)) {
							$machineRecords->whereIn($level . '_id', $location);
						}
					}
					$machines = $machineRecords->get();
				}
			}
			return response()->json([
				'status' => 'success',
				'data' => $machines,
				'message' => 'List of machines.'
			]);
		} catch(\Exception $exception) {
			return response()->json([
                        'status' => 'error',
                        'data' => null,
                        'message' => $exception->getMessage()
                    ], 400);
		}
    }

    public function machineShift($form_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        try {
            $primaryKeys = \App\Survey::find($form_id)->form_keys;
            $data = $this->request->all();
            $primaryValues = array();
            
            if(count($primaryKeys)> 0){
                // Looping through the response object from the body
                foreach($this->request->all() as $key=>$value)
                {
                    // Checking if the key is marked as a primary key and storing the value 
                    // in primaryValues if it is
                    if(in_array($key,$primaryKeys))
                    {
                        if($key == 'moved_from_village'  || $key == 'moved_to_village' ){
                            $primaryValues[$key.'_id'] = $value;
                        }else{
                        $primaryValues[$key] = $value;
                        }
                    }

                }       

                $machine_shifted = DB::collection('shifting_records')
                ->where('form_id','=',$form_id)
                ->where('userName','=',$this->request->user()->id)
                ->where(function($q) use ($primaryValues)
                {
                    foreach($primaryValues as $key => $value)
                {
                        $q->where($key, '=', $value);
                }
                })->get()->first();
                if($machine_shifted != null){
                    return response()->json(
                        [
                        'status' => 'error',
                        'data' => null,
                        'message' => 'Machine already shifted please change parameters'
                    ],
                    400
                    );
                }
            }

            $data = $this->request->all();

            $machine = MachineTracking::firstOrCreate([
                'village_id' => $this->request->moved_from_village,
                'structure_code' => $this->request->old_structure_code,
                'machine_code' => $this->request->machine_code
            ]);
            
            $data['userName']= $this->request->user()->id;
            $data['form_id']= $form_id;
            $shiftingRecord = ShiftingRecord::create($data);
            $shiftingRecord->movedFromVillage()->associate(\App\Village::find($data['moved_from_village']));
            $shiftingRecord->movedToVillage()->associate(\App\Village::find($data['moved_to_village']));
            $shiftingRecord->machineTracking()->associate($machine);
            $shiftingRecord->save();

            $shifting_id = $shiftingRecord->getIdAttribute();

            $machine->status = 'shifted';

            $machine->save();

            $result = [
                '_id' => [
                    '$oid' => $shifting_id
                ],
                'form_title' => $this->generateFormTitle($form_id,$shifting_id,'shifting_records'),
                'createdDateTime' => isset($machine->createdDateTime)? $machine->createdDateTime->getTimeStamp(): $machine->created_at->getTimeStamp(),
                'udpatedDateTime' => isset($machine->udpatedDateTime)? $machine->udpatedDateTime->getTimeStamp(): $machine->updated_at->getTimeStamp() 
            ]; 

            return response()->json(['status'=>'success','data'=>$result,'message'=>'']);
        }catch(\Exception $exception) {
			return response()->json([
                        'status' => 'error',
                        'data' => null,
                        'message' => $exception->getMessage()
                    ], 400);
		}
    }

    public function updateMachineShift($machine_shift_id){
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        try {
            $machine_shifted = ShiftingRecord::find($machine_shift_id);
            if($machine_shifted !== null){
                $data = $this->request->all();
                $machine_shifted->update($data);

                if($this->request->moved_from_village != $machine_shifted->moved_from_village_id){
                    $machine_shifted->movedFromVillage()->dissociate();
                    $machine_shifted->movedFromVillage()->associate(\App\Village::find($data['moved_from_village']));
                }
                if($this->request->moved_to_village != $machine_shifted->moved_to_village_id){
                    $machine_shifted->movedToVillage()->dissociate();
                    $machine_shifted->movedToVillage()->associate(\App\Village::find($data['moved_to_village']));
                }    
                $machine_shifted->save();   

                $result = [
                    '_id' => [
                        '$oid' => $machine_shifted->id
                    ],
                    'form_title' => $this->generateFormTitle($machine_shifted->form_id,$machine_shifted->id,'shifting_records'),
                    'createdDateTime' => isset($machine_shifted->createdDateTime)? $machine_shifted->createdDateTime->getTimeStamp(): $machine_shifted->created_at->getTimeStamp(),
                    'udpatedDateTime' => isset($machine_shifted->udpatedDateTime)? $machine_shifted->udpatedDateTime->getTimeStamp(): $machine_shifted->updated_at->getTimeStamp() 
                ]; 

                return response()->json(['status'=>'success','data'=>$result,'message'=>'']);
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => null,
                        'message' => 'Record not found'
                    ],
                    404
                );
            }

        }catch(\Exception $exception) {
			return response()->json([
                        'status' => 'error',
                        'data' => null,
                        'message' => $exception->getMessage()
                    ], 400);
		}
    }

    public function getShiftingInfo()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        return response()->json([
                                    'status'=>'success',
                                    'data'=> MachineTracking::where('status','shifted')
                                                            ->with('shiftingRecords', 'shiftingRecords.movedFromVillage', 'shiftingRecords.movedToVillage', 'village')
                                                            ->get(),
                                    'message'=>'']); 
    }

    public function machineMoU()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        
        $machine = MachineTracking::where('mou_details','!=',null)->get();
        
        return response()->json(['status'=>'success','data'=>$machine,'message'=>'']);    
    }

    public function createMachineMoU($formId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();
        $mouDetails = array();
        $primaryValues = array();

        $form = Survey::find($formId);
        $primaryKeys = $form->form_keys;

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $mouDetails[$key] = $value;
        }        

        $formExists = MachineMou::where('form_id','=',$formId)
                            ->where('userName','=',$user->id)
                            ->where(function($q) use ($primaryValues)
                            {
                                foreach($primaryValues as $key => $value) {
                                    $q->where($key, '=', $value);
                                }
                            })
                            ->first();

        if (!empty($formExists)) {
            return response()->json(['status'=>'error','data'=>'','message'=>'Insertion Failure!!! Entry already exists with the same values.'],400);
        }

        $mouDetails['form_id'] = $formId;
        $mouDetails['userName'] = $user->id;

        $machine = MachineMou::create($mouDetails);
        $data['_id']['$oid'] = $machine->id;
        $data['form_title'] = $this->generateFormTitle($formId,$machine->id,'machine_mou');
        $data['createdDateTime'] = isset($machine->createdDateTime)? $machine->createdDateTime->getTimeStamp() : $machine->created_at->getTimeStamp();
        $data['updatedDateTime'] = isset($machine->updatedDateTime)? $machine->updatedDateTime->getTimeStamp() : $machine->updated_at->getTimeStamp();

        return response()->json(['status'=>'success','data'=>$data,'message'=>'']); 
    }

    public function updateMachineMoU($recordId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        
        $user = $this->request->user();
        $mouDetails = array();
        $primaryValues = array();

        $mouRecord = MachineMou::find($recordId);
        $form = Survey::find($mouRecord->form_id);
        $primaryKeys = $form->form_keys;

        // Looping through the response object from the body
        foreach($this->request->all() as $key=>$value)
        {
            // Checking if the key is marked as a primary key and storing the value 
            // in primaryValues if it is
            if(in_array($key,$primaryKeys))
            {
                $primaryValues[$key] = $value;
            }
            $mouDetails[$key] = $value;
        }        

        $formExists = MachineMou::where('form_id','=',$mouRecord->form_id)
                            ->where('userName','=',$user->id)
                            ->where(function($q) use ($primaryValues)
                            {
                                foreach($primaryValues as $key => $value) {
                                    $q->where($key, '=', $value);
                                }
                            })
                            ->where('_id','!=',$recordId)
                            ->get()->first();

        if (!empty($formExists)) {
            return response()->json(['status'=>'error','data'=>'','message'=>'Update Failure!!! Entry already exists with the same values.'],400);
        }

        $machine = $mouRecord->update($mouDetails);
        $data['_id']['$oid'] = $recordId;
        $data['form_title'] = $this->generateFormTitle($mouRecord->form_id,$recordId,'machine_mou');
        $data['createdDateTime'] = isset($mouRecord->createdDateTime)? $mouRecord->createdDateTime->getTimeStamp() : $machine->created_at->getTimeStamp();
        $data['updatedDateTime'] = isset($mouRecord->updatedDateTime)? $mouRecord->updatedDateTime->getTimeStamp() : $machine->updated_at->getTimeStamp();

        return response()->json(['status'=>'success','data'=>$data,'message'=>'']); 
    }
}

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
            $deployedMachine->isDeleted = false;
            $deployedMachine->save();

            $result = [
                '_id' => [
                    '$oid' => $deployedMachine->id
                ],
                'form_title' => $this->generateFormTitle($form_id,$deployedMachine->id,'machine_tracking'),
                'createdDateTime' => $deployedMachine->createdDateTime,
                'updatedDateTime' => $deployedMachine->updatedDateTime
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

    public function updateDeployedMachine($formId, $machine_id)
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
				$deployedMachine->updatedDateTime = Carbon::now()->getTimestamp();
                $deployedMachine->save();
    
                $result = [
                    '_id' => [
                        '$oid' => $deployedMachine->id
                    ],
                    'form_title' => $this->generateFormTitle($deployedMachine->form_id,$deployedMachine->id,'machine_tracking'),
                    'createdDateTime' => $deployedMachine->createdDateTime,
                    'updatedDateTime' => $deployedMachine->updatedDateTime
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

    public function getMachinesDeployed($formId){
        try {
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$userName = $this->request->user()->id;
			$limit = (int)$this->request->input('limit') ?:50;
			$offset = $this->request->input('offset') ?:0;
			$order = $this->request->input('order') ?:'desc';
			$field = $this->request->input('field') ?:'createdDateTime';
			$page = $this->request->input('page') ?:1;
			$startDate = $this->request->filled('start_date') ? $this->request->start_date : Carbon::now()->subMonth()->getTimestamp();
			$endDate = $this->request->filled('end_date') ? $this->request->end_date : Carbon::now()->getTimestamp();

			$deployed_machines = MachineTracking::where('userName', $userName)
					->where('form_id', $formId)
					->whereBetween('createdDateTime', [$startDate, $endDate])
					->orderBy($field, $order)
					->paginate($limit);

			if ($deployed_machines->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => '']);
			}
			$createdDateTime = $deployed_machines->first()['createdDateTime'];
			$updatedDateTime = $deployed_machines->last()['updatedDateTime'];
			$resonseCount = $deployed_machines->count();

			$result = [
				'form' => [
					'form_id' => $formId,
					'userName' => $deployed_machines->first()['userName'],
					'createdDateTime' => $createdDateTime,
					'updatedDateTime' => $updatedDateTime,
					'submit_count' => $resonseCount
				]
			];

			$values = [];
			foreach ($deployed_machines as &$structure) {
				$structure['form_title'] = $this->generateFormTitle($formId, $structure['_id'], 'machine_tracking');
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'userName', 'createdDateTime']);
			}

			$result['Current page'] = 'Page ' . $deployed_machines->currentPage() . ' of ' . $deployed_machines->lastPage();
			$result['Total number of records'] = $deployed_machines->total();

			return response()->json([
				'status' => 'success',
				'metadata' => [$result],
				'values' => $values,
				'message '=> ''
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
                'machine_code' => $this->request->machine_code,
                'isDeleted' => false
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
                'createdDateTime' => $shiftingRecord->createdDateTime,
                'udpatedDateTime' => $shiftingRecord->udpatedDateTime
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

    public function updateMachineShift($formId, $machine_shift_id){
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
                    'createdDateTime' => $machine_shifted->createdDateTime,
                    'udpatedDateTime' => $machine_shifted->udpatedDateTime
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

    public function getMachinesShifted($formId){
        try {
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$userName = $this->request->user()->id;
			$limit = (int)$this->request->input('limit') ?:50;
			$offset = $this->request->input('offset') ?:0;
			$order = $this->request->input('order') ?:'desc';
			$field = $this->request->input('field') ?:'createdDateTime';
			$page = $this->request->input('page') ?:1;
			$startDate = $this->request->filled('start_date') ? $this->request->start_date : Carbon::now()->subMonth()->getTimestamp();
			$endDate = $this->request->filled('end_date') ? $this->request->end_date : Carbon::now()->getTimestamp();

			$shifted_machines = ShiftingRecord::where('userName', $userName)
					->where('form_id', $formId)
					->whereBetween('createdDateTime', [$startDate, $endDate])
					->orderBy($field, $order)
					->paginate($limit);

			if ($shifted_machines->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => '']);
			}
			$createdDateTime = $shifted_machines->first()['createdDateTime'];
			$updatedDateTime = $shifted_machines->last()['updatedDateTime'];
			$resonseCount = $shifted_machines->count();

			$result = [
				'form' => [
					'form_id' => $formId,
					'userName' => $shifted_machines->first()['userName'],
					'createdDateTime' => $createdDateTime,
					'updatedDateTime' => $updatedDateTime,
					'submit_count' => $resonseCount
				]
			];

			$values = [];
			foreach ($shifted_machines as &$structure) {
				$structure['form_title'] = $this->generateFormTitle($formId, $structure['_id'], 'shifting_records');
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'userName', 'createdDateTime']);
			}

			$result['Current page'] = 'Page ' . $shifted_machines->currentPage() . ' of ' . $shifted_machines->lastPage();
			$result['Total number of records'] = $shifted_machines->total();

			return response()->json([
				'status' => 'success',
				'metadata' => [$result],
				'values' => $values,
				'message '=> ''
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
        $data['createdDateTime'] = $machine->createdDateTime;
        $data['updatedDateTime'] = $machine->updatedDateTime;

        return response()->json(['status'=>'success','data'=>$data,'message'=>'']); 
    }

    public function updateMachineMoU($formId, $recordId)
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
        $data['createdDateTime'] = $mouRecord->createdDateTime;
        $data['updatedDateTime'] = $mouRecord->updatedDateTime;

        return response()->json(['status'=>'success','data'=>$data,'message'=>'']); 
    }

    public function getMachineMoU($formId){
        try {
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$userName = $this->request->user()->id;
			$limit = (int)$this->request->input('limit') ?:50;
			$offset = $this->request->input('offset') ?:0;
			$order = $this->request->input('order') ?:'desc';
			$field = $this->request->input('field') ?:'createdDateTime';
			$page = $this->request->input('page') ?:1;
			$startDate = $this->request->filled('start_date') ? $this->request->start_date : Carbon::now()->subMonth()->getTimestamp();
			$endDate = $this->request->filled('end_date') ? $this->request->end_date : Carbon::now()->getTimestamp();

			$machine_mou = MachineMou::where('userName', $userName)
					->where('form_id', $formId)
					->whereBetween('createdDateTime', [$startDate, $endDate])
					->orderBy($field, $order)
					->paginate($limit);

			if ($machine_mou->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => '']);
			}
			$createdDateTime = $machine_mou->first()['createdDateTime'];
			$updatedDateTime = $machine_mou->last()['updatedDateTime'];
			$resonseCount = $machine_mou->count();

			$result = [
				'form' => [
					'form_id' => $formId,
					'userName' => $machine_mou->first()['userName'],
					'createdDateTime' => $createdDateTime,
					'updatedDateTime' => $updatedDateTime,
					'submit_count' => $resonseCount
				]
			];

			$values = [];
			foreach ($machine_mou as &$structure) {
				$structure['form_title'] = $this->generateFormTitle($formId, $structure['_id'], 'machine_mou');
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'userName', 'createdDateTime']);
			}

			$result['Current page'] = 'Page ' . $machine_mou->currentPage() . ' of ' . $machine_mou->lastPage();
			$result['Total number of records'] = $machine_mou->total();

			return response()->json([
				'status' => 'success',
				'metadata' => [$result],
				'values' => $values,
				'message '=> ''
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

    public function deleteMachineTracking($recordId)
    {
        try {

            $database = $this->connectTenantDatabase($this->request);
                if ($database === null) {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }
    
            $machine = MachineTracking::find($recordId);
    
            if(empty($machine)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => "Record does not exist"
                    ],
                    404
                );
            }
    
            if($this->request->user()->id !== $machine->userName) {
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => "Record cannot be deleted as you are not the creator of the record"
                    ],
                    403
                );
            }
    
            $machine->isDeleted = true;
            $machine->save();
    
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
}

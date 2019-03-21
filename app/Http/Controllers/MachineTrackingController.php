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
            $userId = $this->request->user()->id;

            $machineTracking = new MachineTracking;
            $machineTracking->userName = $userId;
			$machineTracking->form_id = $form_id;
			$machineTracking->isDeleted = false;
            $machineTracking->deployed = true;

            $condition = ['userName' => $userId];   
            $condition['form_id'] = $form_id;         

			$associatedFields = array_map('strtolower', $this->getLevels()->toArray());
			foreach ($data as $field => $value) {
				
				if (in_array($field, $associatedFields)) {
					if (in_array($field, $primaryKeys) && !empty($value)) {
						$field .= '_id';
						$condition[$field] = $value;
					} else {
						$field .= '_id';
					}
				}
				if (in_array($field, $primaryKeys) && !empty($value)) {
					$condition[$field] = $value;
				}
				$machineTracking->$field = $value;
			}

			$existingMachine = MachineTracking::where($condition)->first();
			if(isset($existingMachine)) { 

				return response()->json([
                	'status' => 'error',
                	'data' => '',
                	'message' => 'Machine already deployed please change parameters'
                ],200);
            }
            

            $machineTracking->save();

            $result = [
                '_id' => [
                    '$oid' => $machineTracking->id
                ],
                'form_title' => $this->generateFormTitle($form_id,$machineTracking->id,'machine_tracking'),
                'createdDateTime' => $machineTracking->createdDateTime,
                'updatedDateTime' => $machineTracking->updatedDateTime
            ]; 
            
            return response()->json(['status'=>'success','data'=>$result,'message'=>''],200);
        }catch(\Exception $exception) {
			return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
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
            $data = $this->request->all();
            
            if($deployedMachine->isDeleted === true) {
				return response()->json([
					'status' => 'error',
					'data' => '',
					'message' => 'Record cannot be updated as it has been deleted!'
                ],404);
			}

            $userId = $this->request->user()->id;
            if ($deployedMachine !== null) {

                $deployedMachine->status = $data['status'];
           	 	$deployedMachine->userName = $userId;
				$associatedFields = array_map('strtolower', $this->getLevels()->toArray());

				foreach ($data as $field => $value) {
				
					if (in_array($field, $associatedFields)) {
							$field .= '_id';
					}
					
					$deployedMachine->$field = $value;
                }
                
                $deployedMachine->deployed = true;
				// $deployedMachine->updatedDateTime = Carbon::now()->getTimestamp();
                $deployedMachine->save();
    
                $result = [
                    '_id' => [
                        '$oid' => $deployedMachine->id
                    ],
                    'form_title' => $this->generateFormTitle($deployedMachine->form_id,$deployedMachine->id,'machine_tracking'),
                    'createdDateTime' => $deployedMachine->createdDateTime,
                    'updatedDateTime' => $deployedMachine->updatedDateTime
                ]; 
                
                return response()->json(['status'=>'success','data'=>$result,'message'=>''],200);

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
                    ->where('isDeleted','!=',true)
                    ->where('deployed',true)
                    ->with('village')
                    ->with('taluka')
                    ->with('shiftingRecords')
					->orderBy($field, $order)
					->paginate($limit);

			if ($deployed_machines->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => ''],200);
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
				foreach (array_map('strtolower', $this->getLevels()->toArray()) as $singleJurisdiction) {
					if (isset($structure[$singleJurisdiction])) {
						unset($structure[$singleJurisdiction]);
						$structure[$singleJurisdiction] = $structure[$singleJurisdiction . '_id'];
						unset($structure[$singleJurisdiction . '_id']);
					}
				}
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
            ],200);
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
            $roleId = $this->request->user()->role_id;
			$roleConfig = \App\RoleConfig::where('role_id', $roleId)->first();
			$level = \App\Jurisdiction::find($roleConfig->level);
			if (strtolower($level->levelName) != 'village') {
				$jurisdictionTypeId = $roleConfig->jurisdiction_type_id;
				$locations = \App\Location::where('jurisdiction_type_id', $jurisdictionTypeId);
				foreach ($userLocation as $levelName => $values) {
					$locations->whereIn($levelName . '_id', $values);
				}
				$userLocation['village'] = $locations->pluck('village_id')->all();
			}
			if (isset($userLocation['village']) && !empty($userLocation['village'])) {
				if ($this->request->deployed === 'true') {
                    $machines = MachineTracking::where('deployed',true)
                                                ->where('isDeleted','!=',true)
                                                ->whereIn('village_id', $userLocation['village'])
                                                ->with('village')
                                                ->get();
				} else {
					$machineCodes = [];
					$machineLevels = ['state', 'district', 'taluka'];
                    $machineTrackingRecords = MachineTracking::whereIn('village_id', $userLocation['village'])
                                                                ->where('isDeleted','!=',true)
                                                                ->get();
					$machineTrackingRecords->each(function($machineTracking, $key) {
						$machineCodes[] = $machineTracking->machine_code;
					});
                    $machineRecords = \App\MachineMaster::whereNotIn('machine_code', $machineCodes)
                                                        ->where('isDeleted','!=',true);
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
            ],200);
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
            $userId = $this->request->user()->id;
            
            $shiftingRecord = new ShiftingRecord;
            $shiftingRecord->userName = $userId;
            $shiftingRecord->form_id = $form_id;
            $shiftingRecord->isDeleted = false;

            $condition = ['userName' => $userId];
            $condition['form_id'] = $form_id;
			$associatedFields = ['moved_from_village','moved_to_village'];
			$associatedFields = array_merge($associatedFields,array_map('strtolower', $this->getLevels()->toArray()));
			foreach ($data as $field => $value) {
				
				if (in_array($field, $associatedFields)) {
					if (in_array($field, $primaryKeys) && !empty($value)) {
						$field .= '_id';
						$condition[$field] = $value;
					} else {
						$field .= '_id';
					}
				}
				if (in_array($field, $primaryKeys) && !empty($value)) {
					$condition[$field] = $value;
				}
				$shiftingRecord->$field = $value;
			}

			$existingRecord = ShiftingRecord::where($condition)->first();
			if(isset($existingRecord)) { 
                			
				return response()->json([
                	'status' => 'error',
                	'data' => '',
                	'message' => 'Machine already shifted please change parameters'
                ],400);
            }
            
            $machineAtSource = MachineTracking::firstOrCreate([
                'village_id' => $this->request->moved_from_village,
                'structure_code' => $this->request->old_structure_code,
                'machine_code' => $this->request->machine_code,
                'deployed' => true,
                'isDeleted' => false
            ]);

            $machineAtDestination = MachineTracking::firstOrCreate([
                'village_id' => $this->request->moved_to_village,
                'structure_code' => $this->request->new_structure_code,
                'machine_code' => $this->request->machine_code,
                'deployed' => true,
                'isDeleted' => false
            ]);

                $shiftingRecord->machineTrackings()->attach([$machineAtSource->id, $machineAtDestination->id]);
                $shiftingRecord->save();
                $machineAtSource->shifting_record_ids = [$shiftingRecord->id];
                $machineAtSource->save();
                $machineAtDestination->shifting_record_ids = [$shiftingRecord->id];
                $machineAtDestination->save();

            // $machineAtSource->shiftingRecords()->sync([$shiftingRecord->id]);
            // $machineAtSource->save();
            // $machineAtDestination->shiftingRecords()->sync([$shiftingRecord->id]);
            // $machineAtDestination->save();

            $result = [
                '_id' => [
                    '$oid' => $shiftingRecord->id
                ],
                'form_title' => $this->generateFormTitle($form_id,$shiftingRecord->id,'shifting_records'),
                'createdDateTime' => $shiftingRecord->createdDateTime,
                'updatedDateTime' => $shiftingRecord->updatedDateTime
            ]; 

            return response()->json(['status'=>'success','data'=>$result,'message'=>'']);
        }catch(\Exception $exception) {
			return response()->json([
                        'status' => 'error',
                        'data' => '',
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
            
            if($machine_shifted->isDeleted === true) {
				return response()->json([
					'status' => 'error',
					'data' => '',
					'message' => 'Record cannot be updated as it has been deleted!'
                ],404);
            }
            
            if($machine_shifted !== null){
                $data = $this->request->all();
                $userId = $this->request->user()->id;
                $machine_shifted->userName = $userId;
                $associatedFields = ['moved_from_village','moved_to_village'];
				$associatedFields = array_merge($associatedFields,array_map('strtolower', $this->getLevels()->toArray()));

				foreach ($data as $field => $value) {
				
					if (in_array($field, $associatedFields)) {
							$field .= '_id';
					}
					
					$machine_shifted->$field = $value;
				}

                        $machineAtSource = MachineTracking::firstOrCreate([
                            'village_id' => $this->request->moved_from_village,
                            'structure_code' => $this->request->old_structure_code,
                            'machine_code' => $this->request->machine_code,
                            'deployed' => true,
                            'isDeleted' => false
                        ]);

                        $machineAtDestination = MachineTracking::firstOrCreate([
                            'village_id' => $this->request->moved_to_village,
                            'structure_code' => $this->request->new_structure_code,
                            'machine_code' => $this->request->machine_code,
                            'deployed' => true,
                            'isDeleted' => false
                        ]);
    
                $machine_shifted->machineTrackings()->sync([$machineAtSource->id, $machineAtDestination->id]);

                $machine_shifted->save();   

                $machineAtSource->shiftingRecords()->sync([$machine_shifted->id]);
                $machineAtSource->save();
                $machineAtDestination->shiftingRecords()->sync([$machine_shifted->id]);
                $machineAtDestination->save();

                $result = [
                    '_id' => [
                        '$oid' => $machine_shifted->id
                    ],
                    'form_title' => $this->generateFormTitle($machine_shifted->form_id,$machine_shifted->id,'shifting_records'),
                    'createdDateTime' => $machine_shifted->createdDateTime,
                    'udpatedDateTime' => $machine_shifted->updatedDateTime
                ]; 

                return response()->json(['status'=>'success','data'=>$result,'message'=>''],200);
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
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
                    ->where('isDeleted','!=',true)
                    ->with('movedFromVillage','movedToVillage')
                    ->with('machineTrackings')
					->orderBy($field, $order)
					->paginate($limit);

			if ($shifted_machines->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => ''],200);
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
				if (isset($structure['movedFromVillage'])) {
					unset($structure['movedFromVillage']);
					$structure['moved_from_village'] = $structure['moved_from_village_id'];
					unset($structure['moved_from_village_id']);
				}
				if (isset($structure['movedToVillage'])) {
					unset($structure['movedToVillage']);
					$structure['moved_to_village'] = $structure['moved_to_village_id'];
					unset($structure['moved_to_village_id']);
				}
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
            ],200);
		} catch(\Exception $exception) {
			return response()->json(
				[
					'status' => 'error',
					'data' => '',
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
                                                            ->where('isDeleted','!=',true)
                                                            ->with('shiftingRecords', 'shiftingRecords.movedFromVillage', 'shiftingRecords.movedToVillage', 'village')
                                                            ->get(),
                                    'message'=>''],200); 
    }

    public function machineMoU()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        
        $machine = MachineTracking::where('mou_details','!=',null)
                                    ->where('isDeleted','!=',true)->get();
        
        return response()->json(['status'=>'success','data'=>$machine,'message'=>''],200);    
    }

    public function createMachineMoU($formId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $userId = $this->request->user()->id;
        $data = $this->request->all();

        $primaryKeys = \App\Survey::find($formId)->form_keys;
        $condition = ['userName' => $userId];
        $condition['form_id'] = $formId;
        // $associatedFields = ['ffs', 'volunteers'];
        // $associatedFields = array();

        $associatedFields = array_map('strtolower', $this->getLevels()->toArray());

        $machine = new MachineMou;

			foreach ($data as $field => $value) {
				
				if (in_array($field, $associatedFields)) {
					if (in_array($field, $primaryKeys) && !empty($value)) {
						$field .= '_id';
						$condition[$field] = $value;
					} else {
						$field .= '_id';
					}
				}
				if (in_array($field, $primaryKeys) && !empty($value)) {
					$condition[$field] = $value;
				}
				$machine->$field = $value;
			}

            $existingMachine = MachineMou::where($condition)->first();
			if(isset($existingMachine)) { 
			
				return response()->json([
                	'status' => 'error',
                	'data' => '',
                    'message' => 'Insertion Failure!!! Entry already exists with the same values.'
                ],400);
			}

        $machine->form_id = $formId;
        $machine->userName = $userId;
        $machine->isDeleted = false;
        $machine->save();

       
        $record['_id']['$oid'] = $machine->id;
        $record['form_title'] = $this->generateFormTitle($formId,$machine->id,'machine_mou');
        $record['createdDateTime'] = $machine->createdDateTime;
        $record['updatedDateTime'] = $machine->updatedDateTime;

        return response()->json(['status'=>'success','data'=>$record,'message'=>''],200); 
    }

    public function updateMachineMoU($formId, $recordId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        
        $userId = $this->request->user()->id;
        $data = $this->request->all();

        $mouRecord = MachineMou::find($recordId);
        
        if($mouRecord->isDeleted === true) {
            return response()->json([
                'status' => 'error',
                'data' => '',
                'message' => 'Record cannot be updated as it has been deleted!'
            ],404);
        }

            $primaryKeys = \App\Survey::find($mouRecord->form_id)->form_keys;
			$condition = ['userName' => $userId];
			$associatedFields = array_map('strtolower', $this->getLevels()->toArray());
			foreach ($data as $field => $value) {
				
				if (in_array($field, $associatedFields)) {
					if (in_array($field, $primaryKeys) && !empty($value)) {
						$field .= '_id';
						$condition[$field] = $value;
					} else {
						$field .= '_id';
					}
				}
				if (in_array($field, $primaryKeys) && !empty($value)) {
					$condition[$field] = $value;
				}
				$mouRecord->$field = $value;
			}
			$existingMachine = MachineMou::where($condition)->first();
			if ($existingMachine !== null) {
				return response()->json(
						[
						'status' => 'error',
						'data' => '',
						'message' => 'Machine MoU record already exists. Please change the parameters.'
					],
					400
				);
            }
            
            $mouRecord->save();

        $record['_id']['$oid'] = $recordId;
        $record['form_title'] = $this->generateFormTitle($mouRecord->form_id,$recordId,'machine_mou');
        $record['createdDateTime'] = $mouRecord->createdDateTime;
        $record['updatedDateTime'] = $mouRecord->updatedDateTime;

        return response()->json(['status'=>'success','data'=>$record,'message'=>''],200); 
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
                    ->where('isDeleted','!=',true)
                    ->with('state','district','taluka')
					->orderBy($field, $order)
					->paginate($limit);

			if ($machine_mou->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => ''],200);
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
				foreach (array_map('strtolower', $this->getLevels()->toArray()) as $singleJurisdiction) {
					if (isset($structure[$singleJurisdiction])) {
						unset($structure[$singleJurisdiction]);
						$structure[$singleJurisdiction] = $structure[$singleJurisdiction . '_id'];
						unset($structure[$singleJurisdiction . '_id']);
					}
				}
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
            ],200);
		} catch(\Exception $exception) {
			return response()->json(
				[
					'status' => 'error',
					'data' => '',
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
                        'data' => '',
                        'message' => $exception->getMessage()
                    ],
                    404
                );
            }
    }
    public function deleteMachineMoU($recordId)
    {
        try {

            $database = $this->connectTenantDatabase($this->request);
                if ($database === null) {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }
    
            $machine = MachineMou::find($recordId);
    
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
    
            if((isset($machine->userName) && $this->request->user()->id !== $machine->userName) || (isset($machine->created_by) && $this->request->user()->id !== $machine->created_by)) {
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
                        'data' => '',
                        'message' => $exception->getMessage()
                    ],
                    404
                );
            }
    }

    public function deleteMachineShift($recordId)
    {
        try {

            $database = $this->connectTenantDatabase($this->request);
                if ($database === null) {
                    return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
                }
    
            $machine = ShiftingRecord::find($recordId);
    
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
    
            if((isset($machine->userName) && $this->request->user()->id !== $machine->userName) || (isset($machine->created_by) && $this->request->user()->id !== $machine->created_by)) {
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

            MachineTracking::find($machine->machine_tracking_ids[0])->update(['isDeleted' => true]);
            MachineTracking::find($machine->machine_tracking_ids[1])->update(['isDeleted' => true]);
            
    
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
                        'data' => '',
                        'message' => $exception->getMessage()
                    ],
                    404
                );
            }
    }
}

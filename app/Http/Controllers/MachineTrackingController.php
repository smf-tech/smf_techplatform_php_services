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
use Illuminate\Support\Arr;
use App\MachineMou;
use App\Survey;
use App\MachineMaster;


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
			$condition['deployed'] = true;
			$existingMachine = MachineTracking::where($condition)->first();
			if(isset($existingMachine)) { 

				return response()->json([
                	'status' => 'error',
                	'data' => '',
					'message' => 'Machine already deployed. Please change parameters.'
                ],400);
            }
            $role = $this->request->user()->role_id;
            $roleConfig = \App\RoleConfig::where('role_id', $role)->first();
            $userRoleLocation = $this->request->user()->location;
            $userRoleLocation['role_id'] = $role;
            $machineTracking->user_role_location = $userRoleLocation;
            $machineTracking->jurisdiction_type_id = $roleConfig->jurisdiction_type_id;

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
            $userLocation = $this->request->user()->location;
			$limit = (int)$this->request->input('limit') ?:50;
			$offset = $this->request->input('offset') ?:0;
			$order = $this->request->input('order') ?:'desc';
			$field = $this->request->input('field') ?:'createdDateTime';
			$page = $this->request->input('page') ?:1;
			$startDate = $this->request->filled('start_date') ? $this->request->start_date : Carbon::now()->subMonth()->getTimestamp();
			$endDate = $this->request->filled('end_date') ? $this->request->end_date : Carbon::now()->getTimestamp();

			$deployed_machines = MachineTracking::where('userName', $userName)
                    ->where('form_id', $formId)
                    ->where(function($q) use ($userLocation) {
                        foreach ($userLocation as $level => $location) {
                            $q->whereIn('user_role_location.' . $level, $location);
                        }
                    })                       
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
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'userName', 'createdDateTime', 'user_role_location', 'jurisdiction_type_id']);
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
					$deployedMachines = MachineTracking::where([
						'userName' => $this->request->user()->id,
						'deployed' => true,
						])
						->where('isDeleted', '!=', true)
						->whereIn('village_id', $userLocation['village'])
						->pluck('machine_code')
						->all();
					$machine = MachineMaster::whereNotIn('machine_code', $deployedMachines);
					foreach ($userLocation as $level => $location) {
						if (strtolower($level) !== 'village') {
							$machine->whereIn(strtolower($level) . '_id', $location);
						}
					}
					$machine->where('isDeleted', '!=', true);
					$machine->with('state', 'district', 'taluka');
					$machines = $machine->get(['machine_code', 'state_id', 'district_id', 'taluka_id']);
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
			if ($data['old_structure_code'] === $data['new_structure_code']) {
				return response()->json([
                	'status' => 'error',
                	'data' => '',
                	'message' => 'Machine can not be shifted to same structure. Please select different structure.'
                ],400);
			}
            $shiftedSourceMachine = MachineTracking::where([
                'village_id' => $this->request->moved_from_village,
                'structure_code' => $this->request->old_structure_code,
                'machine_code' => $this->request->machine_code,
                'deployed' => false
            ])->first();
            if (isset($shiftedSourceMachine)) {
				return response()->json([
                    'status' => 'error',
					'data' => '',
					'message' => 'Machine has already been shifted.'
                ],400);
			}
            $completeStructure = \App\StructureTracking::where([
				'village_id' => $this->request->moved_to_village,
				'structure_code' => $this->request->new_structure_code,
				'status' => 'completed'
			])->first();
			if (isset($completeStructure)) {
				return response()->json([
                	'status' => 'error',
					'data' => '',
					'message' => 'Machine can not be shifted to completed structure.'
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

            $role = $this->request->user()->role_id;
            $roleConfig = \App\RoleConfig::where('role_id', $role)->first();
            $userRoleLocation = $this->request->user()->location;
            $userRoleLocation['role_id'] = $role;

            $shiftingRecord->machineTrackings()->attach([$machineAtSource->id, $machineAtDestination->id]);
            $shiftingRecord->user_role_location = $userRoleLocation;
            $shiftingRecord->jurisdiction_type_id = $roleConfig->jurisdiction_type_id;
            $shiftingRecord->save();

            $machineAtSource->shifting_record_ids = [$shiftingRecord->id];
            $machineAtSource->user_role_location = $userRoleLocation;
            $machineAtSource->jurisdiction_type_id = $roleConfig->jurisdiction_type_id;
			$machineAtSource->deployed = false;
            $machineAtSource->save();

            $machineAtDestination->user_role_location = $userRoleLocation;
            $machineAtDestination->jurisdiction_type_id = $roleConfig->jurisdiction_type_id;            
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

				if ($data['old_structure_code'] === $data['new_structure_code']) {
					return response()->json([
						'status' => 'error',
						'data' => '',
						'message' => 'Machine can not be shifted to same structure. Please select different structure.'
					],400);
				}
                $shiftedSourceMachine = MachineTracking::where([
                    'village_id' => $this->request->moved_from_village,
                    'structure_code' => $this->request->old_structure_code,
                    'machine_code' => $this->request->machine_code,
                    'deployed' => false
                ])->first();
                if (isset($shiftedSourceMachine)) {
                    return response()->json([
                        'status' => 'error',
                        'data' => '',
                        'message' => 'Machine has already been shifted.'
                    ],400);
                }

                $userId = $this->request->user()->id;
                $machine_shifted->userName = $userId;
                $associatedFields = ['moved_from_village','moved_to_village'];
				$associatedFields = array_merge($associatedFields,array_map('strtolower', $this->getLevels()->toArray()));

				$completeStructure = \App\StructureTracking::where([
					'village_id' => $this->request->moved_to_village,
					'structure_code' => $this->request->new_structure_code,
					'status' => 'completed'
				])->first();
				if (isset($completeStructure)) {
					return response()->json([
						'status' => 'error',
						'data' => '',
						'message' => 'Machine can not be shifted to completed structure.'
					],400);
				}

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
                            'deployed' => false,
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
            $userLocation = $this->request->user()->location;
			$limit = (int)$this->request->input('limit') ?:50;
			$offset = $this->request->input('offset') ?:0;
			$order = $this->request->input('order') ?:'desc';
			$field = $this->request->input('field') ?:'createdDateTime';
			$page = $this->request->input('page') ?:1;
			$startDate = $this->request->filled('start_date') ? $this->request->start_date : Carbon::now()->subMonth()->getTimestamp();
			$endDate = $this->request->filled('end_date') ? $this->request->end_date : Carbon::now()->getTimestamp();

            $shifted_machines = ShiftingRecord::where('userName', $userName)
                    ->where('form_id', $formId)
                    ->where(function($q) use ($userLocation) {
                        foreach ($userLocation as $level => $location) {
                            $q->whereIn('user_role_location.' . $level, $location);
                        }
                    })   
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
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'userName', 'createdDateTime', 'user_role_location', 'jurisdiction_type_id']);
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
        $role = $this->request->user()->role_id;
        $userRoleLocation = $this->request->user()->location;
        $userRoleLocation['role_id'] = $role;
        $roleConfig = \App\RoleConfig::where('role_id', $role)->first();
        $machine->user_role_location = $userRoleLocation;
        $machine->jurisdiction_type_id = $roleConfig->jurisdiction_type_id;        
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
            $userLocation = $this->request->user()->location;
			$limit = (int)$this->request->input('limit') ?:50;
			$offset = $this->request->input('offset') ?:0;
			$order = $this->request->input('order') ?:'desc';
			$field = $this->request->input('field') ?:'createdDateTime';
			$page = $this->request->input('page') ?:1;
			$startDate = $this->request->filled('start_date') ? $this->request->start_date : Carbon::now()->subMonth()->getTimestamp();
			$endDate = $this->request->filled('end_date') ? $this->request->end_date : Carbon::now()->getTimestamp();

			$machine_mou = MachineMou::where('userName', $userName)
                    ->where('form_id', $formId)
                    ->where(function($q) use ($userLocation) {
                        foreach ($userLocation as $level => $location) {
                            $q->whereIn('user_role_location.' . $level, $location);
                        }
                    })                     
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
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'userName', 'createdDateTime', 'user_role_location', 'jurisdiction_type_id']);
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

    public function deleteMachineTracking($formId, $recordId)
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
    public function deleteMachineMoU($formId, $recordId)
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

    public function deleteMachineShift($formId, $recordId)
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

    public function machineAggregateDeploy($form_id)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        try {
            $survey = \App\Survey::find($form_id);
            //get the matrix field name and its fields
            list($matrix_field_label, $matrix_fields) = $this->getMatrixdynamicFields($survey);  
            $primaryKeys = $survey->form_keys;

            $data = $this->request->all();
            $userId = $this->request->user()->id;

            $machines_to_deploy = [];
            $children = [];
 
            if(isset($matrix_field_label)){
                $matrix_request_data = $this->request->input($matrix_field_label);
                foreach($matrix_request_data as $matrix_data){
                    $condition = [];
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
                        if ($field != $matrix_field_label){
                            $machineTracking->$field = $value;
                        }
                    }

                    foreach($matrix_data as $key=>$value){
                        if(in_array($key,$primaryKeys)){
                            $condition[$key] = $value; 
                        }
                        $machineTracking->$key = $value;
                    }
					$condition['deployed'] = true;
                    $existingMachine = MachineTracking::where($condition)->first();
                    if(isset($existingMachine)) { 
        
                        return response()->json([
                            'status' => 'error',
                            'data' => '',
                            'message' => 'Machine already deployed please change parameters'
                        ],400);
                    }

                    $machines_to_deploy[] = $machineTracking;
                }
 
                //loop through machines_to_deploy and save them to DB
                foreach ($machines_to_deploy as $machine){
                    $machine->save();
                    $children[] =  $machine->id;
                }

                $date = Carbon::now();
				$user = $this->request->user();
                $assoc_data = array('userName'=>$userId,'children'=>$children,'form_id'=>$form_id,'createdDateTime'=>$date->getTimestamp(),'updatedDateTime'=>$date->getTimestamp(),'isDeleted'=>false);
				$userRoleLocation = $user->location;
                $userRoleLocation['role_id'] = $user->role_id;
                $assoc_data['user_role_location'] = $userRoleLocation;
                $roleConfig = \App\RoleConfig::where('role_id', $user->role_id)->first();
                $assoc_data['jurisdiction_type_id'] = $roleConfig->jurisdiction_type_id;
                $aggregate_assoc = DB::collection('aggregate_associations')->insertGetId($assoc_data);
                       

                $result = [
                    '_id' => $aggregate_assoc,
                    'form_title' => $this->generateFormTitle($form_id,$children[0],'machine_tracking'),
                    'createdDateTime' => $date->getTimestamp(),
                    'updatedDateTime' => $date->getTimestamp()
                ]; 
                
                return response()->json(['status'=>'success','data'=>$result,'message'=>''],200);
            }
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


    public function getAggregateMachinesDeployed($survey_id)
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
        
        //var_dump($aggregateResults);exit;
        
        $collection_name = 'machine_tracking';     
 

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
            $values[] = Arr::except($aggregateResult,['survey_id','userName','updated_at','created_at']);
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

    public function updateAggregateDeployedMachine($survey_id,$groupId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $user = $this->request->user();

        $survey = Survey::find($survey_id);

        // Selecting the collection to use depending on whether the survey has an entity_id or not
        $collection_name = 'machine_tracking';

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
       
        $data['form_title'] = $this->generateFormTitle($survey_id,$group_arr[0],'machine_tracking');
        $data['_id']['$oid'] = $groupId;
        $data['createdDateTime'] = $group_record->first()['createdDateTime'];
        $data['updatedDateTime'] = $group_record->first()['updatedDateTime'];
        return response()->json(['status'=>'success', 'data' => $data, 'message'=>'']);

    }

    public function deleteAggregateMachinesDeployed($survey_id,$groupId){
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
            $collection_name = 'machine_tracking';

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
}

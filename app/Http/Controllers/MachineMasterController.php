<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\MachineMaster;
use App\District;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\MachineTracking;

class MachineMasterController extends Controller
{
    use Helpers;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getMachineCode()
    {
        try {
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$bjsOwned = $this->request->filled('bjsowned') && $this->request->bjsowned == 'true';
			$rented = $this->request->filled('rented') && $this->request->rented == 'true';
			$considerMou = $this->request->filled('mou') && $this->request->mou == 'true';
			$ignoreMou = $this->request->filled('mou') && $this->request->mou == 'false';
			$userLocation = $this->request->user()->location;

			if ($bjsOwned || $rented) {
				if (!isset($userLocation['village'])) {
					$roleId = $this->request->user()->role_id;
					$roleConfig = \App\RoleConfig::where('role_id', $roleId)->first();
					$jurisdictionTypeId = $roleConfig->jurisdiction_type_id;
					$locations = \App\Location::where('jurisdiction_type_id', $jurisdictionTypeId);
					foreach ($userLocation as $levelName => $values) {
						$locations->whereIn($levelName . '_id', $values);
					}
					$userVillages = $locations->pluck('village_id')->all();
				} else {
					$userVillages = $userLocation['village'];
				}
				$deployedMachines = MachineTracking::where([
//					'userName' => $this->request->user()->id,
					'deployed' => true,
					])
					->where('isDeleted', '!=', true)
					->whereIn('village_id', $userVillages)
					->pluck('machine_code')
					->all();
			}

			$machine = MachineMaster::query();
			if ($bjsOwned || $rented) {
				$machine->whereNotIn('machine_code', $deployedMachines);
			}
			if ($bjsOwned && !$rented) {
				$machine->where('owned_by_bjs', true);
			}
			if (!$bjsOwned && $rented) {
				$machine->where('owned_by_bjs', '!=', true);
			}
			foreach ($userLocation as $level => $location) {
				$machine->whereIn(strtolower($level) . '_id', $location);
			}
			$machine->with('state', 'district', 'taluka');
			$machines = $machine->get(['machine_code', 'state_id', 'district_id', 'taluka_id', 'owned_by_bjs']);
			if ((
					(!$bjsOwned && !$rented)
					||
					($bjsOwned && !$rented)
					||
					(!$bjsOwned && $rented)
					||
					($bjsOwned && $rented && !$considerMou)
				) && !$ignoreMou) {
				return response()->json([
					'status' => 'success',
					'data' => $machines,
					'message' => 'List of Machine codes.'
				],200);
			}

			$machines = $machines->filter(function($value, $key) use ($ignoreMou) {
				if ($value->owned_by_bjs) {
					return true;
				}

				$mou = \App\MachineMou::where(
							[
								'machine_code' => $value->machine_code,
								'mou_cancellation'=> false
							]
						)
						->where(function($query) {
							$query->orWhere(function($query) {
								$query->where('rate1_from', '<=', Carbon::now()->getTimestamp())->where('rate1_to', '>=', Carbon::now()->getTimestamp());
							})->orWhere(function($query) {
								$query->where('rate2_from', '<=', Carbon::now()->getTimestamp())->where('rate2_to', '>=', Carbon::now()->getTimestamp());
							})->orWhere(function($query) {
								$query->where('rate3_from', '<=', Carbon::now()->getTimestamp())->where('rate3_to', '>=', Carbon::now()->getTimestamp());
							});
						})
						->get(['machine_code']);
				if ($mou->count() && !$ignoreMou) {
					return true;
				} elseif ($mou->count() === 0 && $ignoreMou) {
					return true;
				}
				return false;
			});

            return response()->json([
				'status' => 'success',
				'data' => $machines->values(),
				'message' => 'List of Machine codes.'
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

    public function createMachineCode($formId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $model_values = [
            'TH' => [200,210,220],
            'JB' => [205, 215,220],
            'HY' => [210,215],
            'SN' => [210,220],
            'KB' => [210,220],
            'KM' => [210],
            'VL' => [210],
            'CT' => [320]
        ];

        if (! array_key_exists($this->request->input('machine_make'),$model_values) )
        {
            return response()->json(
                [
                    'status' => 'error',
                    'data' => '',
                    'message' => 'Please select a valid machine make'
                ],
                400
            );
        }

        if (! in_array($this->request->input('machine_model'),$model_values[$this->request->input('machine_make')]) )
        {
            return response()->json(
                [
                    'status' => 'error',
                    'data' => '',
                    'message' => 'The selected machine model is not available for this Machine Make'
                ],
                400
            );
        }


        $data = $this->request->all();
        $userId = $this->request->user()->id;
        $district = District::find($this->request->input('district'));
        // $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->get(['machine_code']);
        // $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->max('machine_code');
        $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->orderBy('createdDateTime','desc')->first();
        // $queueValue = substr($machines,2,3) + 1;
        $queueValue = $machines !== null ? (int) (substr($machines->machine_code,2,-7)) + 1 : 100;
        /*$modelNumber = $values[$this->request->input('machine_make')][$this->request->input('machine_model')];
        if(empty($modelNumber)) {
            return response()->json(
                [
                    'status' => 'error',
                    'data' => '',
                    'message' => 'Invalid Entry For Machine Model'
                ],
                400
            );
        }*/
        $modelcode = '';
        if($this->request->input('machine_make') == 'TH'){
            if($this->request->input('machine_model') == '220'){
                $modelcode = 'B';
            }
            if(in_array($this->request->input('machine_model'),array('200','210'))){
                $modelcode = 'A';
            }
        }
        if($this->request->input('machine_make') == 'JB'){
            if($this->request->input('machine_model') == '220'){
                $modelcode = 'B';
            } 
            if(in_array($this->request->input('machine_model'),array('205','215'))){
                $modelcode = 'A';
            }       
        }
        if($this->request->input('machine_make') == 'HY'){
    
            if(in_array($this->request->input('machine_model'),array('210','215'))){
                $modelcode = 'A';
            }
        }
        if($this->request->input('machine_make') == 'SN'){

            if(in_array($this->request->input('machine_model'),array('210','220'))){
                $modelcode = 'A';
            }
        }
        if($this->request->input('machine_make') == 'KB'){
            if(in_array($this->request->input('machine_model'),array('210','220'))){
                $modelcode = 'B';
            }
        }
        if($this->request->input('machine_make') == 'KM'){
            if($this->request->input('machine_model') == '210'){
                $modelcode = 'B';
            }
        }
        if($this->request->input('machine_make') == 'VL'){
            if($this->request->input('machine_model') == '210'){
                $modelcode = 'B';
            }
        }
        if($this->request->input('machine_make') == 'CT'){
            if($this->request->input('machine_model') == '320'){
                $modelcode = 'B';
            }
        }
        $finalCode = $district->abbr.$queueValue.$this->request->input('machine_make').$this->request->input('machine_model').$modelcode.$this->request->input('machine_type');

        $userLocation = $this->request->user()->location;
			// $machines = [];
        $role = $this->request->user()->role_id;
        $roleConfig = \App\RoleConfig::where('role_id', $role)->first();
        $jurisdictionType = \App\JurisdictionType::find($roleConfig->jurisdiction_type_id);
        $firstLevel = strtolower(array_values($jurisdictionType->jurisdictions)[0]);
        if (!isset($data[$firstLevel])) {
            $location = \App\Location::where([
                'jurisdiction_type_id' => $jurisdictionType->id,
                'district_id' => $data['district'],
                'taluka_id' => $data['taluka']
            ])->first();
            $data[$firstLevel] = $location->state_id;
        }

        $primaryKeys = \App\Survey::find($formId)->form_keys;
        $condition = ['userName' => $userId];
        $associatedFields = array_map('strtolower', $this->getLevels()->toArray());
        
        $machineRecord = new MachineMaster;

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
            $machineRecord->$field = $value;
        }
        
        $existingMachine = MachineMaster::where($condition)->first();
        if (isset($existingMachine)) {
            return response()->json(
                    [
                    'status' => 'error',
                    'data' => '',
                    'message' => 'Machine already exists. Please change the parameters.'
                ],
                400
            );
        }    

        $machineRecord->userName = $userId;
        $machineRecord->machine_code = $finalCode;
        $machineRecord->form_id = $formId;
        $machineRecord->isDeleted = false;
        //add user role location object to the machine master record
        $userRoleLocation = $this->request->user()->location;
        $userRoleLocation['role_id'] = $role;
        $machineRecord->user_role_location = $userRoleLocation;
        $machineRecord->jurisdiction_type_id = $roleConfig->jurisdiction_type_id;

        $machineRecord->save();
        
        $record['_id']['$oid'] = $machineRecord->id;
        $record['form_title'] = $this->generateFormTitle($formId,$record['_id']['$oid'],'machine_masters');
		$record['createdDateTime'] = $machineRecord->createdDateTime;
		$record['updatedDateTime'] = $machineRecord->updatedDateTime;

        return response()->json([
            'status' => 'success',
            'data' => $record,
            'message' => 'Create Record in Machine Master'
        ],200);
    }

    public function getMachineCodes($formId){
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

            $role = $this->request->user()->role_id;
			$roleConfig = \App\RoleConfig::where('role_id', $role)->first();
            $jurisdictionTypeId = $roleConfig->jurisdiction_type_id;

			$userLocation = $this->getFullHierarchyUserLocation($this->request->user()->location, $jurisdictionTypeId);
            $locationKeys = $this->getFormSchemaKeys($formId);

			$machine_masters= MachineMaster::where('userName', $userName)
                    ->where('form_id', $formId)
                    ->where(function($q) use ($userLocation, $locationKeys) {
						if (!empty($locationKeys)) {
                            foreach ($locationKeys as $locationKey) {
                                if (isset($userLocation[$locationKey]) && !empty($userLocation[$locationKey])) {
                                    $q->whereIn($locationKey . '_id', $userLocation[$locationKey]);
                                }
                            }
                        } else {
                            foreach ($this->request->user()->location as $level => $location) {
                                $q->whereIn('user_role_location.' . $level, $location);
                            }
                        }
					})
                    ->whereBetween('createdDateTime', [$startDate, $endDate])                    
                    ->where('isDeleted','!=',true)
                    ->with('district','taluka')
					->orderBy($field, $order)
					->paginate($limit);

			if ($machine_masters->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => ''],200);
			}
			$createdDateTime = $machine_masters->first()['createdDateTime'];
			$updatedDateTime = $machine_masters->last()['updatedDateTime'];
			$resonseCount = $machine_masters->count();

			$result = [
				'form' => [
					'form_id' => $formId,
					'userName' => $machine_masters->first()['userName'],
					'createdDateTime' => $createdDateTime,
					'updatedDateTime' => $updatedDateTime,
					'submit_count' => $resonseCount
				]
			];

			$values = [];
			foreach ($machine_masters as &$structure) {
				foreach (array_map('strtolower', $this->getLevels()->toArray()) as $singleJurisdiction) {
					if (isset($structure[$singleJurisdiction . '_id'])) {
						unset($structure[$singleJurisdiction]);
						$structure[$singleJurisdiction] = $structure[$singleJurisdiction . '_id'];
						unset($structure[$singleJurisdiction . '_id']);
					}
				}
				$structure['form_title'] = $this->generateFormTitle($formId, $structure['_id'], 'machine_masters');
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'userName', 'createdDateTime', 'user_role_location', 'jurisdiction_type_id']);
			}

			$result['Current page'] = 'Page ' . $machine_masters->currentPage() . ' of ' . $machine_masters->lastPage();
			$result['Total number of records'] = $machine_masters->total();

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

    public function deleteMachine($formId, $recordId)
	{
        try {

        $database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}

        $machine = MachineMaster::find($recordId);

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

        $machines = MachineTracking::where('machine_code',$machine->machine_code)
                                        ->where('isDeleted','!=',true)
                                        ->update(['isDeleted' => true]);

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
}

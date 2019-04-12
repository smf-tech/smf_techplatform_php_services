<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\StructureTracking;
use App\Volunteer;
use App\FFAppointed;
use App\Village;
use Carbon\Carbon;
use App\Taluka;
use Illuminate\Support\Facades\DB;
use App\MachineTracking;

class StructureTrackingController extends Controller
{
    use Helpers;

    const PREPARED = 'prepared';
    const COMPLETED = 'completed';

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function prepare($formId)
    {
        try {
			$database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }

			$userId = $this->request->user()->id;
			$data = $this->request->all();

			$structureTracking = new StructureTracking;
			$structureTracking->status = self::PREPARED;
            $structureTracking->userName = $userId;
			$structureTracking->form_id = $formId;
			$structureTracking->isDeleted = false;

            
			$primaryKeys = \App\Survey::find($formId)->form_keys;
			$condition = ['userName' => $userId];
			$associatedFields = ['ffs', 'volunteers'];
			$associatedFields = array_merge($associatedFields, array_map('strtolower', $this->getLevels()->toArray()));
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
				$structureTracking->$field = $value;
			}
			$existingStructure = StructureTracking::where($condition)->first();
			if ($existingStructure !== null) {
				return response()->json(
						[
						'status' => 'error',
						'data' => '',
						'message' => 'Structure already exists. Please change the parameters.'
					],
					400
				);
			}

			$structureTracking->save();

            return response()->json([
                'status' => 'success',
                'data' => [
					'_id' => [
						'$oid' => $structureTracking->id
				],
                    'form_title' => $this->generateFormTitle($formId, $structureTracking->id, 'structure_trackings'),
                    'createdDateTime' => $structureTracking->createdDateTime,
                    'updatedDateTime' => $structureTracking->updatedDateTime
				],
                'message' => 'Structure prepared successfully.'
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

	public function updatePreparedStructure(Request $request, $formId, $structureId)
	{
		try {
			$database = $this->connectTenantDatabase($request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$structure = StructureTracking::find($structureId);

			if($structure->isDeleted === true) {
				return response()->json([
					'status' => 'error',
					'data' => '',
					'message' => 'Structure cannot be updated as the record has been deleted!'
				],
			404);
			}
			if ($structure !== null) {
				$data = $request->all();
				$formId = $structure->form_id;

				$structure->status = $data['status'];
           	 	$structure->userName = $userId;
				$associatedFields = ['ffs', 'volunteers'];
				$associatedFields = array_merge($associatedFields, array_map('strtolower', $this->getLevels()->toArray()));

				foreach ($data as $field => $value) {
				
					if (in_array($field, $associatedFields)) {
							$field .= '_id';
					}
					
					$structure->$field = $value;
				}

				$structure->save();

				$result = [
					'_id' => [
						'$oid' => $structureId
					],
					'form_title' => $this->generateFormTitle($formId, $structureId, $structure->getTable()),
                    'createdDateTime' => $structure->createdDateTime,
                    'updatedDateTime' => $structure->updatedDateTime
				];
				return response()->json([
                'status' => 'success',
                'data' => $result,
                'message' => 'Structure updated successfully.'
				],200);
			}
			return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => 'Record not found'
                    ],
                    404
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

	public function get()
    {
        try {
            if (!$this->request->filled('prepared') && !$this->request->filled('completed')) {
                return response()->json(
                        [
                        'status' => 'error',
                        'data' => '',
                        'message' => 'prepared or completed parameter is missing'
                    ],
                    400
                );
			}
			
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			$userLocation = $this->request->user()->location;
			$structures = [];
			$worktype = '';
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

				if ($this->request->filled('prepared') && $this->request->prepared === 'true') {
					if ($this->request->filled('worktype') && $this->request->worktype === 'desilting') {
						$worktype = $this->request->input('worktype');
					}
					
					$query = StructureTracking::query();
					$query->when($worktype == 'desilting', function ($q) {
						return $q->where('work_type','desilting');
					});
					$structures = $query->where('status', self::PREPARED)
					 	->whereIn('village_id', $userLocation['village'])
					 	->where('isDeleted','!=',true)
					 	->with('village', 'ffs', 'volunteers')->get();
					
				} elseif ($this->request->filled('prepared') && $this->request->prepared === 'false') {
					$structureCodes = [];
					$stuctureLevels = ['state', 'district', 'taluka'];
					if ($this->request->filled('worktype') && $this->request->worktype === 'desilting') {
						$worktype = $this->request->input('worktype');
					}
					$query = StructureTracking::query();
					$query->when($worktype == 'desilting', function ($q) {
						return $q->where('work_type','desilting');
					});
					$structureTrackingList = $query->whereIn('village_id', $userLocation['village'])			
						->where('isDeleted',false)->get();
					$structureTrackingList->each(function($structureTracking, $key) {
						$structureCodes[] = $structureTracking->structure_code;
					});
					$structureRecords = \App\StructureMaster::whereNotIn('structure_code', $structureCodes)
															->where('isDeleted',false);
					foreach ($userLocation as $level => $location) {
						$structureRecords = $structureRecords->whereIn($level . '_id', $location);
					}
					$structures = $structureRecords->get();
				} elseif ($this->request->filled('completed') && $this->request->completed === 'true') {
					
					if ($this->request->filled('worktype') && $this->request->worktype === 'desilting') {
						$worktype = $this->request->input('worktype');
					}
					$query = StructureTracking::query();
					$query->when($worktype == 'desilting', function ($q) {
						return $q->where('work_type','desilting');
					});
					$structures = $query->where('status', self::COMPLETED)
						->whereIn('village_id', $userLocation['village'])
						->where('isDeleted',false)
						->with('village', 'ffs', 'volunteers')
						->get();
                }
			}
			return response()->json([
				'status' => 'success',
				'data' => $structures,
				'message' => 'List of structures.'
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

	public function getStructures($formId)
	{
		try {
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$status = $this->request->is('*/structure/prepare/*') ? self::PREPARED : self::COMPLETED;
			$userName = $this->request->user()->id;
			$limit = (int)$this->request->input('limit') ?:50;
			$offset = $this->request->input('offset') ?:0;
			$order = $this->request->input('order') ?:'desc';
			$field = $this->request->input('field') ?:'createdDateTime';
			$page = $this->request->input('page') ?:1;
			$startDate = $this->request->filled('start_date') ? $this->request->start_date : Carbon::now()->subMonth()->getTimestamp();
			$endDate = $this->request->filled('end_date') ? $this->request->end_date : Carbon::now()->getTimestamp();

			$structures = StructureTracking::where('userName', $userName)
					->where('form_id', $formId)
					->where('status', $status)
					->where('isDeleted','!=',true)
					->whereBetween('createdDateTime', [$startDate, $endDate])
					->with('village','taluka','ffs')
					->orderBy($field, $order)
					->paginate($limit);

			if ($structures->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => ''],200);
			}
			$createdDateTime = $structures->last()['createdDateTime'];
			$updatedDateTime = $structures->first()['updatedDateTime'];
			$resonseCount = $structures->count();

			$result = [
				'form' => [
					'form_id' => $formId,
					'userName' => $structures->first()['userName'],
					'createdDateTime' => $createdDateTime,
					'updatedDateTime' => $updatedDateTime,
					'submit_count' => $resonseCount
				]
			];

			$values = [];
			foreach ($structures as &$structure) {
				foreach (array_map('strtolower', $this->getLevels()->toArray()) as $singleJurisdiction) {
					if (isset($structure[$singleJurisdiction])) {
						unset($structure[$singleJurisdiction]);
						$structure[$singleJurisdiction] = $structure[$singleJurisdiction . '_id'];
						unset($structure[$singleJurisdiction . '_id']);
					}
				}
				$structure['form_title'] = $this->generateFormTitle($formId, $structure['_id'], 'structure_trackings');
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'taluka', 'userName', 'createdDateTime']);
			}

			$result['Current page'] = 'Page ' . $structures->currentPage() . ' of ' . $structures->lastPage();
			$result['Total number of records'] = $structures->total();

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

	public function complete($formId)
    {
        try {
			$database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			
            $userId = $this->request->user()->id;
			$data = $this->request->all();

			$structureTracking = new StructureTracking;
			$data['status'] = $data['status'] == true ? self::COMPLETED : self::PREPARED;
            $structureTracking->status = $data['status'];
            $structureTracking->userName = $data['userName'] = $userId;
			$structureTracking->form_id = $data['form_id'] = $formId;
			$structureTracking->isDeleted = $data['isDeleted'] = false;
            
			$primaryKeys = \App\Survey::find($formId)->form_keys;
			$condition = ['userName' => $userId];
			$associatedFields = ['ffs', 'volunteers'];
			$associatedFields = array_merge($associatedFields, array_map('strtolower', $this->getLevels()->toArray()));
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
				$structureTracking->$field = $value;
			}

			$existingStructure = StructureTracking::where($condition)->first();
			if(isset($existingStructure)) { 
				$existingStructure->update($data);
			
				return response()->json([
                	'status' => 'success',
                	'data' => [
						'_id' => [
							'$oid' => $existingStructure->getIdAttribute()
						],
						'form_title' => $this->generateFormTitle($formId, $existingStructure->getIdAttribute(), $structureTracking->getTable()),
                    	'createdDateTime' => $existingStructure->createdDateTime,
                    	'updatedDateTime' => $existingStructure->updatedDateTime
						],
						'message' => 'Structure ' . $structureTracking->status . ' successfully.'
            	]);
			}
			
			$structureTracking->save();
			
			if( $structureTracking->status === self::COMPLETED ) {
				$machines = MachineTracking::where('structure_code',$structureTracking->structure_code)
											->where('village_id',$structureTracking->village_id)->update(['deployed' => false]);
			}
			
			return response()->json([
                'status' => 'success',
                'data' => [
					'_id' => [
						'$oid' => $structureTracking->getIdAttribute()
					],
					'form_title' => $this->generateFormTitle($formId, $structureTracking->getIdAttribute(), $structureTracking->getTable()),
                    'createdDateTime' => $structureTracking->createdDateTime,
                    'updatedDateTime' => $structureTracking->updatedDateTime
					],
                'message' => 'Structure ' . $structureTracking->status . ' successfully.'
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

    public function updateComplete($formId,$structureId)
    {
        try {
            $userId = $this->request->user()->id;
            $data = $this->request->all();
            
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }

			$structureTracking = StructureTracking::find($structureId);
			
			if($structureTracking->isDeleted === true) {
				return response()->json([
					'status' => 'error',
					'data' => '',
					'message' => 'Structure cannot be updated as the record has been deleted!'
				],404);
			}

            if(empty($structureTracking))
                return response()->json([
                    'status' => 'error',
                    'data' => '',
                    'message' => 'Update Failed as record does not exist!'
				],404);
			$data['status'] = $data['status'] == true ? self::COMPLETED : self::PREPARED;
			$structureTracking->status = $data['status'];
            $structureTracking->userName = $userId;
			$associatedFields = ['ffs', 'volunteers'];
			$associatedFields = array_merge($associatedFields, array_map('strtolower', $this->getLevels()->toArray()));

				foreach ($data as $field => $value) {
				
					if (in_array($field, $associatedFields)) {
							$field .= '_id';
					}
					$structureTracking->$field = $value;
				}
	
				$structureTracking->save();

				if( $structureTracking->status === self::COMPLETED ) {
					$machines = MachineTracking::where('structure_code',$structureTracking->structure_code)
												->where('village_id',$structureTracking->village_id)
												->where('isDeleted','!=',true)
												->update(['deployed' => false]);
				} elseif( $structureTracking->status === self::PREPARED ) {
					$machines = MachineTracking::where('structure_code',$structureTracking->structure_code)
												->where('village_id',$structureTracking->village_id)
												->where('isDeleted','!=',true)
												->update(['deployed' => true]);
				}
			
            return response()->json([
                'status' => 'success',
                'data' => [
					'_id' => [
						'$oid' => $structureTracking->getIdAttribute()
					],
					'form_title' => $this->generateFormTitle($structureTracking->form_id, $structureId, $structureTracking->getTable()),
                    'createdDateTime' => $structureTracking->createdDateTime,
                    'updatedDateTime' => $structureTracking->updatedDateTime
					],
                'message' => 'Structure ' . $structureTracking->status . ' successfully.'
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
	
	public function deleteStructureTracking($formId, $recordId)
	{
		try {

			$database = $this->connectTenantDatabase($this->request);
				if ($database === null) {
					return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
				}
	
			$structure = StructureTracking::find($recordId);
	
			if(empty($structure)) {
				return response()->json(
					[
						'status' => 'error',
						'data' => '',
						'message' => "Record does not exist"
					],
					404
				);
			}
	
			if($this->request->user()->id !== $structure->userName) {
				return response()->json(
					[
						'status' => 'error',
						'data' => '',
						'message' => "Record cannot be deleted as you are not the creator of the record"
					],
					403
					);
			}
	
			$structure->isDeleted = true;
			$structure->save();
	
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

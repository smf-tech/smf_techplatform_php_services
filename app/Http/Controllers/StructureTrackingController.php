<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\StructureTracking;
use App\Volunteer;
use App\FFAppointed;
use App\Village;
use Carbon\Carbon;

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
            $userId = $this->request->user()->id;
            $data = $this->request->all();
            $data['status'] = self::PREPARED;
            $data['userName'] = $userId;
			$data['form_id'] = $formId;
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			$primaryKeys = \App\Survey::find($formId)->form_keys;
			$condition = ['userName' => $userId];
			foreach ($data as $field => $value) {
				if (in_array($field, $primaryKeys) && !empty($value)) {
					if ($field == 'village') {
						$field .= '_id';
					}
					$condition[$field] = $value;
				}
			}
			$existingStructure = StructureTracking::where($condition)->first();
			if ($existingStructure !== null) {
				return response()->json(
						[
						'status' => 'error',
						'data' => null,
						'message' => 'Structure already exists. Please change the parameters.'
					],
					400
				);
			}
            $structureTracking = StructureTracking::create($data);
            if (isset($data['village']) && !empty($data['village'])) {
                $village = Village::find($data['village']);
                $structureTracking->village()->associate($village);
                $structureTracking->save();
            }
            if (isset($data['ff_name']) && !empty($data['ff_name'])) {
                $ffInstance = FFAppointed::create([
                    'name' => $data['ff_name'],
                    'mobile_number' => $data['ff_mobile_number'],
                    'training_completed' => $data['ff_training_completed']
                ]);
                $ffInstance->structureTracking()->associate($structureTracking);
                $ffInstance->save();
            }

            if (isset($data['volunteers']) && !empty($data['volunteers'])) {
                foreach ($data['volunteers'] as $volunteer) {
                    $volunteerInstance = Volunteer::create($volunteer);
                    $volunteerInstance->structureTracking()->associate($structureTracking);
                    $volunteerInstance->save();
                }
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
                'message' => 'Structure prepared successfully.'
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

	public function updatePreparedStructure(Request $request, $structureId)
	{
		try {
			$database = $this->connectTenantDatabase($request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$structure = StructureTracking::find($structureId);
			if ($structure !== null) {
				$data = $request->all();
				$formId = $structure->form_id;
				$structure->update($data);
				if (isset($data['village']) && $data['village'] != $structure->village_id) {
					$structure->village()->dissociate();
					$structure->village()->associate(Village::find($data['village']));
					$structure->save();
				}
				if (isset($data['ff_name']) && !empty($data['ff_name'])) {
					$ff = FFAppointed::where('structure_traking_id', $structureId)->first();
					if ($ff != null && ($ff->name != $data['ff_name'] || $ff->mobile_number != $data['ff_mobile_number'] || $ff->training_completed != $data['ff_training_completed'])) {
						$ff->update([
							'name' => $data['ff_name'],
							'mobile_number' => $data['ff_mobile_number'],
							'training_completed' => $data['ff_training_completed']
						]);
					}
				}
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
            ]);
			}
			return response()->json(
                    [
                        'status' => 'error',
                        'data' => null,
                        'message' => 'Record not found'
                    ],
                    404
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

	public function get()
    {
        try {
            if (!$this->request->filled('prepared') && !$this->request->filled('completed')) {
                return response()->json(
                        [
                        'status' => 'error',
                        'data' => null,
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
			if (isset($userLocation['village']) && !empty($userLocation['village'])) {
				if ($this->request->filled('prepared') && $this->request->prepared === 'true') {
					$structures = StructureTracking::where('status', self::PREPARED)
							->whereIn('village_id', $userLocation['village'])
							->with('village', 'ffs', 'volunteers')
							->get();
				} elseif ($this->request->filled('prepared') && $this->request->prepared === 'false') {
					$structureCodes = [];
					$stuctureLevels = ['state', 'district', 'taluka'];
					$structureTrackingList = StructureTracking::whereIn('village_id', $userLocation['village'])->get();
					$structureTrackingList->each(function($structureTracking, $key) {
						$structureCodes[] = $structureTracking->structure_code;
					});
					$structureRecords = \App\StructureMaster::whereNotIn('structure_code', $structureCodes);
					foreach ($userLocation as $level => $location) {
						$structureRecords = $structureRecords->whereIn($level . '_id', $location);
					}
					$structures = $structureRecords->get();
				} elseif ($this->request->filled('completed') && $this->request->completed === 'true') {
					$structures = StructureTracking::where('status', self::COMPLETED)
							->whereIn('village_id', $userLocation['village'])
							->with('village', 'ffs', 'volunteers')
							->get();
				}
			}
			return response()->json([
				'status' => 'success',
				'data' => $structures,
				'message' => 'List of structures.'
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
					->whereBetween('createdDateTime', [$startDate, $endDate])
					->orderBy($field, $order)
					->paginate($limit);

			if ($structures->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => '']);
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
				$structure['form_title'] = $this->generateFormTitle($formId, $structure['_id'], 'structure_trackings');
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'userName', 'createdDateTime']);
			}

			$result['Current page'] = 'Page ' . $structures->currentPage() . ' of ' . $structures->lastPage();
			$result['Total number of records'] = $structures->total();

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

	public function complete($formId)
    {
        try {
            $userId = $this->request->user()->id;
            $data = $this->request->all();
            $data['status'] = $data['status'] == true ? self::COMPLETED : self::PREPARED;
            $data['userName'] = $userId;
			$data['form_id'] = $formId;
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			$primaryKeys = \App\Survey::find($formId)->form_keys;
			$condition = ['userName' => $userId];
			foreach ($data as $field => $value) {
				if (in_array($field, $primaryKeys) && !empty($value)) {
					if ($field == 'village') {
						$field .= '_id';
					}
					$condition[$field] = $value;
				}
			}
            $structureTracking = StructureTracking::updateOrCreate($condition, $data);
            if (isset($data['village']) && ($structureTracking->village_id === null || ($structureTracking->village_id !== null && $structureTracking->village_id != $data['village']))) {
                $village = Village::find($data['village']);
				if ($structureTracking->village_id !== null) {
					$structureTracking->village()->dissociate();
				}
                $structureTracking->village()->associate($village);
                $structureTracking->save();
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
                'message' => 'Structure ' . $data['status'] . ' successfully.'
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

    public function updateComplete($structureId)
    {
        try {
            $userId = $this->request->user()->id;
            $data = $this->request->all();
            $data['status'] = $data['status'] == true ? self::COMPLETED : self::PREPARED;
            $data['userName'] = $userId;
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }

            $structureTracking = StructureTracking::find($structureId);

            if(empty($structureTracking))
                return response()->json([
                    'status' => 'error',
                    'data' => '',
                    'message' => 'Update Failed as record does not exist!'
                ]);
            
            $structureTracking->update($data);

            if (isset($data['village']) && $structureTracking->village_id !== null && $structureTracking->village_id != $data['village']) {
                $village = Village::find($data['village']);
				$structureTracking->village()->dissociate();
                $structureTracking->village()->associate($village);
                $structureTracking->save();
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
                'message' => 'Structure ' . $data['status'] . ' successfully.'
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

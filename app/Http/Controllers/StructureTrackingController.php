<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\StructureTracking;
use App\Volunteer;
use App\FFAppointed;
use App\Village;

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

    public function prepare()
    {
        try {
            $userId = $this->request->user()->id;
            $data = $this->request->all();
            $data['status'] = self::PREPARED;
            $data['created_by'] = $userId;
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
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
                'data' => ['preparationId' => $structureTracking->getIdAttribute()],
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

    public function complete()
    {
        try {
            $userId = $this->request->user()->id;
            $data = $this->request->all();
            $data['status'] = $data['status'] == true ? self::COMPLETED : self::PREPARED;
            $data['created_by'] = $userId;
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
            $structureTracking = StructureTracking::updateOrCreate(['village_id' => $data['village'], 'structure_code' => $data['structure_code']], $data);
            if (isset($data['village']) && $structureTracking->village !== null && $structureTracking->village->id != $data['village']) {
                $village = Village::find($data['village']);
                $structureTracking->village()->associate($village);
                $structureTracking->save();
            }
            return response()->json([
                'status' => 'success',
                'data' => ['completionId' => $structureTracking->getIdAttribute()],
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

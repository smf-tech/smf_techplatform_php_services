<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use App\StructureTracking;
use Carbon\Carbon;
use App\Volunteer;
use App\FFAppointed;

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
            $databaseName = $this->connectTenantDatabase($this->request);
            $structureTracking = StructureTracking::create($data);
            $ffInstance = FFAppointed::create([
                'name' => $data['ff_name'],
                'mobile_number' => $data['ff_mobile_number'],
                'training_completed' => $data['ff_training_completed']
            ]);
            $ffInstance->structureTracking()->associate($structureTracking);
            $ffInstance->save();
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
            if (!$this->request->filled('prepared')) {
                return response()->json(
                        [
                        'status' => 'error',
                        'data' => null,
                        'message' => 'prepared parameter is missing'
                    ],
                    400
                );
            }
            $databaseName = $this->connectTenantDatabase($this->request);
            $prepared = $this->request->prepared === 'true' ? self::COMPLETED : self::PREPARED;
            return response()->json([
                'status' => 'success',
                'data' => StructureTracking::where('status', $prepared)->with('ffs', 'volunteers')->get(),
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
            $data['status'] = self::COMPLETED;
            $data['created_by'] = $userId;
            $databaseName = $this->connectTenantDatabase($this->request);
            $structureTracking = StructureTracking::create($data);
            if (isset($data['ff_appointed']) && !empty($data['ff_appointed'])) {
                foreach ($data['ff_appointed'] as $singleFF) {
                    $ffInstance = FFAppointed::create($singleFF);
                    $ffInstance->structureTracking()->associate($structureTracking);
                    $ffInstance->save();
                }
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
                'data' => ['completionId' => $structureTracking->getIdAttribute()],
                'message' => 'Structure completed successfully.'
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

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
            if (isset($data['reporting_date']) && !empty($data['reporting_date'])) {
                $data['reporting_date'] = Carbon::createFromFormat('Y-m-d', $data['reporting_date'])->toDateTimeString();
            }
            $data['status'] = self::PREPARED;
            $data['created_by'] = $userId;
            $databaseName = $this->setDatabaseConfig($this->request);
            DB::setDefaultConnection($databaseName);
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
            $databaseName = $this->setDatabaseConfig($this->request);
            DB::setDefaultConnection($databaseName);
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
            foreach ($data as $key => &$value) {
                if (
                        in_array($key, ['reporting_date', 'work_start_date', 'work_end_date'])
                        &&
                        !empty($value)
                        &&
                        $value !== NULL
                    ) {
                    $value = Carbon::createFromFormat(
                            'Y-m-d',
                            $value
                        )->toDateTimeString();
                }
            }
            $data['status'] = self::COMPLETED;
            $data['created_by'] = $userId;
            $databaseName = $this->setDatabaseConfig($this->request);
            DB::setDefaultConnection($databaseName);
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

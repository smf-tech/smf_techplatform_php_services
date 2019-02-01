<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use App\StructureTracking;
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

    public function prepare()
    {
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
        $data['status'] = self::PREPARED;
        $data['created_by'] = $userId;
        $databaseName = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($databaseName);
        $structureTracking = StructureTracking::create($data);
        var_dump($structureTracking->getIdAttribute());
    }

}

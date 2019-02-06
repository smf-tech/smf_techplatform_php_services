<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use App\StructureMaster;

class StructureMasterController extends Controller
{
    use Helpers;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get()
    {
        try {
            $databaseName = $this->setDatabaseConfig($this->request);
            DB::setDefaultConnection($databaseName);
            return response()->json([
                'status' => 'success',
                'data' => StructureMaster::all('structure_code'),
                'message' => 'List of Structure codes.'
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

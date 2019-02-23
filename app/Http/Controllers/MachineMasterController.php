<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\MachineMaster;
use Illuminate\Support\Facades\DB;

class MachineMasterController extends Controller
{
    use Helpers;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getMachineCode()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        try {
            return response()->json([
                'status' => 'success',
                'data' => MachineMaster::all('machine_code'),
                'message' => 'List of Machine codes.'
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

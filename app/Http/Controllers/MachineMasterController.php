<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\MachineMaster;

class MachineMasterController extends Controller
{
    use Helpers;

    public function getMachineCode()
    {
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

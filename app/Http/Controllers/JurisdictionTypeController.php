<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use App\JurisdictionType;

class JurisdictionTypeController extends Controller
{
    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * 
     * @param string $id
     */
    public function index($id = null)
    {
        $databaseName = $this->setDatabaseConfig($this->request);

        if ($id !== null) {
            try {
                return response()->json(
                    [
                        'status' => 'success',
                        'data' => JurisdictionType::findOrFail($id),
                        'message' => 'Jurisdiction Type found with id ' . $id
                    ],
                    200
                );
            } catch(\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
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
        return response()->json(
            [
                'status' => 'success',
                'data' => JurisdictionType::all(),
                'message' => 'Jurisdiction Type list'
            ],
            200
        );
    }

}

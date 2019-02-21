<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use App\Report;

class ReportController extends Controller
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
        $databaseName = $this->connectTenantDatabase($this->request);
        if ($id !== null) {
            try {
                return response()->json(
                    [
                        'status' => 'success',
                        'data' => Report::findOrFail($id),
                        'message' => 'Report found with id ' . $id
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
                'data' => Report::all(),
                'message' => 'Jurisdiction Type list'
            ],
            200
        );
    }

}

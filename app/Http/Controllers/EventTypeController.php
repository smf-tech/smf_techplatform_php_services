<?php

namespace App\Http\Controllers;

use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\EventType;
use App\Survey;

use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EventTypeController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    
    public function getEventTypes(Request $request)
    {
        
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'Entity does not belong to any Organization.'], 403);
        }
        $responseData = EventType::query()->with('surveys')
                        ->get();
        return response()->json(['status'=>'success','data'=>$responseData ,'message'=>''],200);
    }

    
}

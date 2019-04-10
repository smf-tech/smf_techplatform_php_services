<?php

namespace App\Http\Controllers;

use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Entity;

use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EntityController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    
    public function getEntityInfo($entityId,$column)
    {
        if (!$this->request->filled('value')) {
            return response()->json(
                    [
                    'status' => 'error',
                    'data' => null,
                    'message' => 'Value parameter is missing'],
                400);
        }
        
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'Entity does not belong to any Organization.'], 403);
        }
        $responseData = [];
        $responseDataVal = [];
        $filterVal = $this->request->input('value');
        if ($filterVal == 'max') {
            $responseData = DB::table('entity_'.$entityId)->max($column);
            
            
        } elseif ($filterVal == 'min') {
            $responseData = DB::table('entity_'.$entityId)->min($column);
            
        }
        $responseDataVal = ['column'=>$column,'value'=>$responseData];
        return response()->json(['status'=>'success','data'=>$responseDataVal ,'message'=>''],200);
    }

    
}

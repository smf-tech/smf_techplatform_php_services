<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\MachineMaster;
use App\District;
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

    public function createMachineCode($formId)
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $values = [
            'TH' => ['Model -1' => [200, 'A'], 'Model -2' => [210, 'A'], 'Model -3' =>[220, 'B'] ],
            'JB' => ['Model -1' => [205, 'A'], 'Model -2' => [215,'A'], 'Model -3' =>[220, 'B']],
            'HY' => ['Model -1' => [210, 'A'], 'Model -2' => [215,'A'], 'Model -3' =>[]],
            'SN' => ['Model -1' => [210,'A'], 'Model -2' => [220,'A'], 'Model -3' =>[] ],
            'KB' => ['Model -1' => [210,'B'], 'Model -2' => [220,'B'], 'Model -3' =>[] ],
            'KM' => ['Model -1' => [210,'B'], 'Model -2' =>[], 'Model -3' =>[]],
            'VL' => ['Model -1' => [210,'B'], 'Model -2' =>[], 'Model -3' =>[]],
            'CT' => ['Model -1' => [320,'B'], 'Model -2' =>[], 'Model -3' =>[]]
        ];

        $data = $this->request->all();
        $district = District::find($this->request->input('district_id'));
        // $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->get(['machine_code']);
        
        
        
        
        // $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->max('machine_code');
        $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->orderBy('created_at','desc')->first();
        
        
        
        
        
        
        return number_format($machines->machine_code);
        // 
        
        // $queueValue = substr($machines,2,3) + 1;
        $queueValue = (int) (substr($machines,2,-6)) + 1;
        return $queueValue;
        $modelNumber = $values[$this->request->input('machine_make')][$this->request->input('machine_model')];
        if(empty($modelNumber)) {
            return response()->json(
                [
                    'status' => 'error',
                    'data' => '',
                    'message' => 'Invalid Entry For Machine Model'
                ],
                400
            );
        }
        $finalCode = $district->abbr.$queueValue.$this->request->input('machine_make').$modelNumber[0].$modelNumber[1];
        
        $data['userName'] = $this->request->user()->id;
        $data['machine_code'] = $finalCode;

        $machineRecord = MachineMaster::create($data);
        
        $record['_id']['$oid'] = $machineRecord->id;
        $record['form_title'] = $this->generateFormTitle($formId,$record['_id']['$oid'],'machine_masters');

        return response()->json([
            'status' => 'success',
            'data' => $record,
            'message' => 'Creation of a new record in Machine Master'
        ],201);
    }
}

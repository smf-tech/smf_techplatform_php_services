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
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database);      

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

    public function createMachineCode()
    {
        $values = [
            'TH' => ['Model -1' => [200, 'A'], 'Model -2' => [210, 'A'], 'Model -3' =>[220, 'B'] ],
            'JB' => ['Model -1' => [205, 'A'], 'Model -2' => [215,'A'], 'Model -3' =>[220, 'B']],
            'HY' => ['Model -1' => [210, 'A'], 'Model -2' => [215,'A']],
            'SN' => ['Model -1' => [210,'A'], 'Model -2' => [220,'A'] ],
            'KB' => ['Model -1' => [210,'B'], 'Model -2' => [220,'B'] ],
            'KM' => ['Model -1' => [210,'B']],
            'VL' => ['Model -1' => [210,'B']],
            'CT' => ['Model -1' => [320,'B']]
        ];

        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 

        // return gettype($this->request->all()); //array

        $data = $this->request->all();
        $district = District::find($this->request->input('district_id'));
        // $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->get(['machine_code']);
        // return $machines;
        $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->max('machine_code');
        // $queueValue = substr($machines,2,3) + 1;
        $queueValue = substr($machines,2,-6) + 1;
        // return $machines.'   '.substr($machines,2,-6);
        $modelNumber = $values[$this->request->input('machine_make')][$this->request->input('machine_model')];
        $finalCode = $district->abbr.$queueValue.$this->request->input('machine_make').$modelNumber[0].$modelNumber[1];
        
        $data['created_by'] = $this->request->user()->id;
        $data['machine_code'] = $finalCode;

        return response()->json([
            'status' => 'success',
            'data' => MachineMaster::create($data),
            'message' => 'Creation of a new record in Machine Master'
        ],200);
    }
}

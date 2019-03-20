<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\MachineMaster;
use App\District;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\MachineTracking;

class MachineMasterController extends Controller
{
    use Helpers;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getMachineCode()
    {
        try {
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$userLocation = $this->request->user()->location;
			$machine = MachineMaster::where('machine_code', '!=', null);
			foreach ($userLocation as $level => $location) {
				$machine->whereIn(strtolower($level) . '_id', $location);
			}
			$machine->with('state', 'district', 'taluka');
            return response()->json([
                'status' => 'success',
                'data' => $machine->get(['machine_code', 'state_id', 'district_id', 'taluka_id']),
                'message' => 'List of Machine codes.'
            ],200);
        } catch(\Exception $exception) {
            return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
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

        /*$values = [
            'TH' => ['Model -1' => [200, 'A'], 'Model -2' => [210, 'A'], 'Model -3' =>[220, 'B'] ],
            'JB' => ['Model -1' => [205, 'A'], 'Model -2' => [215,'A'], 'Model -3' =>[220, 'B']],
            'HY' => ['Model -1' => [210, 'A'], 'Model -2' => [215,'A'], 'Model -3' =>[]],
            'SN' => ['Model -1' => [210,'A'], 'Model -2' => [220,'A'], 'Model -3' =>[] ],
            'KB' => ['Model -1' => [210,'B'], 'Model -2' => [220,'B'], 'Model -3' =>[] ],
            'KM' => ['Model -1' => [210,'B'], 'Model -2' =>[], 'Model -3' =>[]],
            'VL' => ['Model -1' => [210,'B'], 'Model -2' =>[], 'Model -3' =>[]],
            'CT' => ['Model -1' => [320,'B'], 'Model -2' =>[], 'Model -3' =>[]]
        ];*/

        $data = $this->request->all();
        $userId = $this->request->user()->id;
        $district = District::find($this->request->input('district_id'));
        // $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->get(['machine_code']);
        // $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->max('machine_code');
        $machines = MachineMaster::where('machine_code','LIKE',$district->abbr.'%')->orderBy('createdDateTime','desc')->first();
        // $queueValue = substr($machines,2,3) + 1;
        $queueValue = (int) (substr($machines->machine_code,2,-6)) + 1;
        /*$modelNumber = $values[$this->request->input('machine_make')][$this->request->input('machine_model')];
        if(empty($modelNumber)) {
            return response()->json(
                [
                    'status' => 'error',
                    'data' => '',
                    'message' => 'Invalid Entry For Machine Model'
                ],
                400
            );
        }*/
        $modelcode = '';
        if($this->request->input('machine_make') == 'TH'){
            if($this->request->input('machine_model') == '220'){
                $modelcode = 'B';
            }
            if(in_array($this->request->input('machine_model'),array('200','210'))){
                $modelcode = 'A';
            }
        }
        if($this->request->input('machine_make') == 'JB'){
            if($this->request->input('machine_model') == '220'){
                $modelcode = 'B';
            } 
            if(in_array($this->request->input('machine_model'),array('205','215'))){
                $modelcode = 'A';
            }       
        }
        if($this->request->input('machine_make') == 'HY'){
    
            if(in_array($this->request->input('machine_model'),array('210','215'))){
                $modelcode = 'A';
            }
        }
        if($this->request->input('machine_make') == 'SN'){

            if(in_array($this->request->input('machine_model'),array('210','220'))){
                $modelcode = 'A';
            }
        }
        if($this->request->input('machine_make') == 'KB'){
            if(in_array($this->request->input('machine_model'),array('210','220'))){
                $modelcode = 'B';
            }
        }
        if($this->request->input('machine_make') == 'KM'){
            if($this->request->input('machine_model') == '210'){
                $modelcode = 'B';
            }
        }
        if($this->request->input('machine_make') == 'VL'){
            if($this->request->input('machine_model') == '210'){
                $modelcode = 'B';
            }
        }
        if($this->request->input('machine_make') == 'CT'){
            if($this->request->input('machine_model') == '320'){
                $modelcode = 'B';
            }
        }
        $finalCode = $district->abbr.$queueValue.$this->request->input('machine_make').$this->request->input('machine_model').$modelcode;

        $primaryKeys = \App\Survey::find($formId)->form_keys;
			$condition = ['userName' => $userId];
            $associatedFields = array_map('strtolower', $this->getLevels()->toArray());
            
            $machineRecord = new MachineMaster;

			foreach ($data as $field => $value) {
				
				if (in_array($field, $associatedFields)) {
					if (in_array($field, $primaryKeys) && !empty($value)) {
						$field .= '_id';
						$condition[$field] = $value;
					} else {
						$field .= '_id';
					}
				}
				if (in_array($field, $primaryKeys) && !empty($value)) {
					$condition[$field] = $value;
				}
				$machineRecord->$field = $value;
			}

        $machineRecord->userName = $userId;
        $machineRecord->machine_code = $finalCode;
        $machineRecord->form_id = $formId;
        $machineRecord->isDeleted = false;
        $machineRecord->save();
        
        $record['_id']['$oid'] = $machineRecord->id;
        $record['form_title'] = $this->generateFormTitle($formId,$record['_id']['$oid'],'machine_masters');
		$record['createdDateTime'] = $machineRecord->createdDateTime;
		$record['updatedDateTime'] = $machineRecord->updatedDateTime;

        return response()->json([
            'status' => 'success',
            'data' => $record,
            'message' => 'Create Record in Machine Master'
        ],200);
    }

    public function getMachineCodes($formId){
        try {
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$userName = $this->request->user()->id;
			$limit = (int)$this->request->input('limit') ?:50;
			$offset = $this->request->input('offset') ?:0;
			$order = $this->request->input('order') ?:'desc';
			$field = $this->request->input('field') ?:'createdDateTime';
			$page = $this->request->input('page') ?:1;
			$startDate = $this->request->filled('start_date') ? $this->request->start_date : Carbon::now()->subMonth()->getTimestamp();
			$endDate = $this->request->filled('end_date') ? $this->request->end_date : Carbon::now()->getTimestamp();

			$machine_masters= MachineMaster::where('userName', $userName)
					->where('form_id', $formId)
                    ->whereBetween('createdDateTime', [$startDate, $endDate])                    
                    ->where('isDeleted','!=',true)
                    ->with('state','district','taluka')
					->orderBy($field, $order)
					->paginate($limit);

			if ($machine_masters->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => ''],200);
			}
			$createdDateTime = $machine_masters->first()['createdDateTime'];
			$updatedDateTime = $machine_masters->last()['updatedDateTime'];
			$resonseCount = $machine_masters->count();

			$result = [
				'form' => [
					'form_id' => $formId,
					'userName' => $machine_masters->first()['userName'],
					'createdDateTime' => $createdDateTime,
					'updatedDateTime' => $updatedDateTime,
					'submit_count' => $resonseCount
				]
			];

			$values = [];
			foreach ($machine_masters as &$structure) {
				foreach (array_map('strtolower', $this->getLevels()->toArray()) as $singleJurisdiction) {
					if (isset($structure[$singleJurisdiction])) {
						unset($structure[$singleJurisdiction]);
						$structure[$singleJurisdiction] = $structure[$singleJurisdiction . '_id'];
						unset($structure[$singleJurisdiction . '_id']);
					}
				}
				$structure['form_title'] = $this->generateFormTitle($formId, $structure['_id'], 'machine_masters');
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'userName', 'createdDateTime']);
			}

			$result['Current page'] = 'Page ' . $machine_masters->currentPage() . ' of ' . $machine_masters->lastPage();
			$result['Total number of records'] = $machine_masters->total();

			return response()->json([
				'status' => 'success',
				'metadata' => [$result],
				'values' => $values,
				'message '=> ''
            ],200);
		} catch(\Exception $exception) {
			return response()->json(
				[
					'status' => 'error',
					'data' => '',
					'message' => $exception->getMessage()
				],
				404
			);
		}      
    }

    public function deleteMachine($recordId)
	{
        try {

        $database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}

        $machine = MachineMaster::find($recordId);

        if(empty($machine)) {
            return response()->json(
				[
					'status' => 'error',
					'data' => '',
					'message' => "Record does not exist"
				],
				404
            );
        }

        if((isset($machine->userName) && $this->request->user()->id !== $machine->userName) || (isset($machine->created_by) && $this->request->user()->id !== $machine->created_by)) {
            return response()->json(
				[
					'status' => 'error',
					'data' => '',
					'message' => "Record cannot be deleted as you are not the creator of the record"
				],
				403
            );
        }

        $machines = MachineTracking::where('machine_code',$machine->machine_code)
                                        ->where('isDeleted','!=',true)
                                        ->update(['isDeleted' => true]);

        $machine->isDeleted = true;
        $machine->save();

        return response()->json(
            [
                'status' => 'success',
                'data' => '',
                'message' => "Record deleted successfully"
            ],
            200
        );

        } catch(\Exception $exception) {
            return response()->json(
				[
					'status' => 'error',
					'data' => '',
					'message' => $exception->getMessage()
				],
				404
            );
        }
        
    }
}

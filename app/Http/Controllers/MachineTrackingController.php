<?php

namespace App\Http\Controllers;

use App\MachineTracking;
use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Entity;
use App\Microservice;
use App\ShiftingRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\MachineMou;



use App\Images;

class MachineTrackingController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    public function machineDeploy()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        $deployedMachine = new MachineTracking;
        $deployedMachine->village()->associate(\App\Village::find($this->request->village));

        $deployedMachine->date_deployed = $this->request->date_deployed;
        $deployedMachine->structure_code = $this->request->structure_code;
        $deployedMachine->machine_code = $this->request->machine_code;
        $deployedMachine->deployed = true;
        $deployedMachine->status = $this->request->status;

        if($this->request->filled('last_deployed')) {
            $deployedMachine->last_deployed = $this->request->last_deployed;
        }

        if($this->request->filled('mou_id')) {
            $machine = MachineMou::where('mou_id',$this->request->input('mou_id'))->first();
            $deployedMachine->mou_details = $machine->toArray();
        }

        $deployedMachine->created_by = $this->request->user()->id;
        $deployedMachine->save();

        $deploymentId = [ 'deploymentId'=> $deployedMachine->id];
        
        return response()->json(['status'=>'success','data'=>$deploymentId,'message'=>'']);
    }

    public function getDeploymentInfo()
    {
		try {
			if (!$this->request->filled('deployed')) {
                return response()->json(
                        [
                        'status' => 'error',
                        'data' => null,
                        'message' => 'deployed parameter is missing'
                    ],
                    400
                );
            }
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}
			$userLocation = $this->request->user()->location;
			$machines = [];
			if (isset($userLocation['village']) && !empty($userLocation['village'])) {
				if ($this->request->deployed === 'true') {
					$machines = MachineTracking::where('deployed',true)->whereIn('village_id', $userLocation['village'])->with('village')->get();
				} else {
					$machineCodes = [];
					$machineLevels = ['state', 'district', 'taluka'];
					$machineTrackingRecords = MachineTracking::whereIn('village_id', $userLocation['village'])->get();
					$machineTrackingRecords->each(function($machineTracking, $key) {
						$machineCodes[] = $machineTracking->machine_code;
					});
					$machineRecords = \App\MachineMaster::whereNotIn('machine_code', $machineCodes);
					foreach ($userLocation as $level => $location) {
						if (in_array($level, $machineLevels)) {
							$machineRecords->whereIn($level . '_id', $location);
						}
					}
					$machines = $machineRecords->get();
				}
			}
			return response()->json([
				'status' => 'success',
				'data' => $machines,
				'message' => 'List of machines.'
			]);
		} catch(\Exception $exception) {
			return response()->json([
                        'status' => 'error',
                        'data' => null,
                        'message' => $exception->getMessage()
                    ], 400);
		}
    }

    public function machineShift()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        $data = $this->request->all();
        // $machine = MachineTracking::where('village_id',$this->request->moved_from_village)
        //                         ->where('structure_code',$this->request->old_structure_code)
        //                         ->where('machine_code',$this->request->machine_code)
        //                         ->first();
        $machine = MachineTracking::firstOrCreate([
            'village_id' => $this->request->moved_from_village,
            'structure_code' => $this->request->old_structure_code,
            'machine_code' => $this->request->machine_code
        ]);

        $shiftingRecord = ShiftingRecord::create($data);
        $shiftingRecord->movedFromVillage()->associate(\App\Village::find($data['moved_from_village']));
        $shiftingRecord->movedToVillage()->associate(\App\Village::find($data['moved_to_village']));
        $shiftingRecord->machineTracking()->associate($machine);
        $shiftingRecord->save();

        $shifting_id = $shiftingRecord->getIdAttribute();

        $record_id = [ 'shiftingId' => $shifting_id];

        $machine->status = 'shifted';

        $machine->save();

        return response()->json(['status'=>'success','data'=>$record_id,'message'=>'']);
    }

    public function getShiftingInfo()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        return response()->json([
                                    'status'=>'success',
                                    'data'=> MachineTracking::where('status','shifted')
                                                            ->with('shiftingRecords', 'shiftingRecords.movedFromVillage', 'shiftingRecords.movedToVillage', 'village')
                                                            ->get(),
                                    'message'=>'']); 
    }

    public function machineMoU()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        
        $machine = MachineTracking::where('mou_details','!=',null)->get();
        
        return response()->json(['status'=>'success','data'=>$machine,'message'=>'']);    
    }

}

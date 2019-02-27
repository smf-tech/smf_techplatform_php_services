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
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }

        if($this->request->input('deployed'))
        {
            $deployedMachines = MachineTracking::where('deployed',true)->with('village')->get();
        }
        
        return response()->json(['status'=>'success','data'=>$deployedMachines,'message'=>'']);
    }

    public function machineShift()
    {
        $database = $this->connectTenantDatabase($this->request);
        if ($database === null) {
            return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
        }
        $data = $this->request->all();
        $machine = MachineTracking::where('village_id',$this->request->moved_from_village)
                                ->where('structure_code',$this->request->old_structure_code)
                                ->where('machine_code',$this->request->machine_code)
                                ->first();

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

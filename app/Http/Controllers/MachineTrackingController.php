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
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database);      

        $deployedMachine = new MachineTracking;
        $deployedMachine->village = $this->request->village;

        $dateTime = Carbon::now()->toDateTimeString();

        $deployedMachine->date_deployed = $dateTime;
        $deployedMachine->structure_code = $this->request->structure_code;
        $deployedMachine->machine_code = $this->request->machine_code;
        $deployedMachine->deployed = true;
        $deployedMachine->status = $this->request->status;

        if($this->request->filled('last_deployed')) {
            
            $dateTime = Carbon::createFromFormat(
                'Y-m-d',
                $this->request->last_deployed
            )->toDateTimeString();

            $deployedMachine->last_deployed = $dateTime;
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
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 

        if($this->request->input('deployed'))
        {
            $deployedMachines = MachineTracking::where('deployed',true)->get();
        }
        
        return response()->json(['status'=>'success','data'=>$deployedMachines,'message'=>'']);
    }

    public function machineShift()
    {
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 
        
        $machine = MachineTracking::where('village',$this->request->moved_from_village)
                                ->where('structure_code',$this->request->old_structure_code)
                                ->where('machine_code',$this->request->machine_code)
                                ->first();


        $data = $this->request->all();
                                
        $shiftingRecord = ShiftingRecord::create($data);

        $shifting_id = $shiftingRecord->getIdAttribute();

        $record_id = [ 'shiftingId' => $shifting_id];
        
        $machine->status = 'shifted';
        $machine->save();

        $machine->shiftingRecords()->save($shiftingRecord);

        return response()->json(['status'=>'success','data'=>$record_id,'message'=>'']);       
    }

    public function getShiftingInfo()
    {      
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 

        return response()->json([
                                    'status'=>'success',
                                    'data'=> MachineTracking::where('status','shifted')
                                                            ->with('shiftingRecords')
                                                            ->get(),
                                    'message'=>'']); 
    }

    public function machineMoU()
    {
        $database = $this->setDatabaseConfig($this->request);
        DB::setDefaultConnection($database); 
        
        $machine = MachineTracking::where('mou_details','!=',null)->get();
        
        return response()->json(['status'=>'success','data'=>$machine,'message'=>'']);    
    }

}

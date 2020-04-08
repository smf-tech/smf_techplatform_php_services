<?php 
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use DateTimeImmutable;
use DateTime;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;

use App\DownloadCertificatePDFRequest;
use App\TeacherNICData;
use App\MobileDispensarySevaVanDetails;
use App\MobileDispensarySevaDailyVehicleDetails;
use App\MobileDispensarySevaPatientDetails;

use PDF; 

use Illuminate\Support\Arr;
date_default_timezone_set('Asia/Kolkata'); 
class mobileDispensarySevaController extends Controller
{
    use Helpers;

    protected $request;
    
    public function __construct(Request $request){ 
    
        $this->request = $request;
        $this->logInfoPath = "logs/mobileDispensarySeva/DB/logs_".date('Y-m-d').'.log';
        $this->errorPath = "logs/mobileDispensarySeva/Error/logs_".date('Y-m-d').'.log'; 

    }

    public function selectVanForm(Request $request){
       
       DB::setDefaultConnection('mongodb');

       $vanData = MobileDispensarySevaVanDetails::get();
      //echo json_encode($vanData);
       //die();

        return view('mobileDispensarySeva.selectVan',compact('vanData'));

    }


    public function insertVanForm(Request $request){
        DB::setDefaultConnection('mongodb');
        return view('mobileDispensarySeva.insertVanForm');

    }

    public function testpage(Request $request){
        return view('mobileDispensarySeva.testPage');
    }

    public function webOptionView(Request $request){

        return view('mobileDispensarySeva.webpageOption');
    }

    public function insertVanInfo(Request $request)
    {
        DB::setDefaultConnection('mongodb');
        $formData = $request->all();
       $entered_vehicle_reg_no = $request->input('vehicle_reg_no');

        // $vanCodeArr = explode('_', $request->input('vanCode'));
        // //$vanCode = $vanCode['0'];

        $checkVanRegNoData = MobileDispensarySevaVanDetails::where('vehicle_reg_no',$entered_vehicle_reg_no)->first();
       // echo json_encode($checkVanRegNoData);
        if($checkVanRegNoData && $checkVanRegNoData['vehicle_reg_no']==$entered_vehicle_reg_no)
        {
                $msg = 'Vehicle registration number is already present.';
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.insertVanForm',compact(['msg'])); 
        }
        else{


            $vanDetailsRecord = MobileDispensarySevaVanDetails::get();
             $vehicleCount = count($vanDetailsRecord);
        




        $vehicleData = new MobileDispensarySevaVanDetails();
        //$vehicleData = new MobileDispensarySevaDailyVehicleDetails();
        foreach($formData as $key => $value)
        {
            $vehicleData[$key]= $value;
        }
        unset($vehicleData['vanCode']);
       
        $vehicleData['bjs_vehicle_no'] = $vehicleCount+1;
       
        $carbon = new Carbon();
        $currentDate = $carbon->setTimezone('Asia/Kolkata');
        $currentDate = $carbon->toDateTimeString();
        
        $dateOnly = $carbon->format('d-m-Y H:i:s');
        
        $vehicleData['created_datetime'] = $dateOnly;
       
        $vehicleData['created_at'] = $currentDate ? : '' ;
        $vehicleData['updated_at'] = $currentDate ? : '';
       
        // echo json_encode($vehicleData);
        // die();
        try{ 

                $success = $vehicleData->save();
            }
            catch(Exception $e)
            {
                $response_data = array('status' =>'200','message'=>'error','data' => $e);
                return response()->json($response_data,200); 
            }

            if($success)
            {
            
                $msg = 'Vehicle details inserted successfully.';
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.insertVanForm',compact(['msg'])); 
            }
            else
            {
                
                $msg = "Couldn't save Vehicle details, please try after some time.";
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.insertVanForm',compact(['msg']));  
            } 

        }
        
        

       
    }

    public function vanDetailsList(Request $request){

        DB::setDefaultConnection('mongodb');

       $vanDetailsList = MobileDispensarySevaVanDetails::get();
      //echo json_encode($vanData);
       //die();

        return view('mobileDispensarySeva.vanDetailsTable',compact('vanDetailsList'));

    }

    public function patientList(Request $request){
         DB::setDefaultConnection('mongodb');

         $patientList = MobileDispensarySevaPatientDetails::orderBy('created_at', 'DESC')->get();

      // echo json_encode($patientList);
      // die();
        return view('mobileDispensarySeva.patientListTable',compact('patientList'));
    }
    public function loadPatientForm(Request $request)
    {
        DB::setDefaultConnection('mongodb');
        $formData = $request->all();
        $vanCode = $request->input('vanCode');
        $vanCodeArr = explode('_', $request->input('vanCode'));
        //$vanCode = $vanCode['0'];
        $vehicleData = new MobileDispensarySevaDailyVehicleDetails();
        foreach($formData as $key => $value)
        {
            $vehicleData[$key]= $value;
        }
        unset($vehicleData['vanCode']);
        $vehicleData['vanCode'] = $vanCodeArr['0'];
        $vehicleData['vehicle_reg_no'] = $vanCodeArr['1'];
       
        $carbon = new Carbon();
        $currentDate = $carbon->setTimezone('Asia/Kolkata');
        $currentDate = $carbon->toDateTimeString();
        
        $dateOnly = $carbon->format('d-m-Y H:i:s');
        
        $vehicleData['created_datetime'] = $dateOnly;
       
        $vehicleData['created_at'] = $currentDate ? : '' ;
        $vehicleData['updated_at'] = $currentDate ? : '';
       

        try{ 

                $success = $vehicleData->save();
            }
            catch(Exception $e)
            {
                $response_data = array('status' =>'200','message'=>'error','data' => $e);
                return response()->json($response_data,200); 
            }

            if($success)
            {
            
                $msg = 'Vehicle daily record created successfully.';
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.patientInfoForm',compact(['vanCode','msg'])); 
            }
            else
            {
                
                $msg = "Couldn't save Vehicle record, please try after some time.";
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.patientInfoForm',compact(['vanCode','msg']));  
            } 

       
    }

    public function savePatientInfo(Request $request)
    {
        $formData = $request->all();
        $vanCode = $request->input('vanCode');
        
        $vanCodeArr = explode('(', $request->input('vanCode'));
        //echo json_encode(substr($vanCodeArr['1'], 0, -1));
        //die();
        DB::setDefaultConnection('mongodb');
        $patientData = new MobileDispensarySevaPatientDetails();
        foreach($formData as $key => $value)
        {
            $patientData[$key]= $value;
        }

        unset($patientData['vanCode']);
        $patientData['vanCode'] = $vanCodeArr['0'];
        $patientData['vehicle_reg_no'] = substr($vanCodeArr['1'], 0, -1);
        $carbon = new Carbon();
        $currentDate = $carbon->setTimezone('Asia/Kolkata');
        $currentDate = $carbon->toDateTimeString();
        $dateOnly = $carbon->format('d-m-Y H:i:s');
        
        $patientData['created_datetime'] = $dateOnly;
        $patientData['created_at'] = $currentDate ? : '' ;
        $patientData['updated_at'] = $currentDate ? : '';
       

        try{ 

                $success = $patientData->save();
            }
            catch(Exception $e)
            {
                $response_data = array('status' =>'200','message'=>'error','data' => $e);
                return response()->json($response_data,200); 
            }

            if($success)
            {
                $vanCode = $vanCodeArr['0'].'_'.substr($vanCodeArr['1'], 0, -1);
                $msg = 'Patient record inserted successfully.';
                $response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.patientInfoForm',compact(['vanCode','msg'])); 
            }
            else
            {
                $msg = "Couldn't create patient record, please try after some time.";
               // $response_data = array('status' =>400,'message'=>"Couldn't create patient record, please try after some time.");
                return view('mobileDispensarySeva.patientInfoForm',compact(['vanCode','msg']));
            } 
    }


    public function showPatientInfoForm(Request $request)
    {
       
        //$vanCode = 'Test';
        return view('mobileDispensarySeva.patientInfoForm'); 
    }


    
}

?>
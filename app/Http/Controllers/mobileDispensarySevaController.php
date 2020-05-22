<?php 
namespace App\Http\Controllers;


use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

use Illuminate\Support\Collection;


use DateTimeImmutable;
use DateTime;
use Carbon\Carbon;


use App\DownloadCertificatePDFRequest;
use App\TeacherNICData;
use App\MobileDispensarySevaVanDetails;
use App\MobileDispensarySevaDailyVehicleDetails;
use App\MobileDispensarySevaPatientDetails;
use App\mobileDispensarySevaPatientContactDetails;
use App\MobileDispensarySevaVanCity;
use App\MobileDispensarySevaVanDepot;

use PDF; 

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

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

       $vanData = MobileDispensarySevaVanDetails::orderBy('bjs_vehicle_no', 'ASC')->get();
      //echo json_encode($vanData);
       //die();

       $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
       $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();

        return view('mobileDispensarySeva.selectVan',compact(['vanData','cityData','depotData']));

    }


    public function insertVanForm(Request $request){
        DB::setDefaultConnection('mongodb');

          $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
          $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();
        //echo json_encode($vanData);
        //die();

        return view('mobileDispensarySeva.insertVanForm',compact(['cityData','depotData']));

        //return view('mobileDispensarySeva.insertVanForm');

    }

    public function showAddCityForm(Request $request){

         DB::setDefaultConnection('mongodb');

      

        return view('mobileDispensarySeva.insertCityForm');
    }

    public function showAddDepotForm(Request $request){

         DB::setDefaultConnection('mongodb');
        return view('mobileDispensarySeva.insertDepotForm');
    }

    

    public function testpage1(Request $request){


        $vanData = MobileDispensarySevaVanDetails::orderBy('bjs_vehicle_no', 'ASC')->get();
      //echo json_encode($vanData);
       //die();

       $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
       $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();

        //return view('mobileDispensarySeva.selectVan',compact(['vanData','cityData','depotData']));

        return view('mobileDispensarySeva.testPage',compact(['vanData','cityData','depotData']));
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
       //echo json_encode($checkVanRegNoData);
        if($checkVanRegNoData && $checkVanRegNoData['vehicle_reg_no']==$entered_vehicle_reg_no)
        {
                $msg = 'Vehicle registration number is already present.';
                $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
                $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();
                return view('mobileDispensarySeva.insertVanForm',compact(['cityData','depotData','msg'])); 
        }
        else{


            $vanDetailsRecord = MobileDispensarySevaVanDetails::where('vehicle_city',$request->input('vehicle_city'))->get();
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
                $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
                $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();
                return view('mobileDispensarySeva.insertVanForm',compact(['cityData','depotData','msg'])); 
            }
            else
            {
                
                $msg = "Couldn't save Vehicle details, please try after some time.";
                $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
                $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();
                return view('mobileDispensarySeva.insertVanForm',compact(['cityData','depotData','msg']));  
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

    public function dailyVehicleRegister(Request $request){
         DB::setDefaultConnection('mongodb');

         $vehicleDailyRegisterData = MobileDispensarySevaDailyVehicleDetails::orderBy('created_at', 'DESC')->get();

      // echo json_encode($patientList);
      // die();
        return view('mobileDispensarySeva.dailyVehicleRegTable',compact('vehicleDailyRegisterData'));
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
            
                 $msg = 'Van - Area Visit Register record submitted successfully.';
                
                 $vanData =  MobileDispensarySevaVanDetails::orderBy('bjs_vehicle_no', 'ASC')->get();
                 $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
                $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();
      

                //return view('mobileDispensarySeva.selectVan',compact('vanData'));
                return view('mobileDispensarySeva.selectVan',compact(['vanData','cityData','depotData','msg'])); 
            }
            else
            {
                $msg = "Couldn't submit Van - Area Visit Register record, please try after some time.";
                $vanData = MobileDispensarySevaVanDetails::orderBy('bjs_vehicle_no', 'ASC')->get();
                $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
                $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();
                
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.selectVan',compact(['vanData','cityData','depotData','msg']));   
            } 

       
    }

    public function testPageSave(Request $request)
    {
        ini_set('upload_max_filesize', '40M');
        ini_set('post_max_size', '40M');
        ini_set('max_input_time', 500);
        ini_set('max_execution_time', 500);
        ini_set("display_errors", 0);

        $formData = $request->all();
        $file = $this->request->file('register_image');
        $url=[];

        if($this->request->file('register_images_one')){
            $fileOne = $this->request->file('register_images_one');
            $fileInstanceOne = $this->request->file('register_images_one');
            $nameOne = $fileInstanceOne->getClientOriginalName();
            $ext = $this->request->file('register_images_one')->getClientMimeType(); 
            //echo $ext;exit;
            $newNameOne = uniqid().'_'.$nameOne;//.'.jpg';
            $s3Path = $this->request->file('register_images_one')->storePubliclyAs(env('SEVA_IMAGE_PATH'), $newNameOne, 'octopusS3');
            
            $url[0] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SEVA_IMAGE_PATH').'/' . $newNameOne;
        }

        if($this->request->file('register_images_two')){
            $fileTwo = $this->request->file('register_images_two');
            $fileInstanceTwo = $this->request->file('register_images_two');
            $nameTwo = $fileInstanceTwo->getClientOriginalName();
            $ext = $this->request->file('register_images_two')->getClientMimeType(); 
            //echo $ext;exit;
            $newNameTwo = uniqid().'_'.$nameTwo;//.'.jpg';
            $s3Path = $this->request->file('register_images_two')->storePubliclyAs(env('SEVA_IMAGE_PATH'), $newNameTwo, 'octopusS3');
            
            $url[count($url)] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SEVA_IMAGE_PATH').'/' . $newNameTwo;
        }

        if($this->request->file('register_images_three')){
            $fileThree = $this->request->file('register_images_three');
            $fileInstanceThree = $this->request->file('register_images_three');
            $nameThree = $fileInstanceThree->getClientOriginalName();
            $ext = $this->request->file('register_images_three')->getClientMimeType(); 
            //echo $exthr;exit;
            $newNameThree = uniqid().'_'.$nameThree;//.'.jpg';
            $s3Path = $this->request->file('register_images_three')->storePubliclyAs(env('SEVA_IMAGE_PATH'), $newNameThree, 'octopusS3');
            
            $url[count($url)] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SEVA_IMAGE_PATH').'/' . $newNameThree;
        }

        if($this->request->file('register_images_four')){

            $fileFour = $this->request->file('register_images_four');
            $fileInstanceFour = $this->request->file('register_images_four');
            $nameFour = $fileInstanceFour->getClientOriginalName();
            $ext = $this->request->file('register_images_four')->getClientMimeType(); 
            //echo $exthr;exit;
            $newNameFour = uniqid().'_'.$nameFour;//.'.jpg';
            $s3Path = $this->request->file('register_images_four')->storePubliclyAs(env('SEVA_IMAGE_PATH'), $newNameFour, 'octopusS3');
            
            $url[count($url)] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SEVA_IMAGE_PATH').'/' . $newNameFour;
        }

        if($this->request->file('register_images_five')){
            $fileFive = $this->request->file('register_images_five');
            $fileInstanceFive = $this->request->file('register_images_five');
            $nameFive = $fileInstanceFive->getClientOriginalName();
            $ext = $this->request->file('register_images_five')->getClientMimeType(); 
            //echo $exthr;exit;
            $newNameFive = uniqid().'_'.$nameFive;//.'.jpg';
            $s3Path = $this->request->file('register_images_five')->storePubliclyAs(env('SEVA_IMAGE_PATH'), $newNameFive, 'octopusS3');
            
            $url[count($url)] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SEVA_IMAGE_PATH').'/' . $newNameFive;
        }

         print_r($url);
        die();



    }

    public function savePatientInfo(Request $request)
    {
		//print_r($this->request->file('register_image'));exit;
        $formData = $request->all();
        $file = $this->request->file('register_image');
        $url=[];
		
		 if($this->request->has('register_images_one_1')) {
			
            $fileOne = $this->request['register_images_one_1'];
			
			$location = storage_path()."/sevaImages/".$fileOne;
			
			$filecontent = file_get_contents($location);
			//$ext = pathinfo($filePath, PATHINFO_EXTENSION); 
			$fileName = basename($location);
			
			$aswFileName = env('SEVA_IMAGE_PATH').'/'.$fileName;
			Storage::disk('octopusS3')->put($aswFileName, $filecontent);
			Storage::disk('octopusS3')->setVisibility($aswFileName, 'public');
			$url1 = Storage::disk('octopusS3')->url($aswFileName);
			$fileName1 =  'https://'.env('OCT_AWS_CDN_PATH').'/'.env('SEVA_IMAGE_PATH').'/'.$fileName;
			unlink($location);

			//echo $fileName1;exit;
            $url[0] = $fileName1;
        }
		
		if($this->request->has('register_images_two_1')) {
			
            $fileOne = $this->request['register_images_two_1'];
			
			$location = storage_path()."/sevaImages/".$fileOne;
			
			$filecontent = file_get_contents($location);
			//$ext = pathinfo($filePath, PATHINFO_EXTENSION); 
			$fileName = basename($location);
			
			$aswFileName = env('SEVA_IMAGE_PATH').'/'.$fileName;
			Storage::disk('octopusS3')->put($aswFileName, $filecontent);
			Storage::disk('octopusS3')->setVisibility($aswFileName, 'public');
			$url1 = Storage::disk('octopusS3')->url($aswFileName);
			$fileName1 =  'https://'.env('OCT_AWS_CDN_PATH').'/'.env('SEVA_IMAGE_PATH').'/'.$fileName;
			unlink($location);
			//echo $fileName1;exit;
            $url[count($url)] = $fileName1;
        }
		
		
		if($this->request->has('register_images_three_1')) {
			
            $fileOne = $this->request['register_images_three_1'];
			
			$location = storage_path()."/sevaImages/".$fileOne;
			
			$filecontent = file_get_contents($location);
			//$ext = pathinfo($filePath, PATHINFO_EXTENSION); 
			$fileName = basename($location);
			
			$aswFileName = env('SEVA_IMAGE_PATH').'/'.$fileName;
			Storage::disk('octopusS3')->put($aswFileName, $filecontent);
			Storage::disk('octopusS3')->setVisibility($aswFileName, 'public');
			$url1 = Storage::disk('octopusS3')->url($aswFileName);
			$fileName1 =  'https://'.env('OCT_AWS_CDN_PATH').'/'.env('SEVA_IMAGE_PATH').'/'.$fileName;
			unlink($location);
			//echo $fileName1;exit;
            $url[count($url)] = $fileName1;
        }



		if($this->request->has('register_images_four_1')) {
			
            $fileOne = $this->request['register_images_four_1'];
			
			$location = storage_path()."/sevaImages/".$fileOne;
			
			$filecontent = file_get_contents($location);
			//$ext = pathinfo($filePath, PATHINFO_EXTENSION); 
			$fileName = basename($location);
			
			$aswFileName = env('SEVA_IMAGE_PATH').'/'.$fileName;
			Storage::disk('octopusS3')->put($aswFileName, $filecontent);
			Storage::disk('octopusS3')->setVisibility($aswFileName, 'public');
			$url1 = Storage::disk('octopusS3')->url($aswFileName);
			$fileName1 =  'https://'.env('OCT_AWS_CDN_PATH').'/'.env('SEVA_IMAGE_PATH').'/'.$fileName;
			unlink($location);
			//echo $fileName1;exit;
            $url[count($url)] = $fileName1;
        }

		if ($this->request->has('register_images_five_1')) {
			
            $fileOne = $this->request['register_images_five_1'];
			
			$location = storage_path()."/sevaImages/".$fileOne;
			
			$filecontent = file_get_contents($location);
			//$ext = pathinfo($filePath, PATHINFO_EXTENSION); 
			$fileName = basename($location);
			
			$aswFileName = env('SEVA_IMAGE_PATH').'/'.$fileName;
			Storage::disk('octopusS3')->put($aswFileName, $filecontent);
			Storage::disk('octopusS3')->setVisibility($aswFileName, 'public');
			$url1 = Storage::disk('octopusS3')->url($aswFileName);
			$fileName1 =  'https://'.env('OCT_AWS_CDN_PATH').'/'.env('SEVA_IMAGE_PATH').'/'.$fileName;
			unlink($location);
			//echo $fileName1;exit;
            $url[count($url)] = $fileName1;
        }

       

        /*if($this->request->file('register_images_one')){
	        $fileOne = $this->request->file('register_images_one');
	        $fileInstanceOne = $this->request->file('register_images_one');
	        $nameOne = $fileInstanceOne->getClientOriginalName();
	        $ext = $this->request->file('register_images_one')->getClientMimeType(); 
	        //echo $ext;exit;
	        $newNameOne = uniqid().'_'.$nameOne;//.'.jpg';
	        $s3Path = $this->request->file('register_images_one')->storePubliclyAs(env('SEVA_IMAGE_PATH'), $newNameOne, 'octopusS3');
	        
	        $url[0] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SEVA_IMAGE_PATH').'/' . $newNameOne;
    	}

        if($this->request->file('register_images_two')){
            $fileTwo = $this->request->file('register_images_two');
            $fileInstanceTwo = $this->request->file('register_images_two');
            $nameTwo = $fileInstanceTwo->getClientOriginalName();
            $ext = $this->request->file('register_images_two')->getClientMimeType(); 
            //echo $ext;exit;
            $newNameTwo = uniqid().'_'.$nameTwo;//.'.jpg';
            $s3Path = $this->request->file('register_images_two')->storePubliclyAs(env('SEVA_IMAGE_PATH'), $newNameTwo, 'octopusS3');
            
            $url[count($url)] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SEVA_IMAGE_PATH').'/' . $newNameTwo;
        }

        if($this->request->file('register_images_three')){
            $fileThree = $this->request->file('register_images_three');
            $fileInstanceThree = $this->request->file('register_images_three');
            $nameThree = $fileInstanceThree->getClientOriginalName();
            $ext = $this->request->file('register_images_three')->getClientMimeType(); 
            //echo $exthr;exit;
            $newNameThree = uniqid().'_'.$nameThree;//.'.jpg';
            $s3Path = $this->request->file('register_images_three')->storePubliclyAs(env('SEVA_IMAGE_PATH'), $newNameThree, 'octopusS3');
            
            $url[count($url)] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SEVA_IMAGE_PATH').'/' . $newNameThree;
        }

        if($this->request->file('register_images_four')){

            $fileFour = $this->request->file('register_images_four');
            $fileInstanceFour = $this->request->file('register_images_four');
            $nameFour = $fileInstanceFour->getClientOriginalName();
            $ext = $this->request->file('register_images_four')->getClientMimeType(); 
            //echo $exthr;exit;
            $newNameFour = uniqid().'_'.$nameFour;//.'.jpg';
            $s3Path = $this->request->file('register_images_four')->storePubliclyAs(env('SEVA_IMAGE_PATH'), $newNameFour, 'octopusS3');
            
            $url[count($url)] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SEVA_IMAGE_PATH').'/' . $newNameFour;
        }

        if($this->request->file('register_images_five')){
            $fileFive = $this->request->file('register_images_five');
            $fileInstanceFive = $this->request->file('register_images_five');
            $nameFive = $fileInstanceFive->getClientOriginalName();
            $ext = $this->request->file('register_images_five')->getClientMimeType(); 
            //echo $exthr;exit;
            $newNameFive = uniqid().'_'.$nameFive;//.'.jpg';
            $s3Path = $this->request->file('register_images_five')->storePubliclyAs(env('SEVA_IMAGE_PATH'), $newNameFive, 'octopusS3');
            
            $url[count($url)] = 'https://' . env('OCT_AWS_CDN_PATH') . '/'.env('SEVA_IMAGE_PATH').'/' . $newNameFive;
        }*/
     
       
        $this->logData($this->logInfoPath ,$formData,'DB');
        //$vanCode =$this->request->input('vanCode');
         
        if($request->input('vanCode')!='')
        {
        	$vanCodeArr = explode('_', $request->input('vanCode'));
        
	        DB::setDefaultConnection('mongodb');
	        $patientData = new MobileDispensarySevaPatientDetails();
	        foreach($formData as $key => $value)
	        {
	            $patientData[$key]= $value;
	        }

	        unset($patientData['vanCode']);
	        unset($patientData['register_images_one']);
	        unset($patientData['register_images_two']);
	        unset($patientData['register_images_three']);
	        unset($patientData['register_images_four']);
	        unset($patientData['register_images_five']);
			
			unset($patientData['register_images_one_1']);
	        unset($patientData['register_images_two_1']);
	        unset($patientData['register_images_three_1']);
	        unset($patientData['register_images_four_1']);
	        unset($patientData['register_images_five_1']);
			
	        $patientData['vanCode'] = $vanCodeArr['0']??'';
	        $patientData['vehicle_reg_no'] = $vanCodeArr['1']??'';
	        $patientData['register_image'] = $url;
	        $carbon = new Carbon();
	        $currentDate = $carbon->setTimezone('Asia/Kolkata');
	        $currentDate = $carbon->toDateTimeString();
	        $dateOnly = $carbon->format('d-m-Y H:i:s');
	        
	        $patientData['created_datetime'] = $dateOnly;
	        $patientData['created_at'] = $currentDate ? : '' ;
	        $patientData['updated_at'] = $currentDate ? : '';
	       //var_dump($patientData);
	       // die();

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
	                $vanData = MobileDispensarySevaVanDetails::get();
                    $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
                    $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();
	                $msg = 'Patient record inserted successfully.';
	               // $response_data = array('status' =>'200','message'=>$msg);
	                return view('mobileDispensarySeva.patientInfoForm',compact(['vanData','cityData','depotData','msg'])); 
	            }
	            else
	            {
	                $vanData = MobileDispensarySevaVanDetails::get();
                    $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
                    $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();
	                $errMsg = "धीमे नेटवर्क के कारण फ़ॉर्म सबमिट नहीं किया जा सका। कृपया पुनः प्रयास करें।";
	                return view('mobileDispensarySeva.patientInfoForm',compact(['vanData','cityData','depotData','errMsg']));
	            }
        }
        else
        {

        	$vanData = MobileDispensarySevaVanDetails::get();
            $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
            $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();
            $errMsg = "धीमे नेटवर्क के कारण फ़ॉर्म सबमिट नहीं किया जा सका। कृपया पुनः प्रयास करें।";
            return view('mobileDispensarySeva.patientInfoForm',compact(['vanData','cityData','depotData','errMsg']));
        }	
         
         
    }

    public function showPatientContactDetailsForm(Request $request){
      //  DB::setDefaultConnection('mongodb');
        return view('mobileDispensarySeva.patientContactDetailsForm');

    }
   
    public function savePatientContactDetails(Request $request)
    {

         DB::setDefaultConnection('mongodb');
        $formData = $request->all();

        $patientData = new mobileDispensarySevaPatientContactDetails();
        //$vehicleData = new MobileDispensarySevaDailyVehicleDetails();
        foreach($formData as $key => $value)
        {
            $patientData[$key]= $value;
        }
        
        $carbon = new Carbon();
        $currentDate = $carbon->setTimezone('Asia/Kolkata');
        $currentDate = $carbon->toDateTimeString();
        
        $dateOnly = $carbon->format('d-m-Y H:i:s');
        
        $patientData['created_datetime'] = $dateOnly;
       
        $patientData['created_at'] = $currentDate ? : '' ;
        $patientData['updated_at'] = $currentDate ? : '';
       
        // echo json_encode($vehicleData);
        // die();
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
            
                $msg = 'Patient details inserted successfully.';
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.patientContactDetailsForm',compact(['msg'])); 
            }
            else
            {
                
                $msg = "Couldn't save Patient details, please try after some time.";
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.patientContactDetailsForm',compact(['msg']));  
            } 

    }


    public function patientContactDetailsList(Request $request){
         DB::setDefaultConnection('mongodb');

         $vehicleDailyRegisterData = mobileDispensarySevaPatientContactDetails::orderBy('created_at', 'DESC')->get();

      // echo json_encode($patientList);
      // die();
        return view('mobileDispensarySeva.patientContactDetailsTable',compact('vehicleDailyRegisterData'));
    }


    public function showPatientInfoForm(Request $request)
    {
       
        //$vanCode = 'Test';DB::setDefaultConnection('mongodb');

       $vanData =  MobileDispensarySevaVanDetails::orderBy('bjs_vehicle_no', 'ASC')->get();
       $cityData = MobileDispensarySevaVanCity::orderBy('city_name', 'ASC')->get();
       $depotData = MobileDispensarySevaVanDepot::orderBy('depot_name', 'ASC')->get();

        return view('mobileDispensarySeva.patientInfoForm',compact(['vanData','cityData','depotData']));
       // return view('mobileDispensarySeva.patientInfoForm'); 
    }


	
	public function imageSevaUpload(Request $request) {
		
		if($this->request->file('file')){
		
            $fileFive = $this->request->file('file');
            $fileInstanceFive = $this->request->file('file');			
			$imageName = time().'.'.$this->request->file('file')->getClientOriginalExtension();
			
			$location = storage_path()."/sevaImages/".$imageName;
			$original_filename_arr = explode('.', $imageName);
            $file_ext = end($original_filename_arr);
            $destination_path = storage_path()."/sevaImages/";
            $image = uniqid() . '.' . $file_ext;

            if ($request->file('file')->move($destination_path, $image)) {
				
				echo $image;  
			} else {
				echo "";
			}
           exit;   
		}
	}



    public function insertCity(Request $request)
    {
        DB::setDefaultConnection('mongodb');
        $formData = $request->all();
        $entered_city_name = $request->input('city_name');
        

        // $vanCodeArr = explode('_', $request->input('vanCode'));
        // //$vanCode = $vanCode['0'];

        $checkCityData = MobileDispensarySevaVanCity::where('city_name',$entered_city_name)->first();
       //echo json_encode($checkVanRegNoData);
        if($checkCityData && $checkCityData['city_name']==$entered_city_name)
        {
                $msg = 'City Name is already present.';
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.insertCityForm',compact(['msg'])); 
        }
        else{


        $cityData = new MobileDispensarySevaVanCity();
        //$cityData = new MobileDispensarySevaDailyVehicleDetails();
        foreach($formData as $key => $value)
        {
            $cityData[$key]= $value;
        }
        
       
        $carbon = new Carbon();
        $currentDate = $carbon->setTimezone('Asia/Kolkata');
        $currentDate = $carbon->toDateTimeString();
        
        $dateOnly = $carbon->format('d-m-Y H:i:s');
        
        $cityData['created_datetime'] = $dateOnly;
       
        $cityData['created_at'] = $currentDate ? : '' ;
        $cityData['updated_at'] = $currentDate ? : '';
       
        // echo json_encode($cityData);
        // die();
        try{ 

                $success = $cityData->save();
            }
            catch(Exception $e)
            {
                $response_data = array('status' =>'200','message'=>'error','data' => $e);
                return response()->json($response_data,200); 
            }

            if($success)
            {
            
                $msg = 'City inserted successfully.';
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.insertCityForm',compact(['msg'])); 
            }
            else
            {
                
                $msg = "Couldn't save City Name, please try after some time.";
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.insertCityForm',compact(['msg']));  
            } 

        }
       
        

       
    }


     public function insertDepot(Request $request)
    {
        DB::setDefaultConnection('mongodb');
        $formData = $request->all();
        $entered_depot_name = $request->input('depot_name');

        // $vanCodeArr = explode('_', $request->input('vanCode'));
        // //$vanCode = $vanCode['0'];

        $checkDepotData = MobileDispensarySevaVanDepot::where('depot_name',$entered_depot_name)->first();
       //echo json_encode($checkVanRegNoData);
        if($checkDepotData && $checkDepotData['depot_name']==$entered_depot_name)
        {
                $msg = 'Depot Name is already present.';
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.insertDepotForm',compact(['msg'])); 
        }
        else{


        $depotData = new MobileDispensarySevaVanDepot();
        //$cityData = new MobileDispensarySevaDailyVehicleDetails();
        foreach($formData as $key => $value)
        {
            $depotData[$key]= $value;
        }
        
       
        $carbon = new Carbon();
        $currentDate = $carbon->setTimezone('Asia/Kolkata');
        $currentDate = $carbon->toDateTimeString();
        
        $dateOnly = $carbon->format('d-m-Y H:i:s');
        
        $depotData['created_datetime'] = $dateOnly;
       
        $depotData['created_at'] = $currentDate ? : '' ;
        $depotData['updated_at'] = $currentDate ? : '';
       
        // echo json_encode($cityData);
        // die();
        try{ 

                $success = $depotData->save();
            }
            catch(Exception $e)
            {
                $response_data = array('status' =>'200','message'=>'error','data' => $e);
                return response()->json($response_data,200); 
            }

            if($success)
            {
            
                $msg = 'Depot inserted successfully.';
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.insertDepotForm',compact(['msg'])); 
            }
            else
            {
                
                $msg = "Couldn't save Depot Name, please try after some time.";
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.insertDepotForm',compact(['msg']));  
            } 

        }
       
        

       
    }



    public function getSelectedCityVan(Request $request)
    {

         $city_name = $request->input('selectedCity');
      // die();
        $vanDetailsRecord = MobileDispensarySevaVanDetails::select('bjs_vehicle_no','vehicle_reg_no')->where('vehicle_city',$city_name)->orderBy('bjs_vehicle_no', 'ASC')->get();

        if($vanDetailsRecord ){
            return  json_encode($vanDetailsRecord);

        }
    }

    
}

?>
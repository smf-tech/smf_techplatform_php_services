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

use PDF; 

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

date_default_timezone_set('Asia/Kolkata'); 
        ini_set('upload_max_filesize', '40M');
      //  ini_set('post_max_size', '40M');
       // ini_set('max_input_time', 500);
     //   ini_set('max_execution_time', 500);
        ini_set("display_errors", 0);


class ImageUploadTestController extends Controller
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

    
    public function webOptionView(Request $request){

        return view('mobileDispensarySeva.webpageOption');
    }

    public function insertVanInfo(Request $request)
    {
        DB::setDefaultConnection('mongodb');
        $formData = $request->all();
        echo 'Input '.$entered_vehicle_reg_no = $request->input('vehicle_reg_no');

        // $vanCodeArr = explode('_', $request->input('vanCode'));
        // //$vanCode = $vanCode['0'];

        $checkVanRegNoData = MobileDispensarySevaVanDetails::where('vehicle_reg_no',$entered_vehicle_reg_no)->first();
       //echo json_encode($checkVanRegNoData);
        if($checkVanRegNoData && $checkVanRegNoData['vehicle_reg_no']==$entered_vehicle_reg_no)
        {
                $msg = 'Vehicle registration number is alreadys.';
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
        die();
        

       
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
                
                 $vanData = MobileDispensarySevaVanDetails::get();
      

                //return view('mobileDispensarySeva.selectVan',compact('vanData'));
                return view('mobileDispensarySeva.selectVan',compact(['vanData','msg'])); 
            }
            else
            {
                $msg = "Couldn't submit Van - Area Visit Register record, please try after some time.";
                $vanData = MobileDispensarySevaVanDetails::get();
                
                //$response_data = array('status' =>'200','message'=>$msg);
                return view('mobileDispensarySeva.selectVan',compact(['vanData','msg']));   
            } 

       
    }

    public function testPageSave(Request $request)
    {

        $formData = $request->all();
        $file = $this->request->file('register_image');
        $url=[];
	//	echo "ggg";
		//print_r($this->request->all());exit;
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
		//	unlink($location);

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
			//unlink($location);
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
			//unlink($location);
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
			//unlink($location);
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
			//unlink($location);
			//echo $fileName1;exit;
            $url[count($url)] = $fileName1;
        }

        
		 $this->logData($this->logInfoPath ,$formData,'DB');
        //$vanCode =$this->request->input('vanCode');
         
        if($request->input('vanCode')!='') {
        	$vanCodeArr = explode('_', $request->input('vanCode'));
        
	        DB::setDefaultConnection('mongodb');
	        $patientData = new MobileDispensarySevaPatientDetails();
			
	        foreach($formData as $key => $value) {
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
	            } catch(Exception $e) {
	                $response_data = array('status' =>'200','message'=>'error','data' => $e);
	                return response()->json($response_data,200); 
	            }

	            if ($success) {
	                $vanData = MobileDispensarySevaVanDetails::get();
	                $msg = 'Patient record inserted successfully.';
	               // $response_data = array('status' =>'200','message'=>$msg);
	                return view('mobileDispensarySeva.testPage',compact(['vanData','msg'])); 
	            } else  {
	                $vanData = MobileDispensarySevaVanDetails::get();
	                $errMsg = "धीमे नेटवर्क के कारण फ़ॉर्म सबमिट नहीं किया जा सका। कृपया पुनः प्रयास करें।";
	                return view('mobileDispensarySeva.testPage',compact(['vanData','errMsg']));
	            }
        } else {

        	$vanData = MobileDispensarySevaVanDetails::get();
            $errMsg = "धीमे नेटवर्क के कारण फ़ॉर्म सबमिट नहीं किया जा सका। कृपया पुनः प्रयास करें।";
            return view('mobileDispensarySeva.testPage',compact(['vanData','errMsg']));
        }	
         
       	
        // print_r($url);
        //die();



    }

    public function savePatientInfo(Request $request)
    {
		//print_r($this->request->file('register_image'));exit;
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
	                $msg = 'Patient record inserted successfully.';
	               // $response_data = array('status' =>'200','message'=>$msg);
	                return view('mobileDispensarySeva.patientInfoForm',compact(['vanData','msg'])); 
	            }
	            else
	            {
	                $vanData = MobileDispensarySevaVanDetails::get();
	                $errMsg = "धीमे नेटवर्क के कारण फ़ॉर्म सबमिट नहीं किया जा सका। कृपया पुनः प्रयास करें।";
	                return view('mobileDispensarySeva.patientInfoForm',compact(['vanData','errMsg']));
	            }
        }
        else
        {

        	$vanData = MobileDispensarySevaVanDetails::get();
            $errMsg = "धीमे नेटवर्क के कारण फ़ॉर्म सबमिट नहीं किया जा सका। कृपया पुनः प्रयास करें।";
            return view('mobileDispensarySeva.patientInfoForm',compact(['vanData','errMsg']));
        }	
         
         
    }


    public function showPatientInfoForm(Request $request)
    {
       
        //$vanCode = 'Test';DB::setDefaultConnection('mongodb');

       $vanData = MobileDispensarySevaVanDetails::get();
      //echo json_encode($vanData);
       //die();

        return view('mobileDispensarySeva.patientInfoForm',compact('vanData'));
       // return view('mobileDispensarySeva.patientInfoForm'); 
    }
	
	public function testpage(Request $request){
		
		$vanData = MobileDispensarySevaVanDetails::get();

        return view('mobileDispensarySeva.testPage',compact('vanData'));
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
}

?>
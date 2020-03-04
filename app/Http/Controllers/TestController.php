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
use PDF; 

use Illuminate\Support\Arr;
date_default_timezone_set('Asia/Kolkata'); 
class TestController extends Controller
{
    use Helpers;

    protected $request;
    
    public function __construct(Request $request){ 
    
        $this->request = $request;
        $this->logInfoPath = "logs/Certificate/DB/logs_".date('Y-m-d').'.log';
        $this->errorPath = "logs/Certificate/Error/logs_".date('Y-m-d').'.log'; 

    }

    public function testForm(Request $request){
       
        return view('Form.formType');

    }

    public function downloadCertificate(Request $request)
    {
       
        $teacherCode = $request->input('teacerCode');
        $teacherTrainingDays = $request->input('trainingDays')??'';
    
          DB::setDefaultConnection('mongodb');
        //$request = json_decode(file_get_contents('php://input'), true);

        $teacher_info = TeacherNICData::where('Code',ltrim($teacherCode, '0'))->first();
        
        if(isset($teacher_info))
        {    
       
        $UserData = new DownloadCertificatePDFRequest;

        $UserData['userName'] = ucwords(strtolower($teacher_info->Teachers_Name));
        $UserData['taluka'] = ucwords(strtolower($teacher_info->Taluka_Name));
        $UserData['district'] = ucwords(strtolower($teacher_info->District_Name
        ));
       
        $UserData['schoolName'] = ucwords(strtolower($teacher_info->School_Name));
        $UserData['certificateType'] = $request->input('certificateType');
        $UserData['teacherTrainingDays'] = $teacherTrainingDays;

        $this->logData($this->logInfoPath ,$UserData,'DB');

        $updateArray[] = [
            'downloaded' => true,
            'certificateType'=> $request->input('certificateType'),
            'dateTime' => new \MongoDB\BSON\UTCDateTime(Carbon::now()) 
            ]; 
        if(isset($teacher_info->downloadStatus)){
            $count = count($teacher_info['downloadStatus']); 
            $teacher_info['downloadStatus.'.$count] = $updateArray;
        }else{
            $teacher_info['downloadStatus'] = $updateArray;
        }
      
        
        if($UserData['userName'] != '')
         {   
            $pdf = app()->make('dompdf.wrapper');
           
            $pdf->loadView('certificate.'.$UserData['certificateType'].'PDF', compact('UserData'))->setPaper('a4', 'portrait');
                //echo json_encode($UserData);
        // die();
             $teacher_info->save();
            return $pdf->stream();
         }else{

             $errorMessage = "Data_is_incorrect.";
             $this->logData($this->errorPath ,$errorMessage,'Error');
            $type = $request->input('certificateType');
             return redirect()->to('http://13.235.105.204/api/downloadCertificateForm/'.$type.'-'.$errorMessage);
         }
       
                        
        
        }
        else{
            $errorMessage = "No_record_found.";
            $type = $request->input('certificateType');
             $this->logData($this->errorPath ,$errorMessage,'Error');
            // session()->flash('errorMessage', 'Post was created!');
            return redirect()->to('http://13.235.105.204/api/downloadCertificateForm/'.$type.'-'.$errorMessage);
            // return view('certificate.formType',compact('type','errorMessage'));
            //$type = $request->input('certificateType');
            //return redirect()->back()->withInput(compact('errorMessage'));

            //return redirect()->previous();
            // redirect("Location: http://13.235.105.204/api/downloadCertificateForm/".$type);

            //return redirect()->route('/api/downloadCertificateForm',['type' => $type]);
            //downloadCertificateForm\/Poste
            //return redirect()->route('api/downloadCertificateForm/'.$type, compact('errorMessage') );
             // return redirect()->route('downloadCertificateForm/Poster', ['type'=>$type]);
           // return view('certificate.formType',compact('errorMessage'));
        }


    }


    public function downloadCertificateReport(Request $request)
    {
        DB::setDefaultConnection('mongodb');
        //$request = json_decode(file_get_contents('php://input'), true);

        $teacher_info = TeacherNICData::where('downloadStatus.downloaded',true)->get();
        if($teacher_info)
        {
            echo json_encode($teacher_info);
            die();

            return view('certificate.formType',compact('type','errorMessage'));
        }   
    }

 

 

    
}
?>
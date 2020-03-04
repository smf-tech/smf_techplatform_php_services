<?php

//owner:Sayli Dixit
namespace App\Http\Controllers;

use JeroenDesloovere\Geolocation\Geolocation;

use Illuminate\Http\Request;
use App\User;
use Maklad\Permission\Models\Role;
use Maklad\Permission\Models\Permission;
use Dingo\Api\Routing\Helpers;
use App\Organisation;
use App\Project;
use App\Module;
use App\RoleConfig;
use App\PlannerUserLeaveBalance;
use App\PlannerTransactions;
use App\PlannerAttendanceTransaction;
use App\PlannerHolidayMaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\ApprovalLog;
use Carbon\Carbon;
use App\Category;

use Jcf\Geocode\Geocode;


use PHPUnit\Framework\TestCase;


use Illuminate\Support\Arr;

class getAddressController extends Controller
{
    use Helpers;
	/**
     *
     * @var Request
     */
    protected $request;

    public function __construct(Request $request) 
    {
        $this->request = $request;
    }

// function for getting dashboard data


    public  function getGeoCodes()
    {
      $testDateTime = (1564159690000/1000); //1564079400000  1564159690000   1564079400000
        //$testDateTime = (1564159690000/1000); //1564159690000   1564079400000

        // echo "Dateonly".date('Y-m-d h:i:s',1564554369000/1000);
        // echo "<br/>DateTime".date('Y-m-d h:i:s',1564554369000/1000);
        // //exit;
        // echo 'Old'.$start_date_str = Carbon::createFromTimestamp($testDateTime);//->toDateTimeString();
        //exit;
        //$var  = Carbon::now('Asia/Kolkata')

        //echo 'new'.$start_date_str->timezone = 'Asia/Kolkata';
        
         //working code
         //$start_date_str = Carbon::createFromTimestamp($testDateTime);
         //echo  "Test<br/>".$carbonDate = new Carbon($start_date_str);
         //$carbonDate->timezone = 'Asia/Kolkata';
         //echo '<br/>new'. $carbonDate->toDateTimeString();
         //echo '<br/>new'. $carbonDate->toDateString();

          $start_date_str = Carbon::createFromTimestamp(1563425000000/1000);
          

          $end_date_str = Carbon::createFromTimestamp(1564425000000 /1000);//->toDateTimeString();

          $carbonStartDate = new Carbon($start_date_str);
          $carbonStartDate->timezone = 'Asia/Kolkata';
          $start_date = $carbonStartDate->toDateTimeString();

          echo $currentDateStartTime = Carbon::now()->startOfDay();
          exit();
          $start_date_time = new Carbon(Carbon::now());
              $start_date_time->timezone = 'Asia/Kolkata';
           echo   $start_date = $start_date_time->startOfDay();
          die();

          $carbonEndDate = new Carbon($end_date_str);
          $carbonEndDate->timezone = 'Asia/Kolkata';


         echo  $end_date = $carbonEndDate->toDateTimeString();

          echo '----'.$start_date_time = Carbon::parse($start_date)->startOfDay();  
          echo '<br/>End Date'.$end_date_time = Carbon::parse($end_date)->endOfDay();

         echo "<br/>Carbon difference".$diff = $start_date_str->diffInDays($end_date_str)+1;

         //echo "<br/>S date function".$days =  unixtojd($start_date_str)-unixtojd($end_date_str);



    
        exit;

        try{    
            $response = Geocode::make()->latLng(18.540860,73.830620);
           // print_r($response);
            //exit;
            if ($response) {
               // echo $response->latitude();
                //echo $response->longitude();
                echo $response->formattedAddress();
               
                echo $response->locationType();
                }
           }
           catch (Exception $e) {
                echo "Message: " . $e->getMessage();
                echo "";
                echo "getCode(): " . $e->getCode();
                echo "";
                echo "__toString(): " . $e->__toString();
            }
    }

    //18.672289, 73.734875
    //18.536825, 73.829565
    
    

    public function getDistance() {
        $lat1=18.672289 ; $lon1 = 73.734875; $lat2=18.536825; $lon2=73.829565; $unit='K';
        
      if (($lat1 == $lat2) && ($lon1 == $lon2)) {
        return 0;
      }
      else {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
           echo $miles * 1.609344;
           exit;  

        } else if ($unit == "N") {
          return($miles * 0.8684);
        } else {
          return $miles;
        }
      }
    }


    //echo distance(18.672289, 73.734875, 18.536825, 73.829565, "M") . " Miles<br>";
  
   
    //echo distance(18.672289, 73.734875, 18.536825, 73.829565, "N") . " Nautical Miles<br>";

}
<?php
/**
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;


use App\User;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

use App\MachineMou;
use App\Machine;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;
/**
 * Class deleteusersCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class mouexpiration extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "mou:user";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "To Expire MOU";


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       $file = fopen('storage/logs/Cron/mouexpiration'.date('Y-m-d').'.log', 'a');
		try {
             $dbName = 'BJS_5ddfbb6bd6e2ef4f78207513';

			$mongoDBConfig = config('database.connections.mongodb');
			$mongoDBConfig['database'] = $dbName;
			\Illuminate\Support\Facades\Config::set(
				'database.connections.' . $dbName,
				$mongoDBConfig
			);
			DB::setDefaultConnection($dbName); 
			
           define('API_ACCESS_KEY','AAAAxAoRWyc:APA91bHVYeWNeHFqwO74C-W-uAJPeydy1XQSShbgq1dO___UW1g8kheoOP6EBi38L-aqMsV7RYw72KiGQL7qZv7IL301DxTUuwFp1Rh3XDfTZCshr217P0EnOQnFZOm4J73vvO7ACAjo');
			$fcmUrl = 'https://fcm.googleapis.com/fcm/send';
			
            $todayDate = new \MongoDB\BSON\UTCDateTime(Carbon::now());       
            $MachineMou = MachineMou::where('status_code','!=','114')
								->where('mou_details.mou_expiry_date','<=',$todayDate)
								->get();
             
            if(count($MachineMou) > 0)
            {
                foreach($MachineMou as $row)
                { 
					fwrite($file,"MachineMou_ID = ". $row['_id'] ."\n");
		
					$Mou = MachineMou::find($row['_id']);
					$Mou['status'] = 'MOU Expired';
					$Mou['status_code'] = '114';
					try{
						$Mou->save(); 
						//notification send start
						if(isset($Mou['provider_information'])) { 
						$machine = Machine::find($Mou['provider_information']['machine_id']);
						$machine['status'] = 'MOU Expired';
						$machine['status_code'] = '114';
						$machine->save();
						
						$machineDetails = Machine::where('_id',$Mou['provider_information']['machine_id'])->get();
						 
						
						DB::setDefaultConnection('mongodb');
						if($machineDetails){
							$user = User::where('location.state',$machineDetails[0]['state_id'])
										  ->orWhere('location.district',$machineDetails[0]['district_id'])	
										  ->orWhere('location.taluka',$machineDetails[0]['taluka_id'])	
										  ->get();
							 			  
							if($user){
								foreach($user as $row){
									if($row['firebase_id'] != null || $row['firebase_id'] !=''){
									$token[]=$row['firebase_id']; 
									$id[]=$row['_id']; 
									}
								}
								 
									$notification = [
										'title' =>'MOU Expired',
										'body' => 'MOU Expired for Machine code ('.$machineDetails[0]['machine_code'].')' ,
										'icon' =>'myIcon', 
										'sound' => 'mySound'
									]; 
									 
									$extraNotificationData = ["message" => $notification,"moredata" =>'dd'];

									$fcmNotification = [
										'registration_ids' => $token, 
										// 'to'        => $token, //single token
										'notification' => $notification,
										'data' => $extraNotificationData
									];
									 
									$headers = [
										'Authorization: key=' . API_ACCESS_KEY,
										'Content-Type: application/json'
									];


									$ch = curl_init();
									curl_setopt($ch, CURLOPT_URL,$fcmUrl);
									curl_setopt($ch, CURLOPT_POST, true);
									curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
									curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
									curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
									$result = curl_exec($ch);
									curl_close($ch);
								 
							}			  
						}
					}
					//notification send end
					}catch(Exception $e)
					{}
				 
                }
            }
             
        } catch (Exception $e) {
            fwrite($file, $e);
            fclose($file);
            $this->error("An error occurred");
        } 
    }
}
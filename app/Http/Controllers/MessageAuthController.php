<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\User;
use App\JurisdictionType;
use GuzzleHttp\Client;
use App\Lib\AES;
use App\Jobs\DataQueue;


class MessageAuthController extends Controller
{
	public function __construct(Request $request)
    {
        $this->request = $request;
		$this->logInfoPath = "logs/Login/DB/logs_".date('Y-m-d').'.log';
		$this->errorPath = "logs/Login/Error/logs_".date('Y-m-d').'.log';

    }
    public function sendOTP(Request $request){
        
        //GET the ph_no
        $ph_no=$request->phone;
        
        if($ph_no == '6363636363' || $ph_no == '1234567890')
          {
                $six_digit_random_number = '123456';              
          }else{  
            //6 digit random number
            $six_digit_random_number = mt_rand(100000, 999999);
         }
        
        $obj=DB::collection('user_otp_verify')->where('ph_no',$ph_no)->first();

        /*$inputKey = "mutthafoundation";
        $blockSize = 256;
        $aes = new AES($six_digit_random_number, $inputKey, $blockSize);
        $encryptedOtp = $aes->encrypt();*/
        $encryptedOtp = base64_encode($six_digit_random_number);
        if(isset($obj)){ 
            DB::collection('user_otp_verify')->where('ph_no',$ph_no)->update(['otp'=>$encryptedOtp, 'time'=>date("Y/m/d H:i:s",time())]);
        }else{
            DB::collection('user_otp_verify')->insert(['ph_no'=>$ph_no , 'otp'=>$encryptedOtp , 'time'=>date("Y/m/d H:i:s",time()) ]  );
        }
        
        $http = new \GuzzleHttp\Client;
        $autoreadcode = env('AUTOREAD_SMS_CODE','gy2LkRfkcrq');

       
        //$sendsmscall = $http->get('http://www.smsjust.com/sms/user/urlsms.php?username=avmabd&pass=avmabd@123&senderid=MVMSMF&dest_mobileno='.$ph_no.'&message=%3C%23%3E%20The%20password%20is:'.$six_digit_random_number.' '.urlencode($autoreadcode).'&response=Y');

        $sendsmscall = $http->get('http://sms2.sminfomedia.in/api/mt/SendSMS?user=avmabd&password=avmabd&senderid=MVMSMF&channel=Trans&DCS=0&flashsms=0&number='.$ph_no.'&text=%3C%23%3E%20The%20password%20is:'.$six_digit_random_number.' '.urlencode($autoreadcode).'&route=5');
       
        //$content = array('otp'=>$six_digit_random_number);
        //$content = array('otp'=>''); 
        $content = array('otp'=>$encryptedOtp);
        $response_data = array('status' =>'success','data' => $content,'message'=>'OTP Has Been Sent');
	    return response()->json($response_data);      
    }

    public function verifyOTP(Request $request){

        $ph_no=$request->phone;
        $otp =$request->otp;
		//$roleCode = '111';
        $invalidDevice=0;
        $obj=DB::collection('user_otp_verify')->where('ph_no',$ph_no)->first();
        
        $otpFromServer=$obj['otp'];
        $sec=(  strtotime(date("Y/m/d H:i:s",time())) -  strtotime(date( $obj['time'] )))  ;
        
        if($otpFromServer!=$otp){
            $response_data = array('status' =>'failed','code' =>400,'data' => '','message'=>'Incorrect OTP');
            return response()->json($response_data );
        }else if(($sec/60)>=30){
        DB::collection('user_otp_verify')->where('ph_no',$ph_no)->update(['otp'=>'', 'time'=>false]);

            $response_data = array('status' =>'failed','code' =>400,'data' => '','message'=>'OTP Expired');
            return response()->json($response_data );

        } else {
			$user = User::select('orgDetails','name','org_id','role_id','project_id','device_id')->where('phone',$ph_no)->first();
			$http = new \GuzzleHttp\Client;
			if(isset($user)){
				$roleCode = '';
				if(isset($user->role_id)) {	
				//$user = User::select('name','org_id','project_id','orgDetails','role_id','device_id')->where('phone',$ph_no)->first();
				$rolename = \App\Role::select('role_code')->where("_id",$user->role_id)->first();
				$roleCode = $rolename->role_code;
               // $roleCode = $rolename->role_code;

                $database = $this->connectTenantDatabase($request,$user->org_id);
                /*echo json_encode($user);
                die();
                $jurisdictionType = JurisdictionType::select('_id','jurisdictions')
                            ->where('project_id',$user->project_id[0])
                            ->where('is_deleted',0)
                            ->first();*/
				//print_r($roleCode);exit;
				//check oprator role for ss 
				if ($roleCode  == '113') {
					$invalidDevice =0;
					if (!$request->has('device_id')) {
							$response_data = array('status' =>'failed',
												'code' =>400,
												'data' => '',
												'message'=>'Device id field missing');
						return response()->json($response_data );							
					}

					if(isset($user->device_id))	 {					
					
						//check device id is equal with the registered device id
						if ($user->device_id != $request->device_id) {
							
							$response_data = array('status' =>'failed',
													'code' =>400,
													'data' => '',
													'message'=>'Invalid device');
							
							$this->logData($this->logInfoPath,$request->all(),'Error',$response_data);
				
							return response()->json($response_data );
						}
					} else {
						$user->device_id = $request->device_id;
						$user->save();
					}	
				$currentTimestamp = strtotime("now");

				$currDate = new \MongoDB\BSON\UTCDateTime($currentTimestamp);
				$database = $this->connectTenantDatabase($request,$user->org_id);
				
				
				
				$moMapping = \App\OperatorMachineMapping::where(['operator_id'=>$user->_id])
								->with('machineData')
								->select('machine_id','operator_id')
								->first();
				//print_r($moMapping );exit;				
				if (!$moMapping) {
					$response_data = array('status' =>400,
											'code' =>400,											
											'message'=>'Please assign operator to machine');
					return response()->json($response_data );	
					
				}

				if (empty($moMapping->machineData[0])) {
					$response_data = array('status' =>400,
											'code' =>400,											
											'message'=>'Please assign operator to machine');
					return response()->json($response_data );	
					
				}	
				//print_r($moMapping->machineData);exit;
				/*$mahineData = \App\MachineMou::where(['machine_mobile_number' => $ph_no,
				])
				//->where('mou_details.mou_expiry_date' > $currDate)
				->first();	*/
				
				$statusCode = $moMapping->machineData[0]['status_code'];
				$machineId = $moMapping->machineData[0]['_id'];				

				
				if ($statusCode == '114' || $statusCode == '105') {
					
					$response_data = array('status' =>400,
											'code' =>400,											
											'message'=>'Your machine MOU has been expired/terminated.');
					return response()->json($response_data );					
				}
				//print_r($mahineData->provider_information['machine_id']);exit;
				//echo $mahineData->provider_information->machine_id;exit;	
				
				//Check machine deployed
				$mappingCnt = \App\StructureMachineMapping::where(['machine_id'=>$machineId,
													'status'=>'deployed'])
													->count();
		

				if($mappingCnt == 0) {
					$response_data = array('status' =>'failed',
											'code' =>400,											
											'message'=>'Please deploy machine on structure.');
					return response()->json($response_data );
					
				}
				
				//get machine details
				$machineData = \App\Machine::find($machineId);
				
				if ($machineData) {
					//echo $requestJson->provider_information->machine_id;exit;
					//send notification
					$roleArr = array('111','112','115');
					$params['org_id'] = $user->org_id;
					$params['request_type'] =  self::NOTIFICATION_OPRATOR_LOGIN;
					$params['update_status'] = 'Oprator Login';				
					$params['code'] = $machineData->machine_code;
					
					$params['stateId'] = $machineData->state_id;
					$params['districtId'] = $machineData->district_id;
					$params['talukaId'] = $machineData->taluka_id;
					$params['modelName'] = 'Operator';
					
					$params['userName'] = $user->name;
					
					$request['functionName'] = __FUNCTION__;
					$request['params'] =  $params;
					$request['roleArr'] = $roleArr;

					dispatch((new DataQueue($request)));

					//$this->sendSSNotification($this->request,$params, $roleArr, $user);
				}				
				
				//send notification on oprator login to TC
				
				//Check MOU expiry
				//$currDate = new \MongoDB\BSON\UTCDateTime($currentTimestamp);
				//$database = $this->connectTenantDatabase($request,$user->org_id);
				
												
			}
			else{

				if(isset($user->device_id))	 {					
					
						//check device id is equal with the registered device id
						if ($user->device_id != $request->device_id) {
							
							$invalidDevice = 1;
							// $response_data = array('status' =>'failed',
							// 						'code' =>300,													
							// 						'message'=>'Invalid device');
							// return response()->json($response_data );
						}else{
							$invalidDevice = 0;
						}

					} else {
						$invalidDevice = 0;
						$user->device_id = $request->device_id;
						$user->save();
					}

			}
			//$user->jurisdiction_type_id = $jurisdictionType->_id;
                			
			$user->role_code = $roleCode;
			}
             
           //generate Oauth token using the 
           $pwd = $request->get('password', $ph_no);
            $response = $http->post('http://localhost/oauth/token', [
            'form_params' => [
                                'grant_type' => 'password',
                                'client_id' => '5c1c78a9d503a33b29274ca4',
                                'client_secret' => 'CL8isQUG6Ch3DUoAhgCfLbwTayZZCPUx21uZ2mxN',
                                'username' => $ph_no,
                                'password' => $pwd,
                                'scope' => '*'
                    ],
            ]);
            //var_dump((string)$response->getBody());exit;
           // echo 'Tests'.$invalidDevice;
            if($invalidDevice == 1)
            	{
            		$responseCode = 300;
            	}else{
            		$responseCode = 200;
            	}
            $gettoken_response = json_decode((string)$response->getBody(), true);
            $response_data = array('status' =>'success','code' =>$responseCode,'data' => $gettoken_response,'message'=>'');
            //return response()->json($response->getBody());
            //$array = json_decode(json_encode($response->getBody()), true);
			$this->logData($this->logInfoPath,$request->all(),'Error',$response_data);
				
            return response()->json($response_data );
		}else{
            //create a new User with phone number and a mock email id (since email unique contraint)
            $email = uniqid().'@placeholder.com';
            $user_data = ['name'=>'',
                        'email'=>$email,
                        'password'=> app('hash')->make($ph_no),
                        'phone'=>$ph_no,
                        'lock'=>true,
                        'approve_status'=>'pending'];
            User::create($user_data);
            $pwd = $request->get('password', $ph_no);
            $response = $http->post('http://localhost/oauth/token', [
            'form_params' => [
                                'grant_type' => 'password',
                                'client_id' => '5c1c78a9d503a33b29274ca4',
                                'client_secret' => 'CL8isQUG6Ch3DUoAhgCfLbwTayZZCPUx21uZ2mxN',
                                'username' => $ph_no,
                                'password' => $pwd,
                                'scope' => '*'
                    ],
            ]);
            //var_dump((string)$response->getBody());exit;
             //echo 'Test'.$invalidDevice;
             if($invalidDevice == 1)
            	{
            		$responseCode = 300;
            	}else{
            		$responseCode = 200;
            	}
            $gettoken_response = json_decode((string)$response->getBody(), true);
            $response_data = array('status' =>'success','code' =>$responseCode,'data' => $gettoken_response,'message'=>'');
            //return response()->json($response->getBody());
            //$array = json_decode(json_encode($response->getBody()), true);
            return response()->json($response_data);

        }
        
     }



    }

    public function verifyOTPLogin(Request $request){
       
	   if (!$request->has('phone')) {
			$response_data = array('status' =>'failed',
									'code' =>400,												
									'message'=>'Phone number field missing');
			return response()->json($response_data );							
		}
		
		if (!$request->has('otp')) {
			$response_data = array('status' =>'failed',
									'code' =>400,												
									'message'=>'OTP field missing');
			return response()->json($response_data );							
		}
	   
	   if (!$request->has('device_id')) {
			$response_data = array('status' =>'failed',
									'code' =>400,												
									'message'=>'Device id field missing');
			return response()->json($response_data );							
		}

        $ph_no=$request->phone;
        $otp =$request->otp;
	  	$invalidDevice = 0;
        $obj = DB::collection('user_otp_verify')->where('ph_no',$ph_no)->first();

        $otpFromServer=$obj['otp'];
		
        $sec=(  strtotime(date("Y/m/d H:i:s",time())) -  strtotime(date( $obj['time'] )))  ;
        
        if($otpFromServer!=$otp){
			
            $response_data = array('status' =>'failed','code' =>400,'message'=>'Incorrect OTP');
            return response()->json($response_data );
			
        } else if(($sec/60)>=30){
			
			DB::collection('user_otp_verify')->where('ph_no',$ph_no)->update(['otp'=>'', 'time'=>false]);

            $response_data = array('status' =>'failed','code' =>400,'message'=>'OTP Expired');
            return response()->json($response_data );

        }else{
        $user=User::where('phone',$ph_no)->first();
        //var_dump($user);exit;
       //$user= DB::collection('users')->where('phone',$ph_no)->first();
        $http = new \GuzzleHttp\Client;
        if(isset($user)){
			
			if(isset($user->role_id))
            {
			    $rolename = \App\Role::select('role_code')->where("_id",$user->role_id)->first();
				$roleCode = $rolename->role_code;
				if ($roleCode  == '113') {
					
					$invalidDevice=0;
					if(isset($user->device_id))	 {					
					
						//check device id is equal with the registered device id
						if ($user->device_id != $request->device_id) {
							
							$response_data = array('status' =>'failed',
													'code' =>400,													
													'message'=>'Invalid device');
							return response()->json($response_data );
						}
					} else {
						$user->device_id = $request->device_id;
						$user->save();
					}
					
				$currentTimestamp = strtotime("now");
				//Check MOU expiry
				$currDate = new \MongoDB\BSON\UTCDateTime($currentTimestamp);
				$database = $this->connectTenantDatabase($request,$user->org_id);			
				
				$moMapping = \App\OperatorMachineMapping::where(['operator_id'=>$user->_id])
								->with('machineData')
								->select('machine_id','operator_id')
								->first();
								
				if (!$moMapping) {
					$response_data = array('status' =>400,
											'code' =>400,											
											'message'=>'Please assign operator to machine');
					return response()->json($response_data );	
					
				}

				if (empty($moMapping->machineData[0])) {
					$response_data = array('status' =>400,
											'code' =>400,											
											'message'=>'Please assign operator to machine');
					return response()->json($response_data );	
					
				}	
				
				$statusCode = $moMapping->machineData[0]['status_code'];
				$machineId = $moMapping->machineData[0]['_id'];				

				if ($statusCode == '114' || $statusCode == '105') {
					$response_data = array('status' =>400,
											'code' =>400,											
											'message'=>'Your machine MOU has been expired/terminated.');
					return response()->json($response_data );					
				}
				//Check machine deployed
				$mappingCnt = \App\StructureMachineMapping::where(['machine_id'=>$machineId,
													'status'=>'deployed'])
													->count();
		

				if($mappingCnt == 0) {
					$response_data = array('status' =>'failed',
											'code' =>400,											
											'message'=>'Please deploy machine on structure.');
					return response()->json($response_data );
					
				}

				//get machine details
				$machineData = \App\Machine::find($machineId);
				
				if ($machineData) {
					
					//send notification
					$roleArr = array('111','112','115');
					$params['org_id'] = $user->org_id;
					$params['request_type'] =  self::NOTIFICATION_OPRATOR_LOGIN;
					$params['update_status'] = 'Oprator Login';				
					$params['code'] = $machineData->machine_code;
					
					$params['stateId'] = $machineData->state_id;
					$params['districtId'] = $machineData->district_id;
					$params['talukaId'] = $machineData->taluka_id;
					$params['modelName'] = 'Operator';
					
					//$this->sendSSNotification($this->request,$params, $roleArr, $user);
					$request['functionName'] = __FUNCTION__;
					$request['params'] =  $params;
					$request['roleArr'] = $roleArr;
					$params['userName'] = $user->name;
					dispatch((new DataQueue($request)));

				}			
								
			}
			else{

				if(isset($user->device_id))	 {					
					
						//check device id is equal with the registered device id
						if ($user->device_id != $request->device_id) {
							
							$invalidDevice = 1;
							// $response_data = array('status' =>'failed',
							// 						'code' =>300,													
							// 						'message'=>'Invalid device');
							//return response()->json($response_data );
						}
					} else {
						$invalidDevice = 0;
						$user->device_id = $request->device_id;
						$user->save();
					}
			}	
			$user->role_code = $roleCode;
         }
           //generate Oauth token using the 
           $pwd = $request->get('password', $ph_no);
            $response = $http->post('http://localhost/oauth/token', [
            'form_params' => [
                                'grant_type' => 'password',
                                'client_id' => '5c1c78a9d503a33b29274ca4',
                                'client_secret' => 'CL8isQUG6Ch3DUoAhgCfLbwTayZZCPUx21uZ2mxN',
                                'username' => $ph_no,
                                'password' => $pwd,
                                'scope' => '*'
                    ],
            ]);
            //var_dump((string)$response->getBody());exit;
            if($invalidDevice == 1)
        	{
        		$responseCode = 300;
        	}else{
        		$responseCode = 200;
        	}
            $gettoken_response = json_decode((string)$response->getBody(), true);
            $response_data = array('status' =>'success','code' =>$responseCode,'data' => $gettoken_response,'message'=>'');
            //return response()->json($response->getBody());
            //$array = json_decode(json_encode($response->getBody()), true);
			$this->logData($this->logInfoPath,$request->all(),'Error',$response_data);
			
			$userData = User::find($user->id);
			
			//$userData->device_id = $request->device_id;
			$userData->save();
			
            return response()->json($response_data );
		}else{
            //create a new User with phone number and a mock email id (since email unique contraint)
            $invalidDevice = 0;
			$deviceId = $request->device_id;
            $email = uniqid().'@placeholder.com';
            $user_data_first = ['name'=>'',
                        'email'=>$email,
                        'password'=> app('hash')->make($ph_no),
                        'phone'=>$ph_no,
                        'approve_status'=>'pending',
						'device_id'=>$deviceId,
					];
						
            User::create($user_data_first);
            $pwd = $request->get('password', $ph_no);
            $response = $http->post('http://localhost/oauth/token', [
            'form_params' => [
                                'grant_type' => 'password',
                                'client_id' => '5c1c78a9d503a33b29274ca4',
                                'client_secret' => 'CL8isQUG6Ch3DUoAhgCfLbwTayZZCPUx21uZ2mxN',
                                'username' => $ph_no,
                                'password' => $pwd,
                                'scope' => '*'
                    ],
            ]);
            //var_dump((string)$response->getBody());exit;
            if($invalidDevice == 1)
        	{
        		$responseCode = 300;
        	}else{
        		$responseCode = 200;
        	}
            $gettoken_response = json_decode((string)$response->getBody(), true);
            $response_data = array('status' =>'success','code' =>$responseCode,'data' => $gettoken_response,'message'=>'');
            //return response()->json($response->getBody());
            //$array = json_decode(json_encode($response->getBody()), true);
            return response()->json($response_data);

        }
        
     }



    }    

	public function getTestEndpoint(Request $request){
	   $content = array('name'=>'test');
	   return response()->json($content);
    }
    
    public function refreshToken(Request $request){
        //get the refresh token from the request
        $data = $request->all();
        $grant_type =$data['grant_type'];
        $client_id =$data['client_id'];
        $client_secret =$data['client_secret'];
        $scope =$data['scope'];
        $refresh_token =$data['refresh_token'];

        $http = new \GuzzleHttp\Client;
        //call the Oauth Api to refresh the token
        $response = $http->post('http://localhost/oauth/token', [
            'form_params' => [
                                'grant_type' => $grant_type,
                                'client_id' => $client_id,
                                'client_secret' => $client_secret,
                                'refresh_token' => $refresh_token,
                                'scope' => $scope
                    ],
            ]);
	
        $refresh_token_response = json_decode((string)$response->getBody(), true);
        $response_data = array('status' =>'success','data' => $refresh_token_response,'message'=>'');
        return response()->json($response_data);           
        
    }
}

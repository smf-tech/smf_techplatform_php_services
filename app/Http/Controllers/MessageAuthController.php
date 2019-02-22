<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\User;
use GuzzleHttp\Client;

class MessageAuthController extends Controller
{
    public function sendOTP(Request $request){
        
        //GET the ph_no
        $ph_no=$request->phone;
        
        //6 digit random number
        $six_digit_random_number = mt_rand(100000, 999999);
         

        $obj=DB::collection('user_otp_verify')->where('ph_no',$ph_no)->first();
        //var_dump($obj);exit;
        if(isset($obj)){ 
            DB::collection('user_otp_verify')->where('ph_no',$ph_no)->update(['otp'=>$six_digit_random_number, 'time'=>date("Y/m/d H:i:s",time())]);
        }else{
            DB::collection('user_otp_verify')->insert(['ph_no'=>$ph_no , 'otp'=>$six_digit_random_number , 'time'=>date("Y/m/d H:i:s",time()) ]  );
        }
        
        $http = new \GuzzleHttp\Client;
        $autoreadcode = env('AUTOREAD_SMS_CODE','JftAsR+UI44');
        $sendsmscall = $http->get('http://www.smsjust.com/sms/user/urlsms.php?username=avmabd&pass=avmabd@123&senderid=MVMSMF&dest_mobileno='.$ph_no.'&message=%3C%23%3E%20The%20password%20is:'.$six_digit_random_number.' '.$autoreadcode.'&response=Y');

        $content = array('otp'=>$six_digit_random_number);
        $response_data = array('status' =>'success','data' => $content,'message'=>'Otp Sent successfully');
	    return response()->json($response_data);      
    }

    public function verifyOTP(Request $request){

        $ph_no=$request->phone;
        $otp =$request->otp;

        $obj=DB::collection('user_otp_verify')->where('ph_no',$ph_no)->first();
        
        $otpFromServer=$obj['otp'];
        $sec=(  strtotime(date("Y/m/d H:i:s",time())) -  strtotime(date( $obj['time'] )))  ;
        
        if((integer)$otpFromServer!=(integer)$otp){
            $response_data = array('status' =>'failed','data' => '','message'=>'InCorrect OTP');
            return response()->json($response_data );
        }else if(($sec/60)>=30){
        DB::collection('user_otp_verify')->where('ph_no',$ph_no)->update(['otp'=>'', 'time'=>false]);

            $response_data = array('status' =>'failed','data' => '','message'=>'OTP Expired');
            return response()->json($response_data );

        }else{
        $user=User::where('phone',$ph_no)->first();
        //var_dump($user);exit;
       //$user= DB::collection('users')->where('phone',$ph_no)->first();
        $http = new \GuzzleHttp\Client;
        if(isset($user)){
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
            $gettoken_response = json_decode((string)$response->getBody(), true);
            $response_data = array('status' =>'success','data' => $gettoken_response,'message'=>'');
            //return response()->json($response->getBody());
            //$array = json_decode(json_encode($response->getBody()), true);
            return response()->json($response_data );
		}else{
            //create a new User with phone number and a mock email id (since email unique contraint)
            $email = uniqid().'@placeholder.com';
            $user_data = ['name'=>'',
                        'email'=>$email,
                        'password'=> app('hash')->make($ph_no),
                        'phone'=>$ph_no,
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
            $gettoken_response = json_decode((string)$response->getBody(), true);
            $response_data = array('status' =>'success','data' => $gettoken_response,'message'=>'');
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

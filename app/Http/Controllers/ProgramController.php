<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Maklad\Permission\Models\Role;
use Maklad\Permission\Models\Permission;
use Dingo\Api\Routing\Helpers;
use App\Organisation;
use App\Project;
use App\Module;
use App\RoleConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\ApprovalLog;
use Carbon\Carbon;
use App\Survey;
use Illuminate\Support\Arr;
use GuzzleHttp\Client;
use App\Lib\AES;
 

class ProgramController extends Controller
{
     public function testOtpLogin(Request $request){

        $ph_no= '8087558438';
        $otp = 'MjI0Njkx';

        $obj=DB::collection('user_otp_verify')->where('ph_no',$ph_no)->first();
        $otpFromServer=$obj['otp'];
        $sec=(  strtotime(date("Y/m/d H:i:s",time())) -  strtotime(date( $obj['time'] )))  ;
        
        if($otpFromServer!=$otp){
            $response_data = array('status' =>'failed','data' => '','message'=>'InCorrect OTP');
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
                     'http_errors' => false
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
	
	public function test()
	{
		// echo "hii";die();
		var_dump(new \MongoDB\BSON\UTCDateTime());
		
	}
    


}

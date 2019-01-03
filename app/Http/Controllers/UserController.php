<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Dingo\Api\Routing\Helpers;

class UserController extends Controller
{
    use Helpers;

    protected $request;

    public function __construct(Request $request) 
    {
        $this->request = $request;
    }

    public function getUserDetails() {
        $user = $this->request->user();
        return $user;
    }

    public function show()
    {
        $users = User::all();
        return $users;
    }

    public function update($phone)
    {
        $user = User::where('phone', $phone)->first();
        if($user){
            $update_data = $this->request->all();
            //return $update_data;
            $update_data['dob'] = strtotime($update_data['dob']);
            $user->update($update_data);
            $user->dob = date('Y-m-d',$user->dob);
            return response()->json($user, 200);
        }else{
            return response()->json($user, 404);
        }
        
    }

    public function approveuser($phone){
        $user = User::where('phone', $phone)->first();
        if($user){
            $put_params = $this->request->all();
            $update_data = ['approve_status'=>$put_params['update_status']];
            $user->update($update_data);
            return response()->json([], 200);
        }else{
            return response()->json([], 404);
        }
    }
}

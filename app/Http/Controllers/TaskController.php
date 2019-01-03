<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Task;
use Dingo\Api\Routing\Helpers;

class TaskController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request) 
    {
        $this->request = $request;
    }


    public function show()
    {
		$tasks = Task::all();
        return $tasks;
    }

    public function getTask()
    {
        $user = $this->request->user();
        $tasks = Task::where('user_id',$user->id)->get();
        return $tasks;
    }
}

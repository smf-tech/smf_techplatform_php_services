<?php 
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use DateTimeImmutable;
use DateTime;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;
use App\Meet;


use Illuminate\Support\Arr;
date_default_timezone_set('Asia/Kolkata'); 
class MeetController extends Controller
{
    use Helpers;

    protected $request;
	
	public function __construct(Request $request) 
    {
        $this->request = $request;
    }
	
	
	//Insert Meet into db
	public function insertMeet(Request $request)
	{
		$user = $this->request->user(); 
		$database = $this->connectTenantDatabase($request,$user->org_id);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
		$data = json_decode(file_get_contents('php://input'), true);
		echo json_encode($data);
			
	}

}




?>
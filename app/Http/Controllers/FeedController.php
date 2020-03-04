<?php

namespace App\Http\Controllers;

use App\Organisation;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
//use App\FeedPost;

use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\FeedPost;
use App\FeedComment;

date_default_timezone_set('Asia/Kolkata'); 
class FeedController extends Controller
{

    use Helpers;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
		$this->logInfoPah = "logs/Feed/DB/logs_".date('Y-m-d').'.log';
		$this->errorPath = "logs/Feed/Error/logs_".date('Y-m-d').'.log';

    }

    
   public function createFeed(Request $request)
    {
		//error_reporting(1);

		$this->logData($this->logInfoPah,$this->request->all(),'DB');
		
		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$org_id =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		} else {
			
			$message = "Insufficent header info";
			
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>404,
									'message'=>$message, 
									'code'=> 404
									);
			
			return response()->json($response_data,200);			
		}	
			
		
		if (!$this->request->has('formData')) {
			$error = array('status' =>400,
							'message' => 'Formdata field is missing',							
							'code' => 400);						
			$this->logData($this->errorPath,$this->request->all(),'Error',$error);
							
			return response()->json($error);			
		}
		
		
		$temp = $this->request['formData'];
		$requestJson = json_decode($temp);
		//echo '<pre>';print_r($requestJson);exit;
		
		if  (!(isset($requestJson->title))) {
		
			$error = array('status' =>400,
								   'message'=>'Title field is missing',
								   'code' => 403);
						
			$this->logData($this->logInfoPah,$error, 'Error');
			
			return response()->json($error);
		}		
		
		if  (strlen(trim($requestJson->title)) > 100) {
			$error = array('status' =>'error',
								   'message'=>'Title field is missing',
								   'code' => 403);
						
			$this->logData($this->logInfoPah,$error, 'Error');
			
			return response()->json($error);			
		}		
		
		if  (strlen(trim($this->request['description'])) > 1000) {
			$error = array('status' =>400,
								   'message'=>'Description field is missing',
								   'code' => 403);
						
			$this->logData($this->logInfoPah,$error, 'Error');
			
			return response()->json($error);			
		}	
		$urls = [];
		if ($this->request->has('imageArraySize')) {
			for ($cnt = 0; $cnt < $this->request['imageArraySize']; $cnt++) {
					
				$fileName = 'image'.$cnt; 		
				
				if ($this->request->file($fileName)->isValid()) {
				
					$fileInstance = $this->request->file($fileName);
					$name = $fileInstance->getClientOriginalName();
					$ext = $this->request->file($fileName)->getClientMimeType(); 
					
					$newName = uniqid().'_'.$name.'.jpg';
					
					//$urls[] = 'https://' . env('OCT_AWS_CDN_PATH') . '/staging/structure/forms/' . $newName;
					 $s3Path = $this->request->file($fileName)->storePubliclyAs('staging/Feed/Image', $newName, 'communityS3');
					if ($s3Path == null || !$s3Path) {
						return response()->json(['status' => 'error', 'data' => '', 'message' => 'Error while uploading an image'], 400);
					}
					$urls[] = 'https://' . env('COMMUNITY_AWS_CDN_PATH') . '/Feed/Image/' . $newName;
            
				}
			}
		}
		
		$userdetails = $this->request->user();		
		$feeObj = new FeedPost;
		$feeObj->title = trim($requestJson->title);
		$feeObj->description = trim($this->request['description']);
		$feeObj->is_exlusive = 0;
		$feeObj->project_id = $project_id;
		
		if (count($urls) > 0) {
			$feeObj->content_type = 'image';
			$feeObj->media_rul = $urls;
		}
		$feeObj->is_published = 0;
		
		if ($requestJson->is_published == 'true') {
			$feeObj->is_published = 1;
			$stmTime =  Carbon::now()->timestamp;
			$feeObj->published_time = $stmTime;
			$feeObj->published_date = new \MongoDB\BSON\UTCDateTime( $stmTime);		
		}		
		$feeObj->is_active = 1;
		$feeObj->is_deleted = 0;		
		$feeObj->external_url = ($this->request->has('external_url')) ? $this->request['external_url'] : '';	
		$feeObj->like_count	= 0;
		$feeObj->comment_count	= 0;
		$feeObj->share_count	= 0;
		$feeObj->user_id	= $userdetails->id;	
		
		try {

			$feeObj->save();
						
			$responsData = array('status' =>200,						 
						'message'=>'Feed created successfully',
						'code' => 200);
			
			$this->logData($this->logInfoPah,$responsData, 'DB');
			
			return response()->json($responsData);
			
		} catch (Exception $e){
			
			$responsData = array('status' =>403,
								   'message'=>'Somthing went worng',
								   'code' => 403);
						
			$this->logData($this->logInfoPah,$responsData, 'DB');
			
			return response()->json($responsData);						
		}		
    }
	
	//get Feedlsit from DB
	public function getFeedList(Request $request) {
	
		$header = getallheaders();
 		if(isset($header['orgId']) && ($header['orgId']!='') 
 			&& isset($header['projectId']) && ($header['projectId']!='')
 			&& isset($header['roleId']) && ($header['roleId']!='')
		  )
 		{	
			$org_id =  $header['orgId'];
			$project_id =  $header['projectId'];
			$role_id =  $header['roleId'];
		} else {
			
			$message = "Insufficent header info";
			
			$this->logData($this->logInfoPah ,$message,'Error');
			$response_data = array('status' =>404,
									'message'=>$message, 
									'code'=> 404
									);
			
			return response()->json($response_data,200);			
		}	
		
		$userdetails = $this->request->user();
		$userId  = $userdetails->id;
		
		$feedList =  FeedPost::where(['is_active'=>1,
									'is_deleted'=>0,
									'is_published'=>1,
									//'user_id'=>$userId,
									'project_id' =>$project_id 
									])
							->with('userDetails')
							->with(['feedLike' => function($q) use ($userId){
								        $q->select('_id','feed_id');									
                                 }])
								 ->with('feedComment')
								 ->orderBy('created_at', 'DESC')
							->get();
		
		$feedList  =  $feedList->toArray();	
		if (count($feedList) == 0) {
			$responseData = array('status' =>400, 
								'message' => 'No data available', 								
								'code'=>400);
								
			return response()->json($responseData);
		}
		$feedResult = [];
		
		foreach($feedList as $key=>$data) {
						
			$feedResult[$key]['feedId'] = $data['_id'];
			$feedResult[$key]['title'] = $data['title'];
			$feedResult[$key]['description'] = $data['description'];
			$feedResult[$key]['isExlusive'] = $data['is_exlusive'];
			$feedResult[$key]['contentType'] = isset($data['content_type']) ? $data['content_type'] : '';
			
			if (isset($data['media_rul'])) {
				$feedResult[$key]['mediaUrl'] = $data['media_rul'];
			}
			$feedResult[$key]['externalUrl'] = $data['external_url'];
			$feedResult[$key]['likeCount'] = $data['like_count'];
			$feedResult[$key]['commentCount'] = $data['comment_count'];
			$feedResult[$key]['shareCount'] = $data['share_count'];
			$feedResult[$key]['likeCount'] = $data['like_count'];
			$feedResult[$key]['userName'] = $data['user_details']['name'];
			$feedResult[$key]['createdDateTime']  = date('d M Y g:i a', strtotime($data['created_at']));
			$feedResult[$key]['userProfileImage'] = isset($data['user_details']['profile_pic']) ? $data['user_details']['profile_pic'] : '';
		
		}		
		$responseData = array('status' =>200, 
								'message' => 'Feed List found', 
								'data' => $feedResult,								
								'code'=>200);
								
        return response()->json($responseData);	
		
	}

	//Use can delete from DB	
	public function deleteFeed()
    {				
		if  (!($this->request->has('feed_id'))) {
			
			$responsData = array('status' =>403,
								   'message'=>'Feed id is missing',
								   'code' => 403);						
			$this->logData($this->logInfoPah,$responsData, 'Error');
			
			return response()->json($responsData);			
		}
		
				
		$feeData = FeedPost::where('_id',$this->request['feed_id'])->first();

		if ($feeData) {			
			$feeData->is_deleted = 1;
			
			try {
				$feeData->save();
				
				$message = 'Feed deleted successfully';
				
				$success = array('status' =>200,								
								'message' => $message,							
								'code' => 200);
								
				$this->logData($this->logInfoPah,$success, 'DB');
				
				return response()->json($success);		
				
			} catch (Exception $e){
					
				$error = array('status' =>400,							
							'message' => $e->getMessage(),							
							'code' => 400);
							
				$responsData = array('status' =>400,
									 'message'=>'Something went worng.',
									 'code' => 403);					
							
				$this->logData($this->errorPath,$error, 'Error');
					
				return response()->json($responsData);
			}
		} else {
				$error = array('status' =>403,							
								'message' => 'Invalid feed id',							
								'code' =>403);
								
				$this->logData($this->logInfoPah,$error, 'DB');
				
				return response()->json($error);
		}				
			
	}

	/**
	*
	*
	*
	*
	*/
	public function getCommentList()
    {		
		//echo '<pre>';print_r($this->request->all());exit;
		
		if  (!($this->request->has('feed_id'))) {
			$responsData = array('status' =>403,
								   'message'=>'Feed id is missing',
								   'code' => 403);						
			$this->logData($this->logInfoPah,$responsData, 'Error');
			
			return response()->json($responsData);			
		}
		
		$userdetails = $this->request->user();
		//$userId = $userdetails->id;	
		
		$feedCommentData = FeedComment::where(['feed_id'=>$this->request['feed_id'],
											'is_deleted'=>0])
									->with('userDetails')
									->get();

		if (!$feedCommentData) {
			
			$responsData = array('status' =>403,
								 'message'=>'No data available',
								 'code' => 403);
								   
			$this->logData($this->logInfoPah,$responsData,'Error');
			
			return response()->json($responsData);
			
		}
		
		$responseData = array('status' =>200, 
								'message' => 'Feed Comment list', 
								'data' => $feedCommentData,
								'code'=> 200);
								
        return response()->json($responseData);
		
	}
	
}
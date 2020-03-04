<?php


namespace App\Http\Controllers;
 
use Aws\Sns\SnsClient;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use GuzzleHttp\Client; 
use Illuminate\Http\Request;
use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;
 

 
class SnsController extends Controller
{ 
	 
	public function snsView()
	{  
		return view('sns.snscommon');
	}
	
	/* public function CreateSnsTopic(Request $request)
	{  
		
		 $topicName = $request->input('topicName');

		$subscription_token = 'arn:aws:sns:ap-south-1:958804627673:snstopic:761a210f-e238-4b88-936f-46910b2ae872';
		$topic = 'arn:aws:sns:ap-south-1:958804627673:snstopic';
		$result = $client->createTopic([ 
			'Name' => $topicName, // REQUIRED
			'Tags' => [
				[
					'Key' => 'TopicType', // REQUIRED
					'Value' => 'Testing', // REQUIRED
				], 
			],
		]);
		return redirect()->to('http://13.235.124.3:8090/api/snsView');		   	
	} */
	
	 public function subscribe(Request $request)
	{  
		$client = new SnsClient([ 
		'region' => 'ap-south-1',
		'version' => '2010-03-31',
		 'credentials' => [
        'key' => 'AKIA56PJVSDM5WI3OCW5',
        'secret' => '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',
			]
		]);
		 
		//$subscription_token = 'arn:aws:sns:ap-south-1:958804627673:snstopic:761a210f-e238-4b88-936f-46910b2ae872';
		$topic = 'arn:aws:sns:ap-south-1:958804627673:snstopic';
		$endpoint ='http://the-octopus.com';// $request->input('http');
		$protocol = 'HTTP';
		 
		try{
		$subscribe_result = $client->subscribe([ 
			'Endpoint' => $endpoint,//'jdhumal@bjsindia.org',
			'Protocol' => $protocol, // REQUIRED
			'ReturnSubscriptionArn' => true ,
			
			'TopicArn' => $topic, // REQUIRED 
		]);
		 var_dump($subscribe_result);
		 echo "<br><br><br>";
		 var_dump($request->all());
		  echo "<br><br><br>";
			foreach (getallheaders() as $name => $value) { 
				echo "$name: $value <br>"; 
			} 
		  
		}
		catch(Exception $e){
			var_dump($e);
			}
		// return redirect()->to('http://13.235.124.3:8090/api/snsView');		 	   	
	}  
	
	public function confirmSubscription(Request $request)
	{
		/* $client = new SnsClient([ 
		'region' => 'ap-south-1',
		'version' => '2010-03-31',
		 'credentials' => [
        'key' => 'AKIA56PJVSDM5WI3OCW5',
        'secret' => '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',
			]
		]); */
		 
		$myfile = fopen("../storage/logs/data.log", "a") or die("Unable to open file!");
		$txt = "John Doe\n";
		fwrite($myfile, $txt);
		 
		fclose($myfile);
	}
	
	
	/* public function publishMessage(Request $request)
	{
		$client = new SnsClient([ 
		'region' => 'ap-south-1',
		'version' => '2010-03-31',
		 'credentials' => [
        'key' => 'AKIA56PJVSDM5WI3OCW5',
        'secret' => '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',
			]
		]);
		
		$subject = $request->input('subject');
		$message = $request->input('message');
		//$message = json_encode(['message' => 'This message is sent from script.']);

		$result = $client->publish([
			'TopicArn' => 'arn:aws:sns:ap-south-1:958804627673:snstopic',
			'Message' => $message,
			'Subject' => $subject
		]);
		return redirect()->to('http://13.235.124.3:8090/api/snsView');		
	} */
	
	/* public function showMessage(Request $request)
	{
		$client = new SnsClient([ 
		'region' => 'ap-south-1',
		'version' => '2010-03-31',
		 'credentials' => [
        'key' => 'AKIA56PJVSDM5WI3OCW5',
        'secret' => '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',
			]
		]);
		
		// Fetch the raw POST body containing the message
$postBody = file_get_contents('php://input');

// JSON decode the body to an array of message data
$message = json_decode($postBody, true);
if ($message) {
    // Do something with the data
    echo $message['Message'];
}
	} */
	
	
	
	/* public function sendQueueMessage()
	{
		$client = new SqsClient([ 
		'region' => 'ap-south-1',
		'version' => '2012-11-05',
		 'credentials' => [
        'key' => 'AKIA56PJVSDM5WI3OCW5',
        'secret' => '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',
			]
		]);
		
		$params = [
			'DelaySeconds' => 10,
			'MessageAttributes' => [
				"Title" => [
					'DataType' => "String",
					'StringValue' => "The Hitchhiker's Guide to the Galaxy"
				],
				"Author" => [
					'DataType' => "String",
					'StringValue' => "Douglas Adams."
				],
				"WeeksOn" => [
					'DataType' => "Number",
					'StringValue' => "6"
				]
			],
			'MessageBody' => "Message from jitendra kumar.",
			'QueueUrl' => 'https://sqs.ap-south-1.amazonaws.com/958804627673/sqsMessage'
		];

		try {
			$result = $client->sendMessage($params);
			var_dump($result);
		} catch (AwsException $e) {
			// output error message if fails
			error_log($e->getMessage());
		}
	} */
	
	/* public function receiveSqlMessage()
	{
		$client = new SqsClient([ 
		'region' => 'ap-south-1',
		'version' => '2012-11-05',
		 'credentials' => [
        'key' => 'AKIA56PJVSDM5WI3OCW5',
        'secret' => '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',
			]
		]);
		
		try {
		$result = $client->receiveMessage(array(
        'AttributeNames' => ['SentTimestamp'],
        'MaxNumberOfMessages' => 1,
        'MessageAttributeNames' => ['All'],
        'QueueUrl' => 'https://sqs.ap-south-1.amazonaws.com/958804627673/sqsMessage', // REQUIRED
        'WaitTimeSeconds' => 0,
		));
		if (!empty($result->get('Messages'))) {
       var_dump($result->get('Messages')[0]);
        $result = $client->deleteMessage([
            'QueueUrl' => 'https://sqs.ap-south-1.amazonaws.com/958804627673/sqsMessage', // REQUIRED
            'ReceiptHandle' => $result->get('Messages')[0]['ReceiptHandle'] // REQUIRED
        ]);
		} else {
				echo "No messages in queue. \n";
			}
		} catch (AwsException $e) {
			// output error message if fails
			error_log($e->getMessage());
		}
	} */
	
} 
<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router->get('/tests','EventTaskController@tests');
$router->get('/', function () use ($router) {
    return $router->app->version();
});

/*$router->post('/auth/login', 'AuthController@postLogin');
//$router->get('/test', 'AuthController@testauth');
$router->group(['middleware' => 'auth:api'], function($router)
{
    $router->get('/test', function() {
        return response()->json([
            'message' => 'Hello Worldsss!',
        ]);
    });
    $router->get('/siteusers', 'AuthController@getSiteUsers');
});*/

$api = app('Dingo\Api\Routing\Router');
//date_default_timezone_set('Asia/Kolkata'); 
$api->version('v1',function($api){
    
    $api->group(['namespace'=>'App\Http\Controllers','middleware'=>['cors']],function($api){
        $api->get('message/otp','MessageAuthController@sendOTP');
        $api->get('token','MessageAuthController@verifyOTP');
        $api->post('token','MessageAuthController@verifyOTPLogin');
        $api->post('refreshtoken','MessageAuthController@refreshToken');
        $api->get('organizations','OrganisationController@listOrgs');
        $api->get('projects/{org_id}','OrganisationController@getorgprojects');
        $api->get('states','LocationController@getstates');
        $api->get('location/level/{orgId}/{jurisdictionTypeId}/{jurisdictionLevel}','LocationController@getLevelData');
        $api->post('locationV2/level','LocationController@getLevelDataV2');
        $api->post('selectedLocationData/level','LocationController@selectedLocationData');
		$api->get('/downloadBooklet/{meetId}/{gridType}','downloadPdfController@downloadBooklet');
        //genrated for testing purpose only
        $api->get('downloadCertificateForm/{type}', 'CertificateController@downloadCertificateForm');
        $api->post('downloadCertificate', 'CertificateController@downloadCertificate');
        
		//seva consent 
        $api->post('savesevaConsentForm', 'ContentMgmtController@savesevaConsentForm');
        $api->get('sevaConsentForm', 'ContentMgmtController@sevaConsentForm');
        $api->get('sevaConsentlist', 'ContentMgmtController@sevaConsentlist');
        $api->get('sevaConsentDetailsList', 'ContentMgmtController@sevaConsentDetailsList');
		
		
        $api->get('selectVan', 'mobileDispensarySevaController@selectVanForm');


        $api->post('loadPatientForm', 'mobileDispensarySevaController@loadPatientForm');
        $api->post('savePatientInfo', 'mobileDispensarySevaController@savePatientInfo');
        //in this get vanCode from cookies
        $api->get('showPatientInfoForm', 'mobileDispensarySevaController@showPatientInfoForm');


        $api->get('showPatientContactDetailsForm', 'mobileDispensarySevaController@showPatientContactDetailsForm');

        $api->post('savePatientContactDetails', 'mobileDispensarySevaController@savePatientContactDetails');

        $api->get('patientContactDetailsList', 'mobileDispensarySevaController@patientContactDetailsList');
        
       // $api->post('testPageSave','mobileDispensarySevaController@testPageSave');

        $api->get('insertVanForm', 'mobileDispensarySevaController@insertVanForm');
        $api->post('insertVanInfo', 'mobileDispensarySevaController@insertVanInfo');

        $api->get('vanDetailsList', 'mobileDispensarySevaController@vanDetailsList');

        $api->get('patientList', 'mobileDispensarySevaController@patientList');
        $api->get('dailyVehicleRegister', 'mobileDispensarySevaController@dailyVehicleRegister');

        $api->get('insertCityForm', 'mobileDispensarySevaController@showAddCityForm');
        $api->post('insertCity', 'mobileDispensarySevaController@insertCity');

        $api->get('testpage1','mobileDispensarySevaController@testpage1');

        $api->get('insertDepotForm', 'mobileDispensarySevaController@showAddDepotForm');
        $api->post('getSelectedCityVan', 'mobileDispensarySevaController@getSelectedCityVan');
        

        $api->post('insertDepot', 'mobileDispensarySevaController@insertDepot');

        $api->get('webOptionView', 'mobileDispensarySevaController@webOptionView');

        $api->get('downloadCertificateReport', 'CertificateController@downloadCertificateReport');
        
        
        $api->get('configuration','OrganisationController@configuration');
		
		$api->get('cron','EventTaskController@cron');
		$api->get('UserToMeet','MeetController@UserToMeet');
		$api->post('imageSevaUpload1','ImageUploadTestController@imageSevaUpload');
        $api->post('testPageSave','ImageUploadTestController@testPageSave');
		$api->get('testPage','ImageUploadTestController@testpage');
		$api->post('imageSevaUpload','mobileDispensarySevaController@imageSevaUpload');
		
		
	 
		
		//google map apis
		
		$api->get('getallstate','Structure1Controller@getallstate');	
		$api->get('getalldistrict/{stateid}','Structure1Controller@getalldistrict');	
		$api->get('getalltaluka/{districtid}','Structure1Controller@getalltaluka');	
		$api->get('getstate/{type}/{id}','Structure1Controller@getstate');		
		$api->get('getStructures/{talukaId}','Structure1Controller@getStructures');	
		$api->get('getmachines/{id}','Structure1Controller@getmachines');
		//url for genrating access token
        $api->post('testlogin','ProgramController@testOtpLogin');
		//test API 
		$api->get('roleIds','Structure1Controller@roleIds');
		//structure preparation details
		$api->get('getStructurePreparedData','StructureController@getStructurePreparedData');
		//$api->post('machineWorkLog','OpratorController@machineWorkingDetails');
		//$api->get('getMachineData','OpratorController@getMachineData');		
		
    
	});
   
    $api->group(['prefix'=>'oauth'],function($api){
        $api->post('token','\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
    });
    $api->group(['namespace'=>'App\Http\Controllers','middleware'=>['auth:api','cors']],function($api){
        $api->get('roles/{org_id}/{project_id}','RoleController@getorgroles');
        $api->get('userLocationUpdate','UserController@userLocationUpdate');
        //$api->get('user/{phone}','UserController@show');
        $api->get('getUserDetails','UserController@getUserDetails');
        $api->get('getUserProfileDetails','UserController@getUserProfileDetails');
        //not yet use any where in current application
        $api->get('users','UserController@getUsers');
        $api->post('mvUserInfo','UserController@mvUserInfo');
		$api->get('test/{id}','UserController@test');
        $api->get('tasks','TaskController@show');
        $api->get('tasksOfUser','TaskController@getTask');
        //API to update user firebase Id
        $api->put('updateFirebaseId', 'UserController@updateFirebaseId');
		
		
		//new apis for events
		
       $api->get('statuscount/{user_id}/{org_id}','EventTaskController@statuscount');
       $api->post('addmember','EventTaskController@addmember');
       $api->post('deletemember','EventTaskController@deletemember');
       $api->post('addform/','EventTaskController@addform');
       $api->post('submitAttendanceEvent','EventTaskController@submitAttendanceEvent');
       $api->get('generateAttendanceCode/{eventId}','EventTaskController@generateAttendanceCode');
       $api->post('event_task','EventTaskController@event_task');
       $api->post('getEventByMonth','EventTaskController@getEventByMonth');
       $api->post('getEventByDay','EventTaskController@getEventByDay');
       $api->post('addmembertoevent','EventTaskController@addmembertoevent');
       $api->get('roleEvent','EventTaskController@roleEvent');
       $api->get('getEventMembers/{eventId}','EventTaskController@getEventMembers');
      
       $api->post('test','ProgramController@test');
       $api->get('push','EventTaskController@push');
       $api->get('dumpMaster','DumpMasterController@dumpData');
		//new apis for tasks
		
		$api->get('addmembertask','EventTaskController@addmembertask');
		$api->get('deleteTask/{taskId}','EventTaskController@deleteTask');
		$api->get('taskMarkComplete/{taskId}','EventTaskController@taskMarkComplete');
		// $api->get('cron','EventTaskController@cron');


		 //---------------------Meet API's Start------------------- 
		  
		 $api->get('checkProfile/{mobile}{meetId}','MeetController@checkProfile');
		 $api->get('meet_types','MeetController@meet_types');
		 $api->post('getMatrimonyRoleUsers','MeetController@getMatrimonyRoleUsers');
		 $api->post('insertMeet','MeetController@insertMeet');
		 $api->get('meetpublished/{meetId}','MeetController@meetpublished');
		 $api->post('getMeet','MeetController@getMeet');
		 $api->get('masterData','MeetController@masterData');
		 $api->post('insertUser','MeetController@insertUser');
		 $api->get('archiveMeet/{meetId}/{type}','MeetController@archiveMeet');
		 $api->post('registration_meet','MeetController@registration_meet');
         $api->get('allocateBadge/{meetId}/{type}','MeetController@allocateBadge');
         $api->get('getMeetUsers/{meetId}','MeetController@getMeetUsers');
         $api->post('userApproval','MeetController@userApproval');
         $api->post('markAttendance_interview','MeetController@markAttendance_interview');
         $api->get('isFinalize/{meetId}','MeetController@isFinalize');
         $api->get('group_batches/{meetId}','MeetController@group_batches');
         $api->post('search','MeetController@search');		 
		 //---------------------Meet API's End-------------------

		
       //--------------------Planner API's start ------------------//
       //api for getting planner dashboard data
        $api->get('plannersummary','PlannerController@getDashBoardSummary');
       //API for getting holiday list
        $api->get('getHolidayList/{year}/{month}','PlannerController@getHolidayList');
        //API for getting current year holiday list
        $api->get('getYearHolidayList','PlannerController@getYearHolidayList');
        //API to get user leave balance 
        $api->get('getUserLeaveBalance','PlannerController@getUserLeaveBalance');
        //API to get user Leave data for total,used, balance
        $api->get('getUserLeaveSummery','PlannerController@getUserLeaveSummery');
        $api->get('getTeamAttendance/{date}','PlannerController@getTeamAttendance');
        // $api->get('getUserRole/{userId}','PlannerController@getUserRole'); 

        //api for getting team user attendance for month
        //$api->post('getTeamUserAttendance','AttendanceController@getTeamUserAttendance'); 
        //-----------Teammanagment API's start-----------------------------
 
        //api for teammanagment dashboard
        $api->get('teammanagmentsummary','TeamManagmentController@getallcount');
        //api for teammanagment filter
        $api->get('teammanagmentfilter','TeamManagmentController@getfilterbytype');
        //api for teammanagment userlist according to filter 
        $api->post('getlistbyfilter','TeamManagmentController@getListByFilter');
        //api for user detail and formlist of single user
        $api->post('getuserbyfilter','TeamManagmentController@getUserByFilter');
        //api for teammanagment form detail 
        $api->post('getformdetail','TeamManagmentController@getformdetail');
        //api for approval
        $api->post('applicationapproval','TeamManagmentController@applicationapproval');
        //-----------Teammanagment API''s end-----------------------------
	
        //----------------Attendance API's start ---------------------//			
        //api for getting user attendance for month
        $api->get('getuserattendance/{year}/{month}','AttendanceController@getAttendanceByMonth');
        //api for inserting attendance record like check in or check out
        $api->post('insertAttendance','AttendanceController@insertAttendance');
        //api for getting specific date attendance record
        $api->get('attendanceOfDate/{date}/','AttendanceController@attendanceOfDate');

        //api for getting team user attendance for month
        $api->post('getTeamUserAttendance','AttendanceController@getTeamUserAttendance');

        //------------Attendance API's end -------------------------//

        //------------Leaves API's start ------------------------//
        //api for getting leave summarized  data for user
        $api->get('getLeavesSummary/{year}/{month}','PlannerLeavesController@getLeavesSummary');
		$api->post('createLeave','EventTaskController@createLeave');
		$api->post('editLeave','EventTaskController@editLeave');
		$api->get('deleteLeave/{leaveId}','EventTaskController@deleteLeave');
		$api->post('applyCompoff','EventTaskController@applyCompoff');
		
		$api->get('formss/schemaa/{form_id}','EventTaskController@getSurveyDetail');
		$api->get('formss/resultt/{form_id}','EventTaskController@showResponses');
        //-----------Leaves API's end -------------------------//


        //--------------------Content Management API's start ------------------//
           $api->get('contentDashboard','ContentMgmtController@contentDashboard');
        //--------------------Content Management API's end ----------------//   

        //-------------------Testing API's ------------------------//   
        $api->get('getAddress','getAddressController@getGeoCodes');  
        $api->get('getDistance','getAddressController@getDistance');  
        $api->get('clearRecord','clearRecordController@clearRecord');  
        //---------------- Testing API's end ------------------//


		$api->get('orgs','OrganisationController@show');
        $api->put('updateUser/{phone}', ['uses' => 'UserController@update']);
        $api->get('modules/{org_id}/{role_id}','RoleController@getroleconfig');
        $api->put('users/approval/{approvalLogId}', ['uses' => 'UserController@approveuser']);
        $api->post('upload-image', 'UserController@upload');
        $api->post('upload-images', 'UserController@uploadImages');
        
        $api->get('forms/schema','SurveyController@getSurveys');
        $api->get('forms/schema/{form_id}','SurveyController@getSurveyDetails');
        
        $api->get('forms/result/{form_id}','SurveyController@showResponse');
        $api->post('forms/result/{form_id}','SurveyController@createResponse');
        $api->put('forms/result/{form_id}/{response_id}','SurveyController@updateSurvey');
        $api->delete('forms/result/{formId}/{recordId}','SurveyController@deleteFormResponse');

        $api->get('locations', 'LocationController@getLocations');
        $api->get('districts', 'LocationController@getDistricts');
        $api->get('talukas', 'LocationController@getTalukas');
        $api->get('villages', 'LocationController@getVillages');
        $api->get('clusters', 'LocationController@getClusters');
        $api->get('getCity', 'LocationController@getCity');
        $api->get('getCountry', 'LocationController@getCountry');

        $api->get('jurisdiction-types[/{id}]', 'JurisdictionTypeController@index');
        $api->get('reports[/{id}]', 'ReportController@index');

        $api->get('users/approvals','UserController@approvalList');

        $api->post('structure/prepare/{formId}', 'StructureTrackingController@prepare');
		$api->put('structure/prepare/{formId}/{structureId}', 'StructureTrackingController@updatePreparedStructure');
        $api->get('structure/prepare', 'StructureTrackingController@get');
        $api->get('structure/prepare/{formId}', 'StructureTrackingController@getStructures');        
		$api->delete('structure/prepare/{formId}/{recordId}', 'StructureTrackingController@deleteStructureTracking');
        $api->post('structure/complete/{formId}', 'StructureTrackingController@complete');
        $api->put('structure/complete/{formId}/{structureId}', 'StructureTrackingController@updateComplete');
		$api->get('structure/complete/{formId}', 'StructureTrackingController@getStructures');
		$api->delete('structure/complete/{formId}/{recordId}', 'StructureTrackingController@deleteStructureTracking');
        $api->get('structuremaster/code', 'StructureMasterController@get');
        $api->post('structure/{form_id}', 'StructureMasterController@structureCreate');
        $api->get('structure/{form_id}', 'StructureMasterController@getStructures');
        $api->delete('structure/{formId}/{recordId}','StructureMasterController@deleteStructure');
        
        $api->post('machine/deploy/{form_id}','MachineTrackingController@machineDeploy');
        $api->put('machine/deploy/{formId}/{machine_id}','MachineTrackingController@updateDeployedMachine');
        $api->get('machine/deploy/{form_id}','MachineTrackingController@getMachinesDeployed');
        $api->delete('machine/deploy/{form_id}/{recordId}','MachineTrackingController@deleteMachineTracking');
        $api->get('machine/deploy','MachineTrackingController@getDeploymentInfo');
        $api->post('machine/shift/{form_id}','MachineTrackingController@machineShift');
        $api->put('machine/shift/{formId}/{machine_shift_id}','MachineTrackingController@updateMachineShift');
        $api->get('machine/shift/{form_id}','MachineTrackingController@getMachinesShifted');
        $api->delete('machine/shift/{form_id}/{recordId}','MachineTrackingController@deleteMachineShift');
        $api->get('machine/shift', 'MachineTrackingController@getShiftingInfo');
        $api->get('machine/mou','MachineTrackingController@machineMoU');
        $api->post('machine/mou/{form_id}','MachineTrackingController@createMachineMoU');
        $api->put('machine/mou/{formId}/{recordId}','MachineTrackingController@updateMachineMoU');
        $api->get('machine/mou/{form_id}','MachineTrackingController@getMachineMoU');
        $api->delete('machine/mou/{form_id}/{recordId}','MachineTrackingController@deleteMachineMoU');
        $api->get('machinemaster/code', 'MachineMasterController@getMachineCode');
        
		$api->get('user/approvals', 'UserController@getApprovalLog');
        $api->post('machine/{form_id}', 'MachineMasterController@createMachineCode');
        $api->get('machine/{form_id}', 'MachineMasterController@getMachineCodes');
        $api->delete('machine/{form_id}/{recordId}','MachineMasterController@deleteMachine');
        
        $api->get('forms/aggregate/{form_id}','SurveyController@showAggregateResponse');
        $api->post('forms/aggregate/{form_id}','SurveyController@createAggregateResponse');
        $api->put('forms/aggregate/{form_id}/{group_id}','SurveyController@updateAggregateResponse');
        $api->delete('forms/aggregate/{form_id}/{group_id}','SurveyController@deleteAggregateResponse');

        $api->post('machine/deploy/aggregate/{form_id}','MachineTrackingController@machineAggregateDeploy');
        $api->put('machine/deploy/aggregate/{form_id}/{group_id}','MachineTrackingController@updateAggregateDeployedMachine');
        $api->get('machine/deploy/aggregate/{form_id}','MachineTrackingController@getAggregateMachinesDeployed');
        $api->delete('machine/deploy/aggregate/{form_id}/{group_id}','MachineTrackingController@deleteAggregateMachinesDeployed');

        $api->post('machine/workhours/aggregate/{form_id}','SurveyController@machineAggregateWorkhours');
        $api->put('machine/workhours/aggregate/{form_id}/{group_id}','SurveyController@updateAggregateWorkhours');
        $api->get('machine/workhours/aggregate/{form_id}','SurveyController@showAggregateResponse');
        $api->delete('machine/workhours/aggregate/{form_id}/{group_id}','SurveyController@deleteAggregateResponse');        

        $api->post('silttransportation/{form_id}','SurveyController@siltTransportation');
        $api->put('silttransportation/{form_id}/{record_id}','SurveyController@updateSiltTransportation');
        $api->get('silttransportation/{form_id}','SurveyController@showResponse');
        $api->delete('silttransportation/{form_id}/{record_id}','SurveyController@deleteFormResponse'); 

        $api->get('entity/{entity_id}/column/{column_name}','EntityController@getEntityInfo');
        
        $api->post('events', 'EventController@create');
		$api->get('events', 'EventController@getEvents');

        $api->get('event-types','EventTypeController@getEventTypes');

        $api->post('machinemeterreading/{form_id}','SurveyController@machineMeterReading');
        $api->put('machinemeterreading/{form_id}/{record_id}','SurveyController@updateMachineMeterReading');
        $api->get('machinemeterreading/{form_id}','SurveyController@showResponse');
        $api->delete('machinemeterreading/{form_id}/{record_id}','SurveyController@deleteFormResponse'); 

        $api->post('farmersilttransportation/{form_id}','SurveyController@farmerSiltTransportation');
        $api->put('farmersilttransportation/{form_id}/{record_id}','SurveyController@updateFarmerSiltTransportation');
        $api->get('farmersilttransportation/{form_id}','SurveyController@showResponse');
        $api->delete('farmersilttransportation/{form_id}/{record_id}','SurveyController@deleteFormResponse'); 
		
		
		//-----------------Machine API's start------------------		
		$api->post('machineList','MachineController@machineList');
		$api->get('statusChange/{id}/{code}/{statuscodes}/{type}','MachineController@statusChange');
		$api->post('getMachineAnalytics','MachineController@getMachineAnalytics'); 
		//$api->post('machineMou','MachineController@machineMou'); 
		$api->post('MOUTerminateDeployed','MachineController@MOUTerminateDeployed'); 
		$api->get('machineDetails/{machineId}/{type}','MachineController@machineDetails'); 
		//-----------------Machine API's End------------------

		//StructureController API starts here	
		/*$api->post('structureList','StructureController@getStructureList'); 
		$api->get('structureAnalyst','StructureController@getStructureAnalyst'); 
        $api->get('structureMasterData','StructureController@getStructureMasterData'); 
		$api->post('prepareStructure','StructureController@saveStructurePreparedData'); 
		$api->post('getAllAvlMachineList','StructureController@getAllMachineAvalList'); 
		$api->post('machineDeployed','StructureController@machineDeployed');
		$api->post('structureVisit','Structure1Controller@structureVisit');	
		$api->post('communityMobilisation','Structure1Controller@communityMobilisation');		
		//machine APIs
		$api->post('dieselRecord','StructureController@machineDieselRecord'); 
		$api->post('siltRecord','StructureController@siltDetails'); 
		$api->post('machineShift','StructureController@machineShifting');
		
		$api->get('masterDataList','Structure1Controller@getMasterData');
		$api->post('structureStatus','Structure1Controller@changeStructureStatus');	
		//oprator 
		$api->post('machineWorkLog','OpratorController@machineWorkingDetails'); */
		
		//StructureController API starts here

		$api->post('machineVisit','StructureController@machineVisit');		
		$api->post('dieselRecord','StructureController@machineDieselRecord');
		$api->post('siltDetails','StructureController@siltDetails');
		$api->post('createMachine','MachineController@createMachine');
		$api->post('prepareStructure','StructureController@saveStructurePreparedData');	
		$api->post('machineWorkLog','OpratorController@machineWorkingDetails');
		$api->post('structureVisit','StructureController@structureVisit');	
		$api->post('communityMobilisation','StructureController@communityMobilisation');
		$api->post('sowStructure','StructureController@closeStructure');
		$api->post('machineMou','MachineController@machineMou');
		
		$api->post('structureList','StructureController@getStructureList'); 
		$api->post('structureAnalyst','StructureController@getStructureAnalyst'); 
        $api->get('masterDataList','StructureController@getStructureMasterData'); 
		//$api->post('prepareStructure','StructureController@saveStructurePreparedData'); 
		$api->post('getAllAvlMachineList','StructureController@getAllMachineAvalList'); 
		$api->post('machineDeployed','StructureController@machineDeployed');
		$api->post('createStructure','StructureController@createStructure');		
		$api->post('machineShift','StructureController@machineShifting');
		$api->post('structureStatus','StructureController@changeStructureStatus');
		//machine APIs
		//$api->post('dieselRecord','Structure1Controller@machineDieselRecord'); 
		$api->post('workLog','MachineController@getWorkDetails');
		$api->post('siltRecord','StructureController@siltDetails'); 

		
		
		//$api->get('masterDataList','StructureController@getMasterData');	
				
		//oprator 
		$api->post('logDetails','OpratorController@getMachineWorkLogDetails');
		$api->post('machineNonUtilization','OpratorController@machineNonUtilisation');
		$api->get('getMachineData','OpratorController@getMachineData');		
		$api->get('roleAccess','StructureController@roleAccess');	
		$api->post('catchmentVillages','StructureController@getCathmentVillages');
		$api->post('getMachineWorkLogData','OpratorController@getMachineWorkLogData');
		//$api->post('machineWorkLog','OpratorController@machineWorkingDetails');
		//Feed APIs
		$api->post('newFeed','FeedController@createFeed');			
		$api->get('getFeedList','FeedController@getFeedList');
		$api->post('deleteFeed','FeedController@deleteFeed');
        $api->post('commentList','FeedController@getCommentList');
			
		$api->post('curlCall','Structure1Controller@curlCall');
		$api->post('awsImageMove','UserController@awsImageMove');
		$api->post('structureBoundary','StructureController@structureBoundary');
		//$api->post('awsImageMove','UserController@awsImageMove');
		$api->post('machineMouUpload','MachineController@machineMouUpload');
		$api->post('machineSignOff','MachineController@machineSignOff');
		$api->post('editWorkLog','MachineController@editWorkLog');
		$api->post('machineAvailable','MachineController@machineAvailable');
		
		//new oprator 
		$api->post('createOperator','MachineController@createOperator');
		$api->get('getOperatorList','MachineController@getOperatorList');
		$api->post('assignOperator','MachineController@assignOperator');
		$api->post('releaseOperator','MachineController@releaseOperator');
		
		$api->get('insertMapping','MachineController@insertMapping');
		
		//date synch API
		$api->post('dataSynch','OpratorController@dataSynch');		
						
		
    });
});

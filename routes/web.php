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

         //genrated for testing purpose only
        //url for genrating access token
        $api->post('testlogin','ProgramController@testOtpLogin');
		
    });
   
    $api->group(['prefix'=>'oauth'],function($api){
        $api->post('token','\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
    });
    $api->group(['namespace'=>'App\Http\Controllers','middleware'=>['auth:api','cors']],function($api){
        $api->get('roles/{org_id}','RoleController@getorgroles');
        //$api->get('user/{phone}','UserController@show');
        $api->get('user','UserController@getUserDetails');
        $api->get('users','UserController@getUsers');
		$api->get('test/{id}','UserController@test');
        $api->get('tasks','TaskController@show');
        $api->get('tasksOfUser','TaskController@getTask');
		
		
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
       $api->get('getEventMembers/{eventId}','EventTaskController@getEventMembers');
      
       $api->post('test','ProgramController@test');

		//new apis for tasks
		
		$api->get('addmembertask','EventTaskController@addmembertask');
		$api->get('deleteTask/{taskId}','EventTaskController@deleteTask');
		$api->get('taskMarkComplete/{taskId}','EventTaskController@taskMarkComplete');


		 

       //--------------------Planner API's start ------------------//
       //api for getting planner dashboard data
        $api->get('plannersummary','PlannerController@getDashBoardSummary');
       //API for getting holiday list
        $api->get('getHolidayList/{year}/{month}','PlannerController@getHolidayList');
        //API for getting current year holiday list
        $api->get('getYearHolidayList','PlannerController@getYearHolidayList');

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
        $api->put('users/{phone}', ['uses' => 'UserController@update']);
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
		
		
		
    });
});
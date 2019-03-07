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
        $api->post('refreshtoken','MessageAuthController@refreshToken');
        $api->get('organizations','OrganisationController@listOrgs');
        $api->get('projects/{org_id}','OrganisationController@getorgprojects');
        $api->get('states','LocationController@getstates');
        $api->get('location/level/{orgId}/{jurisdictionTypeId}/{jurisdictionLevel}','LocationController@getLevelData');
        
    });
    
    $api->group(['prefix'=>'oauth'],function($api){
        $api->post('token','\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
    });

    $api->group(['namespace'=>'App\Http\Controllers','middleware'=>['auth:api','cors']],function($api){
        $api->get('roles/{org_id}','RoleController@getorgroles');
        //$api->get('user/{phone}','UserController@show');
        $api->get('user','UserController@getUserDetails');
		
        $api->get('tasks','TaskController@show');
        $api->get('tasksOfUser','TaskController@getTask');

        $api->get('orgs','OrganisationController@show');
        $api->put('users/{phone}', ['uses' => 'UserController@update']);
        $api->get('modules/{org_id}/{role_id}','RoleController@getroleconfig');
        $api->put('users/approval/{approvalLogId}', ['uses' => 'UserController@approveuser']);
        $api->post('upload-image', 'UserController@upload');

        $api->get('forms/schema','SurveyController@getSurveys');
        $api->get('forms/schema/{form_id}','SurveyController@getSurveyDetails');

        $api->get('forms/result/{form_id}','SurveyController@showResponse');
        $api->post('forms/result/{form_id}','SurveyController@createResponse');
        $api->put('forms/result/{form_id}/{response_id}','SurveyController@updateSurvey');

        $api->get('locations', 'LocationController@getLocations');
        $api->get('districts', 'LocationController@getDistricts');
        $api->get('talukas', 'LocationController@getTalukas');
        $api->get('villages', 'LocationController@getVillages');
        $api->get('clusters', 'LocationController@getClusters');

        $api->get('jurisdiction-types[/{id}]', 'JurisdictionTypeController@index');
        $api->get('reports[/{id}]', 'ReportController@index');

        $api->get('users/approvals','UserController@approvalList');

        $api->post('structure/prepare/{formId}', 'StructureTrackingController@prepare');
		$api->put('structure/prepare/{structureId}', 'StructureTrackingController@updatePreparedStructure');
        $api->get('structure/prepare', 'StructureTrackingController@get');
        $api->post('structure/complete/{formId}', 'StructureTrackingController@complete');
        $api->put('structure/complete/{structureId}', 'StructureTrackingController@updateComplete');
        $api->get('structure/code', 'StructureMasterController@get');
        $api->post('structure/{form_id}', 'StructureMasterController@structureCreate');

        $api->post('machine/deploy/{form_id}','MachineTrackingController@machineDeploy');
        $api->put('machine/deploy/{machine_id}','MachineTrackingController@updateDeployedMachine');
        $api->get('machine/deploy','MachineTrackingController@getDeploymentInfo');
        $api->post('machine/shift/{form_id}','MachineTrackingController@machineShift');
        $api->put('machine/shift/{machine_shift_id}','MachineTrackingController@updateMachineShift');
        $api->get('machine/shift', 'MachineTrackingController@getShiftingInfo');
        $api->get('machine/mou','MachineTrackingController@machineMoU');
        $api->post('machine/mou/{form_id}','MachineTrackingController@createMachineMoU');
        $api->put('machine/mou/{form_id}/{response_id}','MachineTrackingController@updateMachineMoU');
        $api->get('machine/code', 'MachineMasterController@getMachineCode');

		$api->get('user/approvals', 'UserController@getApprovalLog');
        $api->post('machine/{form_id}', 'MachineMasterController@createMachineCode');
    });

});
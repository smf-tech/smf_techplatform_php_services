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
        $api->get('organizations','OrganisationController@listorgs');
        $api->get('roles/{org_id}','RoleController@getorgroles');
        $api->get('projects/{org_id}','OrganisationController@getorgprojects');
        $api->get('states','LocationController@getstates');
        $api->get('location/level/{state_id}/{level}','LocationController@getleveldata');

    });
    
    $api->group(['prefix'=>'oauth'],function($api){
        $api->post('token','\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
    });

    $api->group(['namespace'=>'App\Http\Controllers','middleware'=>['auth:api','cors']],function($api){

        $api->get('users','UserController@show');
        $api->get('user','UserController@getUserDetails');
		
        $api->get('tasks','TaskController@show');
        $api->get('tasksOfUser','TaskController@getTask');

        $api->get('orgs','OrganisationController@show');
        $api->get('surveysOfOrganisation','OrganisationController@getSurveys');
        $api->get('getSurveyDetails/{survey_id}','OrganisationController@getSurveyDetails');
        $api->put('users/{phone}', ['uses' => 'UserController@update']);
        $api->get('modules/{org_id}/{role_id}','RoleController@getroleconfig');
        $api->put('users/approval/{phone}', ['uses' => 'UserController@approveuser']);

        $api->get('forms/schema','SurveyController@getSurveys');
        $api->get('forms/schema/{form_id}','SurveyController@getSurveyDetails');
        // $api->delete('forms/{form}','SurveyController@deleteSurvey');
        // $api->put('forms/{form_id}','SurveyController@updateSurvey');      

        $api->get('forms/result/{form_id}','SurveyController@showResponse');
        $api->post('forms/result/{form_id}','SurveyController@createResponse');
        $api->put('forms/result/{form_id}','SurveyController@updateSurvey');
    });

});
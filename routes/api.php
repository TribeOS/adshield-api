<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/failed', ['as' => 'ApiError', 'uses' => 'Adshield\ApiController@RequestFailed']);

/**
 * route for frontend
 */
//get stats
Route::get('/adshield/getstats', ['uses' => 'Adshield\ApiController@GetAdshieldStats'])
	->middleware('authapi');

/**
 * routes for third party websites
 */

//main route to load adshield
Route::get('/adshield', ['uses' => 'Adshield\AdshieldController@ImportAdshield'])
	->middleware('authapi');

/**
 * route for adshield api calls from Third Party
 */

/**
 * stat logging for iframed or direct
 */
Route::post('/adshield/logstat', ['as' => 'AdshieldLogstat', 'uses' => 'Adshield\ApiStatController@DoLog'])
	->middleware('api.access');

/**
 * logging and checking for safe/unsafe referrer
 */
Route::post('/adshield/checkurl', ['as' => 'AdshieldCheckUrl', 'uses' => 'Adshield\ApiReferrerController@Check'])
	->middleware('api.access');

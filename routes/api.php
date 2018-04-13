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
	Route::get('/{apikey}/getstats/{type?}', ['uses' => 'Adshield\ApiController@GetAdshieldStats'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->where('type', '[a-zA-Z_]+')
		->middleware('authapi');


/**
 * routes for third party websites
 */

//main route to load adshield
Route::get('/adshieldjs', ['uses' => 'Adshield\AdshieldController@ImportAdshield'])
	->middleware('api.access');

/**
 * route for adshield api calls from Third Party
 */

/**
 * adshield stat and checker
 */
Route::post('/ashandler', ['as' => 'AdshieldHandler', 'uses' => 'Adshield\AdshieldStatController@adShield'])
	->middleware('api.access');

/**
 * stat logging for iframed or direct
 */
Route::post('/logstat', ['as' => 'AdshieldLogstat', 'uses' => 'Adshield\ApiStatController@DoLog'])
	->middleware('api.access');

/**
 * logging and checking for safe/unsafe referrer
 */
Route::post('/checkurl', ['as' => 'AdshieldCheckUrl', 'uses' => 'Adshield\ApiReferrerController@Check'])
	->middleware('api.access');

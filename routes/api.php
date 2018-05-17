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

URL::forceScheme('https');


Route::get("/", function() {
	return redirect()->away('https://tribeos.io');
});

Route::get('/failed', ['as' => 'ApiError', 'uses' => 'Adshield\VisualizerController@RequestFailed']);


/**
 * routes for third party websites
 */

//main route to load adshield
Route::get('adshieldjs', ['uses' => 'Adshield\AdshieldController@ImportAdshield'])
	->middleware('api.access');

/**
 * route for adshield api calls from Third Party
 */

/**
 * adshield stat and checker
 */
Route::post('ashandler', ['as' => 'AdshieldHandler', 'uses' => 'Adshield\AdshieldStatController@adShield'])
	->middleware('api.access');

/**
 * stat logging for iframed or direct
 */
Route::post('logstat', ['as' => 'AdshieldLogstat', 'uses' => 'Adshield\ApiStatController@DoLog'])
	->middleware('api.access');

/**
 * logging and checking for safe/unsafe referrer
 */
Route::post('checkurl', ['as' => 'AdshieldCheckUrl', 'uses' => 'Adshield\ApiReferrerController@Check'])
	->middleware('api.access');



/**
 * route for frontend
 */

	//ip access list
	Route::get('/{apikey}/ipaccesslist', ['uses' => 'Adshield\IpAccessListController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//ip violators list
	Route::get('/{apikey}/ipviolatorslist', ['uses' => 'Adshield\IpViolatorListController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//ip violators graph
	Route::get('/{apikey}/ipviolatorgraph', ['uses' => 'Adshield\IpViolatorListController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');


	//get stats
	Route::get('/{apikey}/{type?}', ['uses' => 'Adshield\VisualizerController@GetAdshieldStats'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->where('type', '[a-zA-Z_]+')
		->middleware('authapi');


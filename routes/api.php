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
	Route::get('/{apikey}/ipaccesslists', ['uses' => 'Adshield\IpAccessListController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//ip violators list
	Route::get('/{apikey}/knownViolators', ['uses' => 'Adshield\IpViolatorListController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//ip violators graph
	Route::get('/{apikey}/ipViolatorGraphs', ['uses' => 'Adshield\IpViolatorListController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//ip js check failed list
	Route::get('/{apikey}/jsCheckFails', ['uses' => 'Adshield\IpJsCheckFailedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//ip js check failed graph
	Route::get('/{apikey}/jsCheckFailGraphs', ['uses' => 'Adshield\IpJsCheckFailedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//js not loaded list
	Route::get('/{apikey}/jsNotLoadeds', ['uses' => 'Adshield\JsNotLoadedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//js not loaded graph
	Route::get('/{apikey}/jsNotLoadedGraphs', ['uses' => 'Adshield\JsNotLoadedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator user agent list
	Route::get('/{apikey}/knownViolatorUserAgents', ['uses' => 'Adshield\KnownViolatorUserAgentController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator user agent graph
	Route::get('/{apikey}/knownViolatorUserAgentGraphs', ['uses' => 'Adshield\KnownViolatorUserAgentController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//browser integrity check list
	Route::get('/{apikey}/browserIntegrityChecks', ['uses' => 'Adshield\BrowserIntegrityCheckController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//browser integrity check graph
	Route::get('/{apikey}/browserIntegrityCheckGraphs', ['uses' => 'Adshield\BrowserIntegrityCheckController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//suspicious user agent list
	Route::get('/{apikey}/suspUserAgents', ['uses' => 'Adshield\SuspUserAgentController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//suspicious user agent graph
	Route::get('/{apikey}/suspUserAgentGraphs', ['uses' => 'Adshield\SuspUserAgentController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator data center list
	Route::get('/{apikey}/knownViolatorDataCenters', ['uses' => 'Adshield\KnownViolatorDataCenterController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator data center graph
	Route::get('/{apikey}/knownViolatorDataCenterGraphs', ['uses' => 'Adshield\KnownViolatorDataCenterController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//pages per minute exceeded list
	Route::get('/{apikey}/pagesPerMinuteExceeds', ['uses' => 'Adshield\PagesPerMinuteExceedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//pages per minute exceeded  graph
	Route::get('/{apikey}/pagesPerMinuteExceedGraphs', ['uses' => 'Adshield\PagesPerMinuteExceedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//pages per minute exceeded list
	Route::get('/{apikey}/pagesPerSessionExceeds', ['uses' => 'Adshield\PagesPerMinuteExceedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//pages per minute exceeded  graph
	Route::get('/{apikey}/pagesPerSessionExceedGraphs', ['uses' => 'Adshield\PagesPerMinuteExceedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//blocked country list
	Route::get('/{apikey}/blockedCountries', ['uses' => 'Adshield\BlockedCountryController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//blocked country graph
	Route::get('/{apikey}/blockedCountryGraphs', ['uses' => 'Adshield\BlockedCountryController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//aggregator user agent list
	Route::get('/{apikey}/aggregatorUserAgents', ['uses' => 'Adshield\AggregatorUserAgentController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//aggregator user agent graph
	Route::get('/{apikey}/aggregatorUserAgentGraphs', ['uses' => 'Adshield\AggregatorUserAgentController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator automation tool list
	Route::get('/{apikey}/knownViolatorAutoTools', ['uses' => 'Adshield\KnownViolatorToolController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator automation tool graph
	Route::get('/{apikey}/knownViolatorAutoToolGraphs', ['uses' => 'Adshield\KnownViolatorToolController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//protection summary overview graph
	Route::get('/{apikey}/protectionOverviewGraphs', ['uses' => 'Adshield\ProtectionOverviewController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//threats summary graph
	Route::get('/{apikey}/threatGraphs', ['uses' => 'Adshield\Threats\ThreatsController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//automated traffic threats
	Route::get('/{apikey}/automatedTraffics', ['uses' => 'Adshield\Threats\ThreatsController@getAutomatedTraffic'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//traffic by organization list
	Route::get('/{apikey}/trafficByOrgs', ['uses' => 'Adshield\Threats\ThreatsController@getTrafficByOrganization'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//susipicious countries
	Route::get('/{apikey}/suspiciousCountries', ['uses' => 'Adshield\Threats\ThreatsController@getSuspiciousCountries'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');


	//Summary Reports
	
	//click fraud
	Route::get('/{apikey}/clickFraudReports', ['uses' => 'Adshield\SummaryReports\ClickFraudController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//captcha requests
	Route::get('/{apikey}/captchaRequests', ['uses' => 'Adshield\SummaryReports\CaptchaRequestController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//desirable automated traffic
	Route::get('/{apikey}/desirableAutomatedTraffics', ['uses' => 'Adshield\SummaryReports\DesirableAutomatedTrafficController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//targeted content
	Route::get('/{apikey}/targetedContents', ['uses' => 'Adshield\SummaryReports\TargetedContentController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');


	//get stats
	Route::get('/{apikey}/{type?}', ['uses' => 'Adshield\VisualizerController@GetAdshieldStats'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->where('type', '[a-zA-Z_]+')
		->middleware('authapi');


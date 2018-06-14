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

// URL::forceScheme('https');


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


	
	//==========================================================================================

	//User authentication/login
	Route::post('login', ['uses' => 'Adshield\LoginController@login'])
		->middleware('api.access');

	//log out
	Route::any('logout', ['uses' => 'Adshield\LoginController@logout'])
		->middleware('api.access');


	//Protection Summary

	//ip violators list
	Route::get('/knownViolators', ['uses' => 'Adshield\IpViolatorListController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//ip violators graph
	Route::get('/ipViolatorGraphs', ['uses' => 'Adshield\IpViolatorListController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//ip js check failed list
	Route::get('/jsCheckFails', ['uses' => 'Adshield\IpJsCheckFailedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//ip js check failed graph
	Route::get('/jsCheckFailGraphs', ['uses' => 'Adshield\IpJsCheckFailedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//js not loaded list
	Route::get('/jsNotLoadeds', ['uses' => 'Adshield\JsNotLoadedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//js not loaded graph
	Route::get('/jsNotLoadedGraphs', ['uses' => 'Adshield\JsNotLoadedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator user agent list
	Route::get('/knownViolatorUserAgents', ['uses' => 'Adshield\KnownViolatorUserAgentController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator user agent graph
	Route::get('/knownViolatorUserAgentGraphs', ['uses' => 'Adshield\KnownViolatorUserAgentController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//browser integrity check list
	Route::get('/browserIntegrityChecks', ['uses' => 'Adshield\BrowserIntegrityCheckController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//browser integrity check graph
	Route::get('/browserIntegrityCheckGraphs', ['uses' => 'Adshield\BrowserIntegrityCheckController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//suspicious user agent list
	Route::get('/suspUserAgents', ['uses' => 'Adshield\SuspUserAgentController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//suspicious user agent graph
	Route::get('/suspUserAgentGraphs', ['uses' => 'Adshield\SuspUserAgentController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator data center list
	Route::get('/knownViolatorDataCenters', ['uses' => 'Adshield\KnownViolatorDataCenterController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator data center graph
	Route::get('/knownViolatorDataCenterGraphs', ['uses' => 'Adshield\KnownViolatorDataCenterController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//pages per minute exceeded list
	Route::get('/pagesPerMinuteExceeds', ['uses' => 'Adshield\PagesPerMinuteExceedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//pages per minute exceeded  graph
	Route::get('/pagesPerMinuteExceedGraphs', ['uses' => 'Adshield\PagesPerMinuteExceedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//pages per minute exceeded list
	Route::get('/pagesPerSessionExceeds', ['uses' => 'Adshield\PagesPerSessionExceedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//pages per minute exceeded  graph
	Route::get('/pagesPerSessionExceedGraphs', ['uses' => 'Adshield\PagesPerSessionExceedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//blocked country list
	Route::get('/blockedCountries', ['uses' => 'Adshield\BlockedCountryController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//blocked country graph
	Route::get('/blockedCountryGraphs', ['uses' => 'Adshield\BlockedCountryController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//aggregator user agent list
	Route::get('/aggregatorUserAgents', ['uses' => 'Adshield\AggregatorUserAgentController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//aggregator user agent graph
	Route::get('/aggregatorUserAgentGraphs', ['uses' => 'Adshield\AggregatorUserAgentController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator automation tool list
	Route::get('/knownViolatorAutoTools', ['uses' => 'Adshield\KnownViolatorToolController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//known violator automation tool graph
	Route::get('/knownViolatorAutoToolGraphs', ['uses' => 'Adshield\KnownViolatorToolController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//protection summary overview graph
	Route::get('/protectionOverviewGraphs', ['uses' => 'Adshield\ProtectionOverviewController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//threats summary graph
	Route::get('/threatGraphs', ['uses' => 'Adshield\Threats\ThreatsController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//automated traffic threats
	Route::get('/automatedTraffics', ['uses' => 'Adshield\Threats\ThreatsController@getAutomatedTraffic'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//traffic by organization list
	Route::get('/trafficByOrgs', ['uses' => 'Adshield\Threats\ThreatsController@getTrafficByOrganization'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//susipicious countries
	Route::get('/suspiciousCountries', ['uses' => 'Adshield\Threats\ThreatsController@getSuspiciousCountries'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//==========================================================================================


	//Summary Reports
	
	//click fraud
	Route::get('/clickFraudReports', ['uses' => 'Adshield\SummaryReports\ClickFraudController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//captcha requests
	Route::get('/captchaRequests', ['uses' => 'Adshield\SummaryReports\CaptchaRequestController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//desirable automated traffic
	Route::get('/desirableAutomatedTraffics', ['uses' => 'Adshield\SummaryReports\DesirableAutomatedTrafficController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//targeted content
	Route::get('/targetedContents', ['uses' => 'Adshield\SummaryReports\TargetedContentController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//==========================================================================================
	

	//SETTINGS

	//Settings Page
	Route::any('/contentProtections/{id?}', ['uses' => 'Adshield\Settings\ContentProtectionController@handleSettings'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');
	
	//custom pages
	Route::any('/customPages/{id?}', ['uses' => 'Adshield\Settings\CustomPagesController@handleSettings'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//ip access list
	Route::get('/ipaccesslists', ['uses' => 'Adshield\IpAccessListController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//country block list
	Route::any('/countryBlockLists/{id?}', ['uses' => 'Adshield\Settings\CountryBlockListController@handle'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//content distribution
	Route::any('/contentDistributions/{id?}', ['uses' => 'Adshield\Settings\ContentDistributionController@handle'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//account management
	Route::any('/accountManagements/{id?}', ['uses' => 'Adshield\Settings\AccountManagementController@handle'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');


	//==========================================================================================


	// COMPLETE LOG
	
	//log
	Route::any('/completeLogs/{id?}', ['uses' => 'Adshield\LogController@handle'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');
	//==========================================================================================


	// SUMMARY 
	
	//traffic summary
	Route::any('/trafficSummaries', ['uses' => 'Adshield\TrafficSummary\TrafficSummaryController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//cache analysis
	Route::any('/cacheAnalyses', ['uses' => 'Adshield\TrafficSummary\CacheAnalysisController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');

	//upstream http errors
	Route::any('/upstreamHttpErrors', ['uses' => 'Adshield\TrafficSummary\UpstreamHttpErrorsController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');


	//==========================================================================================


	//MISC. calls
	Route::get('/countries/{id?}', ['uses' => 'Adshield\Misc\CountryController@handle'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->middleware('authapi');
	//==========================================================================================


	//get stats
	Route::get('/{type?}', ['uses' => 'Adshield\VisualizerController@GetAdshieldStats'])
		->where('apikey', '[a-zA-Z0-9]{2,8}')
		->where('type', '[a-zA-Z_]+')
		->middleware('authapi');


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


if (\App::environment() !== 'local') {
	\URL::forceScheme('https');
}

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
 * violations api endpoint
 */
Route::any('v/{userKey?}', ['as' => 'CheckViolation', 'uses' => 'Adshield\Violations\ViolationCheckController@Check'])
	->where('userKey', '[a-zA-Z0-9]+')->middleware('api.access');

/**
 * violation : "JS Disabled" end point
 */
Route::get('nojs/{userKey?}', ['as' => 'LogNoJsViolation', 'uses' => 'Adshield\Violations\ViolationNoJsController@log'])
	->where('userKey', '[a-zA-Z0-9]+')->middleware('api.access');


/**
 * captcha handler
 */
Route::any('cap/{userKey?}/{act?}', ['as' => 'CaptchaReceiver', 'uses' => 'Adshield\Violations\CaptchaController@receive'])
	->where('userKey', '[a-zA-Z0-9]+')
	->where('act', '[a-z]{1,10}')
	->middleware('api.access');


/**
 * route for frontend
 */


	//==========================================================================================

	//User authentication/login
	Route::any('login', ['uses' => 'Adshield\LoginController@login'])
		->middleware('api.access');

	//verify token
	Route::get('tokens', ['uses' => 'Adshield\LoginController@verifyToken'])
		->middleware('api.access');

	//log out
	Route::any('logout', ['uses' => 'Adshield\LoginController@logout'])
		->middleware('api.access');



Route::middleware(['authapi'])->group(function () {


	//Protection Summary

	//ip violators list
	Route::get('/knownViolators', ['uses' => 'Adshield\Protection\IpViolatorListController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//ip violators graph
	Route::get('/ipViolatorGraphs', ['uses' => 'Adshield\Protection\IpViolatorListController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//ip js check failed list
	Route::get('/jsCheckFails', ['uses' => 'Adshield\Protection\IpJsCheckFailedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//ip js check failed graph
	Route::get('/jsCheckFailGraphs', ['uses' => 'Adshield\Protection\IpJsCheckFailedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//js not loaded list
	Route::get('/jsNotLoadeds', ['uses' => 'Adshield\Protection\JsNotLoadedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//js not loaded graph
	Route::get('/jsNotLoadedGraphs', ['uses' => 'Adshield\Protection\JsNotLoadedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//known violator user agent list
	Route::get('/knownViolatorUserAgents', ['uses' => 'Adshield\Protection\KnownViolatorUserAgentController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//known violator user agent graph
	Route::get('/knownViolatorUserAgentGraphs', ['uses' => 'Adshield\Protection\KnownViolatorUserAgentController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//browser integrity check list
	Route::get('/browserIntegrityChecks', ['uses' => 'Adshield\Protection\BrowserIntegrityCheckController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//browser integrity check graph
	Route::get('/browserIntegrityCheckGraphs', ['uses' => 'Adshield\Protection\BrowserIntegrityCheckController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//suspicious user agent list
	Route::get('/suspUserAgents', ['uses' => 'Adshield\Protection\SuspUserAgentController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//suspicious user agent graph
	Route::get('/suspUserAgentGraphs', ['uses' => 'Adshield\Protection\SuspUserAgentController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//known violator data center list
	Route::get('/knownViolatorDataCenters', ['uses' => 'Adshield\Protection\KnownViolatorDataCenterController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//known violator data center graph
	Route::get('/knownViolatorDataCenterGraphs', ['uses' => 'Adshield\Protection\KnownViolatorDataCenterController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//pages per minute exceeded list
	Route::get('/pagesPerMinuteExceeds', ['uses' => 'Adshield\Protection\PagesPerMinuteExceedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//pages per minute exceeded  graph
	Route::get('/pagesPerMinuteExceedGraphs', ['uses' => 'Adshield\Protection\PagesPerMinuteExceedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//pages per minute exceeded list
	Route::get('/pagesPerSessionExceeds', ['uses' => 'Adshield\Protection\PagesPerSessionExceedController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//pages per minute exceeded  graph
	Route::get('/pagesPerSessionExceedGraphs', ['uses' => 'Adshield\Protection\PagesPerSessionExceedController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//blocked country list
	Route::get('/blockedCountries', ['uses' => 'Adshield\Protection\BlockedCountryController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//blocked country graph
	Route::get('/blockedCountryGraphs', ['uses' => 'Adshield\Protection\BlockedCountryController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//aggregator user agent list
	Route::get('/aggregatorUserAgents', ['uses' => 'Adshield\Protection\AggregatorUserAgentController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//aggregator user agent graph
	Route::get('/aggregatorUserAgentGraphs', ['uses' => 'Adshield\Protection\AggregatorUserAgentController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//known violator automation tool list
	Route::get('/knownViolatorAutoTools', ['uses' => 'Adshield\Protection\KnownViolatorToolController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//known violator automation tool graph
	Route::get('/knownViolatorAutoToolGraphs', ['uses' => 'Adshield\Protection\KnownViolatorToolController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//protection summary overview graph
	Route::get('/protectionOverviewGraphs', ['uses' => 'Adshield\Protection\ProtectionOverviewController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');


	//THREATS SUMMARY =======================

	//threats summary graph
	Route::get('/threatGraphs', ['uses' => 'Adshield\Threats\ThreatsController@getGraphData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//automated traffic threats
	Route::get('/automatedTraffics', ['uses' => 'Adshield\Threats\ThreatsController@getAutomatedTraffic'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//traffic by organization list
	Route::get('/trafficByOrgs', ['uses' => 'Adshield\Threats\ThreatsController@getTrafficByOrganization'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//susipicious countries
	Route::get('/suspiciousCountries', ['uses' => 'Adshield\Threats\ThreatsController@getSuspiciousCountries'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//==========================================================================================


	//Summary Reports
	
	//click fraud
	Route::get('/clickFraudReports', ['uses' => 'Adshield\SummaryReports\ClickFraudController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//captcha requests
	Route::get('/captchaRequests', ['uses' => 'Adshield\SummaryReports\CaptchaRequestController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//desirable automated traffic
	Route::get('/desirableAutomatedTraffics', ['uses' => 'Adshield\SummaryReports\DesirableAutomatedTrafficController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//targeted content
	Route::get('/targetedContents', ['uses' => 'Adshield\SummaryReports\TargetedContentController@getData'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//==========================================================================================
	

	//SETTINGS

	//Settings Page
	Route::any('/contentProtections/{id?}', ['uses' => 'Adshield\Settings\ContentProtectionController@handleSettings'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');
	
	//custom pages
	Route::any('/customPages/{id?}', ['uses' => 'Adshield\Settings\CustomPagesController@handleSettings'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//ip access list
	Route::get('/ipaccesslists', ['uses' => 'Adshield\Settings\IpAccessListController@getList'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//country block list
	Route::any('/countryBlockLists/{id?}', ['uses' => 'Adshield\Settings\CountryBlockListController@handle']);

	//content distribution
	Route::any('/contentDistributions/{id?}', ['uses' => 'Adshield\Settings\ContentDistributionController@handle'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');

	//account management
	Route::any('/accountManagements/{id?}', ['uses' => 'Adshield\Settings\AccountManagementController@handle'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');


	//==========================================================================================


	// COMPLETE LOG
	
	//log
	Route::any('/completeLogs/{id?}', ['uses' => 'Adshield\LogController@handle'])
		->where('apikey', '[a-zA-Z0-9]{2,8}');
	//==========================================================================================


	// SUMMARY 
	
	//traffic summary
	Route::any('/trafficSummaries', ['uses' => 'Adshield\TrafficSummary\TrafficSummaryController@getData']);

	//cache analysis
	Route::any('/cacheAnalyses', ['uses' => 'Adshield\TrafficSummary\CacheAnalysisController@getData']);

	//upstream http errors
	Route::any('/upstreamHttpErrors', ['uses' => 'Adshield\TrafficSummary\UpstreamHttpErrorsController@getData']);


	//==========================================================================================


	//USER ACCOUNT and USER ASSETS

	Route::any('/users/{id?}', ['uses' => 'Adshield\Accounts\UserController@handle']);
	Route::any('/passwords/{id?}', ['uses' => 'Adshield\Accounts\PasswordController@handle']);
	Route::any('/userWebsites', ['uses' => 'Adshield\Settings\UserWebsitesController@handle']);

	//==========================================================================================


	//MISC. calls
	Route::get('/countries/{id?}', ['uses' => 'Adshield\Misc\CountryController@handle']);
	//==========================================================================================


	//get stats
	Route::get('/adshieldstats', ['uses' => 'Adshield\VisualizerController@GetAdshieldStats'])
		->where('type', '[a-zA-Z_]+');

});
<?php

namespace App\Http\Controllers\Adshield\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

use App\Http\Controllers\Adshield\LoginController;
use App\Model\User;
use App\Model\UserConfig;

use App\Http\Controllers\Adshield\LogController;


/**
 * aka Ad Protection Controller
 * handles all ad protection page data
 */
class ContentProtectionController extends Controller
{

	public function handleSettings(Request $request)
	{
        try {
            $token = $request->bearerToken();
            $userId = LoginController::getUserIdFromToken($token);
        } catch (Exception $e) {}

		if ($request->isMethod('get'))
		{
			$userKey = Input::get('userKey');
			return $this->getSettings($userKey);
		}
		else
		{
			return $this->saveSettings();
		}
	}

	private function getSettings($userKey)
	{
		$settings = UserConfig::where('userKey', $userKey)->first();

		$data = [
			"threatResponse" => [
				"requestsFromKnownViolators" => "captcha",
				"requestsFromKnownViolatorDataCenters" => "block",
				"jsCheckFailed" => "block",
				"knownViolatorUA" => "block",
				"suspiciousUA" => "block",
				"browserIntegrity" => "block",
				"pagesPerMinuteExceed" => "block",
				"pagesPerSessionExceed" => "block",
				"blockedCountry" => "block",
				"aggregatorUA" => "block",
				"knownViolatorAutoTool" => "block",
				"sessionLengthExceed" => "block",
				"badUA" => "block",
				"unclassifiedUA" => "block",
				"bot" => "block",
			],
			"referrersAndProxies" => [
				"blockReferrers" => "no",
				"blockAnonymousProxies" => "yes"
			],
			"machineLearning" => [],
			'sessions' => [
				'maxSessionLength' => 1800,
				'pagesPerSession' => 50,
				'sessionTimeout' => 1800,
				'pagesPerMinute' => 30
			]
		];
		if (!empty($settings) && !empty($settings->getConfigJson('contentProtection'))) 
		{
			$config = $settings->getConfigJson('contentProtection');
			if (!empty($config['threatResponse'])) $data['threatResponse'] = array_merge($data['threatResponse'], $config['threatResponse']);
			if (!empty($config['referrersAndProxies'])) $data['referrersAndProxies'] = array_merge($data['referrersAndProxies'], $config['referrersAndProxies']);
			if (!empty($config['sessions'])) $data['sessions'] = array_merge($data['sessions'], $config['sessions']);
		}

		return response()->json(['id'=>1, 'pageData' => $data]);

	}

	private function saveSettings()
	{
		$settings = Input::get('contentProtection', []);
		$userKey = $settings['pageData']['userKey'];
		$config = UserConfig::where('userKey', $userKey)->first();

		if (empty($config)) {
			$config = new UserConfig();
			$config->userKey = $userKey;
		}
		$config->updatedOn = gmdate("Y-m-d H:i:s");
		$value = json_decode($config->config, 1);
		unset($settings['pageData']['userKey']);
		$value['contentProtection'] = $settings['pageData'];
		$config->config = json_encode($value);
		$config->save();

		LogController::QuickLog(LogController::ACT_SAVE_SETTINGS, [
			'title' => 'Ad Protection'
		]);
		
		//only one record in the database for this
		return response()->json(['id'=>1, 'pageData' => $settings]);
	}

}

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
			return $this->getSettings();
		}
		else
		{
			return $this->saveSettings();
		}
	}

	private function getSettings()
	{
		$userKey = Input::get('userKey');
		$settings = UserConfig::where('userKey', $userKey)->first();

		$data = [
			"threatResponse" => [
				"requestsFromUnknownViolators" => "captcha",
				"requestsFromKnownViolatorDataCenters" => "block"
			],
			"referrersAndProxies" => [
				"blockReferrers" => "no",
				"blockAnonymousProxies" => "yes"
			],
			"machineLearning" => []
		];
		if (!empty($settings)) $data = $settings->getConfigJson('contentProtection');

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
		
		//only one record in the database for this
		return response()->json(['id'=>1, 'pageData' => $settings]);
	}

}

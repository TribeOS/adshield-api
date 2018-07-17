<?php


namespace App\Http\Controllers\Adshield\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

use App\User;
use App\UserConfig;


class ContentProtectionController extends Controller
{

	public function handleSettings(Request $request)
	{
		$userId = 0;
        try {
            $token = $request->bearerToken();
            $userId = LoginController::getUserIdFromToken($token);
        } catch (Exception $e) {}

		if ($request->isMethod('get'))
		{
			return $this->getSettings($userId);
		}
		else
		{
			return $this->saveSettings($userId);
		}
	}

	private function getSettings()
	{
		// $settings = DB::table("settings")->where('name', 'contentProtection')->first();
		// $settings = json_decode($settings->settings);

		$data = [
			'threatResponse' => [
				'requestsFromUnknownViolators' => 'block', //block, captcha
				'requestsFromKnownViolatorDataCenters' => 'captcha', //block, captcha
			],
			'referrersAndProxies' => [
				'blockReferrers' => 'yes', //yes, no
				'blockAnonymousProxies' => 'no', //yes, no
			],
			'machineLearning' => []

		];

		return response()->json(['id'=>1, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');

	}

	private function saveSettings($userId)
	{
		$settings = Input::get('contentProtection', []);
		$config = UserConfig::where('userId', $userId)->first();

		if (empty($config)) {
			$config = new UserConfig();
			$config->userId = $userId;
		}
		$config->updatedOn = gmdate("Y-m-d H:i:s");
		$value = json_decode($config->config, 1);
		$value['contentProtection'] = $settings;
		$config->config = json_encode($value);
		$config->save();
		
		//only one record in the database for this
		return response()->json(['id'=>1, 'pageData' => $settings])
			->header('Content-Type', 'application/vnd.api+json')
			->header('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
	}

}

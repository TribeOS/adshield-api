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


class CustomPagesController extends Controller
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
			'captchaPages' => [
				'captchaPageUrl' => '',
				'captchaPageUrlEnabled' => false,
				'blockPageurl' => '',
				'blockPageurlEnabled' => false,
				'dropPageUrl' => '',
				'dropPageUrlEnabled' => false,
			],
			'validationIdentityPages' => [
				'jsValidationUrl' => '',
				'jsValidationUrlEnabled' => false,
				'unableToIdentifyUrl' => '',
				'unableToIdentifyUrlEnabled' => false,
			]
		];

		if (!empty($settings)) $data = $settings->getConfigJson('customPage');

		return response()->json(['id'=>1, 'pageData' => $data]);

	}

	private function saveSettings()
	{
		$settings = Input::get('customPage', []);
		$userKey = $settings['pageData']['userKey'];
		$config = UserConfig::where('userKey', $userKey)->first();

		if (empty($config)) {
			$config = new UserConfig();
			$config->userKey = $userKey;
		}
		$config->updatedOn = gmdate("Y-m-d H:i:s");
		$value = json_decode($config->config, 1);
		unset($settings['pageData']['userKey']);
		$value['customPage'] = $settings['pageData'];
		$config->config = json_encode($value);
		$config->save();

		return response()->json(['id'=>1, 'pageData' => $settings]);
	}

}

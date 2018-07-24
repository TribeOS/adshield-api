<?php

namespace App\Http\Controllers\Adshield\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

use App\Http\Controllers\Adshield\LoginController;
use App\User;
use App\UserConfig;


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
			return $this->getSettings($userId);
		}
		else
		{
			return $this->saveSettings($userId);
		}
	}

	private function getSettings($userId)
	{
		$settings = UserConfig::where('userId', $userId)->first();
		$config = $settings->getConfigJson('customPage');

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

		if (!empty($config)) $data = $config;

		return response()->json(['id'=>1, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');

	}

	private function saveSettings($userId)
	{
		$settings = Input::get('customPage', []);
		$config = UserConfig::where('userId', $userId)->first();

		if (empty($config)) {
			$config = new UserConfig();
			$config->userId = $userId;
		}
		$config->updatedOn = gmdate("Y-m-d H:i:s");
		$value = json_decode($config->config, 1);
		$value['customPage'] = $settings['pageData'];
		$config->config = json_encode($value);
		$config->save();

		return response()->json(['id'=>1, 'pageData' => $settings])
			->header('Content-Type', 'application/vnd.api+json')
			->header('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
	}

}

<?php

namespace App\Http\Controllers\Adshield\Settings;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;



class CustomPagesController extends BaseController
{

	public function handleSettings(Request $request)
	{
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
		// $settings = DB::table("settings")->where('name', 'customPages')->first();
		// $settings = json_decode($settings->settings);

		$data = [
			'captchaPages' => [
				'captchaPageUrl' => '/sys/captcha.html',
				'captchaPageUrlEnabled' => true,
				'blockPageurl' => '/sys/block.html',
				'blockPageurlEnabled' => false,
				'dropPageUrl' => '/sys/drop.html',
				'dropPageUrlEnabled' => false,
			],
			'validationIdentityPages' => [
				'jsValidationUrl' => '/sys/js_validate.html',
				'jsValidationUrlEnabled' => false,
				'unableToIdentifyUrl' => '/sys/unable_to_identify.html',
				'unableToIdentifyUrlEnabled' => true,
			]
		];

		return response()->json(['id'=>1, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');

	}

	private function saveSettings()
	{
		$settings = Input::get('customPage', []);
		//save settings to database here
		// DB::table("settings")
		// 	->update([
		// 		'settings' => json_encode($settings),
		// 		'updatedOn' => gmdate('Y-m-d H:i:s')
		// 	]);
		//only one record in the database for this
		return response()->json(['id'=>1, 'pageData' => $settings])
			->header('Content-Type', 'application/vnd.api+json')
			->header('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
	}

}

<?php

namespace App\Http\Controllers\Adshield\Settings;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;



class ContentDistributionController extends BaseController
{

	public function handle(Request $request)
	{
		if ($request->isMethod('get'))
		{
			return $this->getSettings();
		}
		else if ($request->isMethod('put'))
		{
			return $this->saveSettings();
		}
	}

	private function getSettings()
	{
		// $settings = DB::table("settings")->where('name', 'customPages')->first();
		// $settings = json_decode($settings->settings);

		$data = [
			'manageCache' => [
				'enableContentCache' => true,
				'enableUrlCache' => true,
				'enableMobileCache' => false,
			],
			'cacheExtension' => [
				'html' => true,
				'css' => false,
				'js' => false
			],
			'compressionReroute' => [
				'enableReroute' => false,
				'enableBypassCookie' => false
			],
			'customHeaders' => [
				'clientHeader' => 'X-Forwarder-For'
			]
		];

		return response()->json(['id'=>1, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');

	}

	private function saveSettings()
	{
		$settings = Input::get('contentDistribution', []);
		$settings = json_encode($settings['pageData']);
		print_r($settings);
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

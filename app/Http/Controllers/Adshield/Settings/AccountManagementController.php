<?php

namespace App\Http\Controllers\Adshield\Settings;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;



class AccountManagementController extends BaseController
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
			'users' => [
				['email' => 'aman@tr.be', 'accessLevel' => 'account access'],
				['email' => 'florin@tr.be', 'accessLevel' => 'account access'],
				['email' => 'is@tr.be', 'accessLevel' => 'account access'],
				['email' => 'jw@tr.be', 'accessLevel' => 'account access'],
			],
			'account' => [
				'company' => 'Tr.be',
				'address' => 'temporary address value'
			],
			'emailNotifications' => [
				['email' => 'is@tr.be', 'coverage' => 'all'],
				['email' => 'jw@tr.be', 'coverage' => 'all'],
			],
			'password' => [
			]
		];

		return response()->json(['id'=>1, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');

	}


	private function saveSettings()
	{
		$settings = Input::get('accountManagement', []);
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

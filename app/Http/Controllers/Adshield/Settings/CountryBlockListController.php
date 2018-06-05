<?php

namespace App\Http\Controllers\Adshield\Settings;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;



class CountryBlockListController extends BaseController
{

	public function handleSettings(Request $request)
	{
		if ($request->isMethod('get'))
		{
			return $this->getBlockedCountries();
		}
		else
		{
			return $this->addCountry();
		}
	}

	private function getBlockedCountries()
	{

		$data = [
			['id' => 1, 'name' => 'Philippines', 'code' => 'PH'],
		];

		return response()->json($data)
			->header('Content-Type', 'application/vnd.api+json');

	}

	private function addCountry()
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

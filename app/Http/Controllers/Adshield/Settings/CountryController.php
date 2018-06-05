<?php

namespace App\Http\Controllers\Adshield\Settings;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;



class CountryBlockListController extends BaseController
{

	private function getCountries()
	{

		$data = [
			['name' => 'Philippines', 'code' => 'PH'],
		];

		return response()->json(['id'=>1, 'list' => $data])
			->header('Content-Type', 'application/vnd.api+json');

	}
	
}

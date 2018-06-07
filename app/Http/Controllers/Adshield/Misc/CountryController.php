<?php

namespace App\Http\Controllers\Adshield\Misc;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

use App\Country;



class CountryController extends BaseController
{

	public function handle($id=null)
	{
		if ($request->isMethod('get'))
		{
			$filter = [];
			$code  = Input::get('code', []);
			if (!empty($code)) $filter['code'] = $code;
			$data = getCountries($filter);
			return response()->json($data)
				->header('Content-Type', 'application/vnd.api+json');
		}
	}

	private function getCountries($filter=[])
	{
		$data = Country::where($filter)->orderBy('countryName')->get();
		return $data;
	}
	
}

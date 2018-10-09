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

	public function handle(Request $request, $id=null)
	{
		if ($request->isMethod('get'))
		{
			if(empty($id)) {
				$data = $this->getCountries();
			} else {
				$data = Country::find($id);
			}
			return response()->json($data)
				->header('Content-Type', 'application/vnd.api+json');
		}
	}

	private function getCountries()
	{
		$filter = [];
		$name = Input::get('countryName', '');
		$userKey = Input::get('userKey', '');
		$data = DB::table("countries")
			->leftJoin("asBlockedCountries", function($join) use($userKey) {
				$join->on("asBlockedCountries.country", "=", "countries.id")
					->where('asBlockedCountries.userKey', '=', $userKey);
			})
			->select(DB::raw("countries.countryCode, countries.id, countries.countryName"))
			->whereNull("asBlockedCountries.country")
			->orderBy('countryName')->take(10);
		if (!empty($name)) $data->where('countryName', 'like', '%' . $name . '%');

		return $data->get();
	}
	
}

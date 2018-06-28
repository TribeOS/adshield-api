<?php

namespace App\Http\Controllers\Adshield\Settings;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

use App\Country;

class CountryBlockListController extends BaseController
{

	public function handle(Request $request, $id=null)
	{
		if ($request->isMethod('get'))
		{
			if (empty($id)) {
				$data = $this->getBlockedCountries();
			} else {
				$data = $this->getBlockedCountry($id);
			}
			return response()->json($data)
				->header('Content-Type', 'application/vnd.api+json');
		}
		else if ($request->isMethod('post'))
		{
			return $this->addCountry();
		}
		else if ($request->isMethod('delete'))
		{
			return $this->remove($id);
		}
	}

	private function getBlockedCountry($id)
	{
		$data = DB::table("asBlockedCountries")->where("id", $id)->first();
		return $data;
	}

	private function getBlockedCountries()
	{
		$data = DB::table("asBlockedCountries")->get();
		return $data;
	}

	private function addCountry()
	{
		$id = Input::get("countryBlockList")['country'];
		//save settings to database here
		$insertId = DB::table("asBlockedCountries")
			->insertGetId([
				'country' => $id,
				'addedOn' => gmdate('Y-m-d H:i:s')
			]);

		$data = [
			'id' => $insertId,
			'addedOn' => gmdate("Y-m-d H:i:s"),
			'countryBlockList' => ['country' => $id]
		];
		return response()->json($data)
			->header('Content-Type', 'application/vnd.api+json')
			->header('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
	}

	private function remove($id)
	{
		DB::table("asBlockedCountries")->where("id", $id)->delete();
		return response()->json([])
			->header('Content-Type', 'application/vnd.api+json')
			->header('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
	}

}

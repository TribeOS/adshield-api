<?php

namespace App\Http\Controllers\Adshield\Settings;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Request;

use App\Model\Country;

use App\Http\Controllers\Adshield\LogController;


class CountryBlockListController extends BaseController
{

	public function handle($id=null)
	{
		if (Request::isMethod('get'))
		{
			if (empty($id)) {
				$userKey = Request::get('filter')['userKey'];
				$data = $this->getBlockedCountries($userKey);
				$data = ['id' => 0, 'listData' => $data];
			} else {
				$data = $this->getBlockedCountry($id);
			}
			return response()->json($data)
				->header('Content-Type', 'application/vnd.api+json');
		}
		else if (Request::isMethod('post'))
		{
			return $this->addCountry();
		}
		else if (Request::isMethod('delete'))
		{
			return $this->remove($id);
		}
	}

	private function getBlockedCountry($id)
	{
		$data = DB::table("asBlockedCountries")->where("id", $id)->first();
		return $data;
	}

	private function getBlockedCountries($userKey)
	{
		$limit = Request::get("limit", 10);
		$page = Request::get("page", 1);

		$data = DB::table("asBlockedCountries")
			->join("countries", "countries.id", "=", "asBlockedCountries.country")
			->selectRaw("asBlockedCountries.*, countryName, countryCode")
			->where('userKey', $userKey);
		$data = $data->paginate($limit);
		return $data;
	}

	private function addCountry()
	{
		$id = Input::get("countryBlockList")['country'];
		$userKey = Input::get("countryBlockList")['userKey'];
		//save settings to database here
		$insertId = DB::table("asBlockedCountries")
			->insertGetId([
				'country' => $id,
				'addedOn' => gmdate('Y-m-d H:i:s'),
				'userKey' => $userKey
			]);

		$data = [
			'id' => $insertId,
			'addedOn' => gmdate("Y-m-d H:i:s"),
			'countryBlockList' => ['country' => $id]
		];

		$country = Country::where("id", $id)->first();

		LogController::QuickLog(LogController::ACT_COUNTRY_ADD, [
			'country' => $country->countryName,
			'userKey' => $userKey
		]);

		return response()->json($data)
			->header('Content-Type', 'application/vnd.api+json')
			->header('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
	}

	private function remove($id)
	{
		// $country = Country::where("id", $id)->first();
		DB::table("asBlockedCountries")->where("id", $id)->delete();

		return response()->json([])
			->header('Content-Type', 'application/vnd.api+json')
			->header('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
	}

}

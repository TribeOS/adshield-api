<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;


class ApiController extends BaseController
{

	public function RequestFailed()
	{
		return view()->make('api.error');
	}

	/**
	 * get stats from adshield stat log (count of each status for the given period)
	 */
	public function GetAdshieldStats()
	{
		$dateFrom = Input::get("dateFrom", gmdate("Y-m-d 00:00:00", strtotime("today")));
		$dateTo = Input::get("dateTo", gmdate("Y-m-d H:i:s"));
		$userKey = Input::get("userKey", "");
		$stats = ApiStatController::GetStats($dateFrom, $dateTo, $userKey);

		return response()->json(['success'=>true, 'data'=>$stats]);
	}

	public function GetAdshieldTransactionSince()
	{
		$totalSince = ApiStatController::GetTotalTransactionsSince();
		return response()->json(['success'=>true, 'data' => $totalSince]);
	}

}

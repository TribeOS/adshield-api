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
		$requestedStat = Input::get("type", "stat");
		$result = null;
		$success = true;
		switch($requestedStat)
		{
			case 'stat':
				$result = $this->GetStats();
				break;
			case 'transactionSince':
				$result = $this->GetAdshieldTransactionSince();
				break;
			default:
				//none
				$success = false;
		}

		return response()->json(['success'=>$success, 'data' => $result]);
	}

	private function GetStats()
	{
		$dateFrom = Input::get("dateFrom", gmdate("Y-m-d 00:00:00", strtotime("today")));
		$dateTo = Input::get("dateTo", gmdate("Y-m-d H:i:s"));
		$userKey = Input::get("userKey", "");
		$stats = ApiStatController::GetStats($dateFrom, $dateTo, $userKey);

		return $stats;
	}

	public function GetAdshieldTransactionSince()
	{
		$timeAgo = Input::get("elapsed", "2 seconds ago");
		$totalSince = ApiStatController::GetTotalTransactionsSince($timeAgo);
		return $totalSince;
	}

}

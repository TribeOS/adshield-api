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
	public function GetAdshieldStats($apikey, $type='stat')
	{
		$requestedStat = $type;
		$result = null;
		$success = true;
		switch($requestedStat)
		{
			case 'adshieldstats':
				//data format is due to how ember handles data
				$result = [
					'adshieldstats' => [
						'id' => 0,
						'stat' => $this->GetAllStatsVisualizer(),
						'meta' => 'general data for stats.'
					]
				];
				break;
			default:
				//none
				$success = false;
		}

		return response()->json($result)
			->header('Content-Type', 'application/vnd.api+json');
	}

	private function GetAllStatsVisualizer()
	{
		$data = [];
		$data['stat'] = $this->GetStats(true);
		$data['transactions'] = $this->GetAdshieldTransactionSince();
		return $data;
	}

	private function GetStats($fromBeginning=false)
	{
		if ($fromBeginning)
		{
			$dateFrom = null;
		}
		else
		{
			$dateFrom = Input::get("dateFrom", gmdate("Y-m-d 00:00:00", strtotime("today")));
		}

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

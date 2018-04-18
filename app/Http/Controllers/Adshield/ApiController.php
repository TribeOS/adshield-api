<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

date_default_timezone_set("America/New_York");


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
		$userKey = Input::get('userKey', null);
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
						'stat' => $this->GetAllStatsVisualizer($userKey),
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

	private function GetAllStatsVisualizer($userKey=null)
	{
		$data = [];
		$data['stat'] = $this->GetStats($userKey, true);

		$data['transactions'] = [
			'today' => $this->GetAdshieldTransactionSince($userKey, gmdate("Y-m-d H:i:s", strtotime('midnight today'))),
			'week' => $this->GetAdshieldTransactionSince($userKey, gmdate("Y-m-d H:i:s", strtotime('midnight this week'))),
			'month' => $this->GetAdshieldTransactionSince($userKey, gmdate("Y-m-1 H:i:s", strtotime('midnight this month'))),
		];

		$data['transactionsInterval'] = $this->GetAdshieldTransactionForPastTime($userKey);
		$data['adClicks'] = [
			'today' => $this->GetTotalAdClicks($userKey, gmdate("Y-m-d H:i:s", strtotime('midnight today'))),
			'week' => $this->GetTotalAdClicks($userKey, gmdate("Y-m-d H:i:s", strtotime('midnight this week'))),
			'month' => $this->GetTotalAdClicks($userKey, gmdate("Y-m-1 H:i:s", strtotime('midnight this month')))
		];
		return $data;
	}

	private function GetStats($userKey, $fromBeginning=false)
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
		$stats = ApiStatController::GetStats($userKey, $dateFrom, $dateTo);
		return $stats;
	}

	private function GetAdshieldTransactionSince($userKey, $dateFrom)
	{
		$totalSince = ApiStatController::GetTotalTransactionsSince($userKey, $dateFrom);
		return $totalSince;
	}

	/**
	 * gets the transactions count for each $interval seconds on a $steps steps
	 * use to populate chartJS graph
	 */
	public function GetAdshieldTransactionForPastTime($userKey, $interval=2, $steps=7)
	{
		// $timeSince = ($interval * $steps) . ' seconds ago';
		$timeSince = $interval . ' seconds ago';
		$total = ApiStatController::GetTotalTransactionsSince($userKey, $timeSince);

		// $temporaryTransactions = [];
		// foreach($transactions as $transaction) $temporaryTransactions[] = $transaction;
		// $transactions = $temporaryTransactions;

		// $count = 0;
		// $data = [];
		// date_default_timezone_set("UTC");
		// $time = strtotime($timeSince);
		// for($a=0; $a<$steps; $a++)
		// {
		// 	$total = 0;
		// 	$time += $interval;
		// 	if (isset($transactions[$count]))
		// 	{
		// 		$currentTime = strtotime($transactions[$count]->dOn);
		// 		if ($currentTime < $time)
		// 		{
		// 			$total = $transactions[$count]->total;
		// 			$count ++;
		// 		}
		// 	}
		// 	$data[] = $total;
		// }

		return $total;
	}

	private function GetTotalAdClicks($userKey, $dateFrom)
	{
		$clicks = AdshieldStatController::GetAdClicks($userKey, $dateFrom);
		return $clicks;
	}

}

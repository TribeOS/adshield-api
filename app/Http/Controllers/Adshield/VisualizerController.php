<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use App\Events\AdShieldUpdated;

use App\Model\UserWebsite;

date_default_timezone_set("America/New_York");


class VisualizerController extends BaseController
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
		$userKey = Input::get('userKey', null);
		$result = null;
		
		//data format is due to how ember handles data
		$result = [
			'adshieldstats' => [
				'id' => 0,
				'stat' => $this->GetAllStatsVisualizer($userKey, gmdate("Y-m-d H:i:s")),
				'meta' => 'general data for stats.'
			]
		];
	
		return response()->json($result)
			->header('Content-Type', 'application/vnd.api+json');
	}

	public static function BroadcastStats($userKey, $time)
	{
		$self = new VisualizerController();
		$result = [
			'adshieldstats' => [
				'id' => 0,
				'stat' => $self->GetAllStatsVisualizer(null, $time),
				'meta' => 'general data for stats.'
			]
		];
		event(new AdShieldUpdated($result));
	}

	private function GetAllStatsVisualizer($userKey=null, $time)
	{
		$data = [];
		$data['stat'] = $this->GetStats($userKey,
			gmdate("Y-m-1 H:i:s", strtotime("midnight this month")),
			gmdate("Y-m-d H:i:s"));

		$data['transactions'] = [
			'today' => $this->GetAdshieldTransactionSince($userKey, gmdate("Y-m-d H:i:s", strtotime('midnight today'))),
			'week' => $this->GetAdshieldTransactionSince($userKey, gmdate("Y-m-d H:i:s", strtotime('midnight this week'))),
			'month' => $this->GetAdshieldTransactionSince($userKey, gmdate("Y-m-1 H:i:s", strtotime('midnight this month'))),
		];

		// $data['transactionsInterval'] =$this->GetAdshieldTransactionForPastTime($userKey, $time);

		$transactions = $this->GetAdshieldTransactionForPastTime($userKey, $time);
		$data['transactionsInterval'] = [
			'data' => $transactions['data'],
			'userKey' => $userKey,
			'accountId' => $transactions['accountId']
		];

		$data['adClicks'] = [
			'today' => $this->GetTotalAdClicks($userKey, gmdate("Y-m-d H:i:s", strtotime('midnight today'))),
			'week' => $this->GetTotalAdClicks($userKey, gmdate("Y-m-d H:i:s", strtotime('midnight this week'))),
			'month' => $this->GetTotalAdClicks($userKey, gmdate("Y-m-1 H:i:s", strtotime('midnight this month')))
		];
		return $data;
	}

	private function GetStats($userKey, $dateFrom, $dateTo)
	{
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
	public function GetAdshieldTransactionForPastTime($userKey, $time)
	{
		$accountId = UserWebsite::where('userKey', $userKey)->first();
		if (empty($accountId)) {
			$accountId = 0;
		} else {
			$accountId = $accountId->accountId;
		}

		$total = ApiStatController::GetLiveTransactions($userKey, $time);
		// return $total;
		return [
			'data' => $total,
			'accountId' => $accountId
		];
	}

	private function GetTotalAdClicks($userKey, $dateFrom)
	{
		$clicks = AdshieldStatController::GetAdClicks($userKey, $dateFrom);
		return $clicks;
	}

}

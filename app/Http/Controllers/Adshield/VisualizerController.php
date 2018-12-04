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
				'stat' => $self->GetAllStatsVisualizer($userKey, $time),
				'meta' => 'general data for stats.'
			]
		];
		event(new AdShieldUpdated($result));
	}

	private function GetAllStatsVisualizer($userKey=null, $time)
	{
		$accountId = UserWebsite::where('userKey', $userKey)->first();
		if (empty($accountId)) {
			$accountId = 0;
		} else {
			$accountId = $accountId->accountId;
		}

		$data = ['userKey' => $userKey, 'accountId' => $accountId];
		$data['stat'] = $this->GetStats($accountId, $userKey,
			gmdate("Y-m-1 H:i:s", strtotime("midnight this month")),
			gmdate("Y-m-d H:i:s"));

		$data['transactions'] = [
			'today' => $this->GetAdshieldTransactionSince($accountId, $userKey, gmdate("Y-m-d H:i:s", strtotime('midnight today'))),
			'week' => $this->GetAdshieldTransactionSince($accountId, $userKey, gmdate("Y-m-d H:i:s", strtotime('midnight this week'))),
			'month' => $this->GetAdshieldTransactionSince($accountId, $userKey, gmdate("Y-m-1 H:i:s", strtotime('midnight this month'))),
		];

		$data['transactionsInterval'] =$this->GetAdshieldTransactionForPastTime($accountId, $userKey, $time);

		$data['adClicks'] = [
			'today' => $this->GetTotalAdClicks($accountId, $userKey, gmdate("Y-m-d H:i:s", strtotime('midnight today'))),
			'week' => $this->GetTotalAdClicks($accountId, $userKey, gmdate("Y-m-d H:i:s", strtotime('midnight this week'))),
			'month' => $this->GetTotalAdClicks($accountId, $userKey, gmdate("Y-m-1 H:i:s", strtotime('midnight this month')))
		];
		return $data;
	}

	private function GetStats($accountId, $userKey, $dateFrom, $dateTo)
	{
		$stats = ApiStatController::GetStats($accountId, $userKey, $dateFrom, $dateTo);
		return $stats;
	}

	private function GetAdshieldTransactionSince($accountId, $userKey, $dateFrom)
	{
		$totalSince = ApiStatController::GetTotalTransactionsSince($accountId, $userKey, $dateFrom);
		return $totalSince;
	}

	/**
	 * gets the transactions count for each $interval seconds on a $steps steps
	 * use to populate chartJS graph
	 */
	public function GetAdshieldTransactionForPastTime($accountId, $userKey, $time)
	{
		$total = ApiStatController::GetLiveTransactions($accountId, $userKey, $time);
		return $total;
	}

	private function GetTotalAdClicks($accountId, $userKey, $dateFrom)
	{
		$clicks = AdshieldStatController::GetAdClicks($accountId, $userKey, $dateFrom);
		return $clicks;
	}

}

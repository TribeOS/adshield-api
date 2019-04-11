<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use App\Events\AdShieldUpdated;
use Illuminate\Http\Request;

use App\Model\UserWebsite;


class VisualizerController extends BaseController
{

	public function RequestFailed()
	{
		return view()->make('api.error');
	}

	/**
	 * get stats from adshield stat log (count of each status for the given period)
	 */
	public function GetAdshieldStats(Request $request)
	{
		try {
            $token = $request->bearerToken();
            $user = LoginController::getUserIdFromToken($token, true);
        } catch (Exception $e) {}

        $accountId = $user->accountId;
		$userKey = Input::get('userKey', null);
		$result = null;
		
		//data format is due to how ember handles data
		$result = [
			'adshieldstats' => [
				'id' => 0,
				'stat' => $this->GetAllStatsVisualizer($accountId, $userKey, gmdate("Y-m-d H:i:s"), true),
				'meta' => 'general data for stats.'
			]
		];
	
		return response()->json($result)
			->header('Content-Type', 'application/vnd.api+json');
	}


	/**
	 * @param String $userKey UserKey of the requested website
	 * @param Date/Time $time Timestamp of the event
	 * @param Boolean $justClick Indicate if this event occured due to ad click only.
	 */
	public static function BroadcastStats($userKey, $time, $justClick=false)
	{
		$account = UserWebsite::where('userKey', $userKey)->first();

		if (empty($account)) {
			$accountId = 0;
		} else {
			$accountId = $account->accountId;
		}
		$token = sha1($accountId); //we've sent this value to the user upon login/token validation

		$self = new VisualizerController();
		$result = [
			'adshieldstats' => [
				'id' => 0,
				'stat' => $self->GetAllStatsVisualizer($accountId, $userKey, $time, false, $justClick),
				'meta' => 'general data for stats.',
				'token' => $token
			]
		];
		event(new AdShieldUpdated($result));
	}

	private function GetAllStatsVisualizer($accountId, $userKey=null, $time, $initCall=false, $justClick=false)
	{

		if (!$initCall) return $this->getStatsSince($accountId, $userKey, $time, $justClick);

		$data = ['userKey' => $userKey, 'accountId' => $accountId];
		$data['stat'] = $this->GetStats($accountId, $userKey,
			gmdate("Y-m-1 H:i:s", strtotime("midnight this month")),
			gmdate("Y-m-d H:i:s"));

		$data['transactions'] = [
			'today' => $this->GetAdshieldTransactionSince($accountId, $userKey, gmdate("Y-m-d 00:00:00", strtotime('midnight today'))),
			'week' => $this->GetAdshieldTransactionSince($accountId, $userKey, gmdate("Y-m-d 00:00:00", strtotime('midnight this week'))),
			'month' => $this->GetAdshieldTransactionSince($accountId, $userKey, gmdate("Y-m-1 00:00:00", strtotime("this month"))),
		];

		//use transactions.month as "directAccess value"
		$data['stat'][4]['count'] = $data['transactions']['month'];
		for($a = 0; $a < count($data['stat']) - 2; $a ++)
		{
			$data['stat'][4]['count'] -= $data['stat'][$a]['count'];
		}

		//we can use just the value 1 since for every hit/request we will be sending the stat update to the frontend.
		$data['transactionsInterval'] = 1; //$this->GetAdshieldTransactionForPastTime($accountId, $userKey, $time);
		if ($initCall)
		{
			$data['transactionsInterval'] = 0; 
		}

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

	/**
	 * gets all the hits we have for the past 2 minutes on 2 second interval
	 */
	private function GetPreviousTicks($userKey)
	{
		$stats = AdshieldStatController::GetPreviousTicks($userKey);
		return $stats;
	}



	/**
	 * fetch all live stats
	 */
	private function getStatsSince($accountId, $userKey, $time, $justClick = false)
	{
		$data = ['userKey' => $userKey, 'accountId' => $accountId];
		$dateTime = $time;

		///only click happened. no other transactions/requests
		if ($justClick)
		{
			//just pass 0 value data structure so frontend won't break
			$data['stat'] = [
				['status' => 0, 'title' => 'Unsafe', 'count' => 0], //unsafe
				['status' => 1, 'title' => 'Safe', 'count' => 0], //safe
				['status' => 5, 'title' => 'IFramed', 'count' => 0],	//iframe
				['status' => 6, 'title' => 'Bot', 'count' => 0],	//bot
				['status' => 7, 'title' => 'Direct Access', 'count' => 0]  //direct access (no referrer)
			];
			$click = $this->GetTotalAdClicks($accountId, $userKey, $dateTime);
			$data['transactionsInterval'] = 0;
			$data['transactions'] = [
				'today' => 0, 'week' => 0, 'month' => 0
			];
		}
		else
		{
			///a request has been made
			$data['stat'] = $this->GetStats($accountId, $userKey, $dateTime, $dateTime);
			$data['stat'][4]['count'] = 1;
			for($a = 0; $a < count($data['stat']) - 2; $a ++)
			{
				$data['stat'][4]['count'] -= $data['stat'][$a]['count'];
				if ($data['stat'][4]['count'] < 0) $data['stat'][4]['count'] = 0;
			}

			$data['transactions'] = [
				'today' => 1, 'week' => 1, 'month' => 1
			];
			$data['transactionsInterval'] = 1; //$this->GetAdshieldTransactionForPastTime($accountId, $userKey, 

			$click = 0;
		}

		$data['adClicks'] = [
			'today' => $click, 'week' => $click, 'month' => $click
		];

		return $data;
	}

}

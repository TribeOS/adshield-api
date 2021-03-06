<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Request;
use Config;

use App\Http\Controllers\Adshield\Protection\DummyDataController;
use App\Http\Controllers\Adshield\LogController;
use App\Http\Controllers\Adshield\Violations\ResponseController;

use App\Model\UserWebsite;
use App\Http\Controllers\Adshield\Settings\UserWebsitesController;


class BlockedRequestController extends BaseController
{


	public function getData()
	{
		$filter = Request::get("filter", []);

		$data = [
			'totalTrafficVsBlocked' => $this->getTotalTrafficVsBlocked($filter),
			'blockedRequests' => $this->getBlockedRequests($filter),
			'listData' => $this->getListData($filter)
		];

		LogController::QuickLog(LogController::ACT_VIEW_REPORT, [
			'title' => 'Blocked Requests',
			'userKey' => $filter['userKey']
		]);

		return response()->json(['id'=>0, 'pageData' => $data]);
	}


	/**
	 * gets the total number of requests/traffic for the given period and website
	 * together with the total number of captcha served/shown (logged)
	 * @param  [type] $days [description]
	 * @return [type]       [description]
	 */
	private function getTotalTrafficVsBlocked($filter)
	{
		//get total trafic
		$traffic = DB::table("trViolationLog")
			->join("trViolationSession", function($join) use($filter) {
				$join->on("trViolationSession.id", "=", "trViolationLog.sessionId");
				if ($filter['userKey'] !== 'all') $join->where("trViolationSession.userKey", $filter['userKey']);

				if (!empty($filter['duration']) && $filter['duration'] > 0)
				{
					$duration = $filter['duration'];
					$join->where("trViolationLog.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
				}
			});

		if ($filter['userKey'] == 'all') {
			$traffic->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolationSession.userKey')
					->where("userWebsites.status", UserWebsitesController::ST_ACTIVE)
					->where('userWebsites.accountId', Config::get('user')->accountId);
			});
		}

		$traffic = $traffic->count();

		//get total blocked served

		$blocked = DB::table('trViolations')
			->join('trViolationResponses', function($join) use($filter) {
				$join->on('trViolationResponses.violationId', '=', 'trViolations.id')
					->where('trViolationResponses.responseTaken', '=', ResponseController::RP_BLOCKED);
				if ($filter['userKey'] !== 'all') $join->where('userKey', '=', $filter['userKey']);

				if (!empty($filter['duration']) && $filter['duration'] > 0)
				{
					$duration = $filter['duration'];
					$join->where("trViolationResponses.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
				}
			});

		if ($filter['userKey'] == 'all') {
			$blocked->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolations.userKey')
					->where("userWebsites.status", UserWebsitesController::ST_ACTIVE)
					->where('userWebsites.accountId', Config::get('user')->accountId);
			});
		}
		$blocked = $blocked->count();

		$data = [
			'data' => [(int)$traffic, (int)$blocked],
			'label' => ['Total Traffic', 'Blocked Requests']
		];

		return $data;
	}


	/**
	 * gets total captcha and traffic count
	 * @param  [type] $filter [description]
	 * @return [type]         [description]
	 */
	private function getBlockedRequests($filter)
	{

		$records = [];
		$duration = $filter['duration'];

		//get traffic count
		$records['traffic'] = DB::table("trViolationLog")
			->join("trViolationSession", function($join) use($filter, $duration) {
				$join->on("trViolationSession.id", "=", "trViolationLog.sessionId");
				if ($filter['userKey'] !== 'all') $join->where("trViolationSession.userKey", $filter['userKey']);
				if ($duration > 0) $join->where("trViolationLog.createdOn", ">", gmdate("Y-m-d H:i:s", strtotime("$duration DAYS AGO")));
			});

		if ($filter['userKey'] == 'all') {
			$records['traffic']->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolationSession.userKey')
					->where("userWebsites.status", UserWebsitesController::ST_ACTIVE)
					->where('userWebsites.accountId', Config::get('user')->accountId);
			});
		}

		if ($duration > 0) {
			$records['traffic']->selectRaw("DATE(trViolationLog.createdOn) AS marker, COUNT(*) AS total");
		} else {
			$records['traffic']->selectRaw("YEAR(trViolationLog.createdOn) AS marker, COUNT(*) AS total");
		}
		$records['traffic'] = $records['traffic']->groupBy("marker")->get();

		//get blocked requests
		$records['blocked'] = DB::table('trViolations')
			->join('trViolationResponses', function($join) use($filter, $duration) {
				$join->on('trViolationResponses.violationId', '=', 'trViolations.id')
					->where('trViolationResponses.responseTaken', '=', ResponseController::RP_BLOCKED);
				if ($filter['userKey'] !== 'all') $join->where('userKey', '=', $filter['userKey']);
				if ($duration > 0) $join->where("trViolationResponses.createdOn", ">", gmdate("Y-m-d H:i:s", strtotime("$duration DAYS AGO")));
			});

		if ($filter['userKey'] == 'all') {
			$records['blocked']->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolations.userKey')
					->where("userWebsites.status", UserWebsitesController::ST_ACTIVE)
					->where('userWebsites.accountId', Config::get('user')->accountId);
			});
		}

		if ($duration > 0) {
			$records['blocked']->selectRaw("DATE(trViolations.createdOn) AS marker, COUNT(*) AS total");
		} else {
			$records['blocked']->selectRaw("YEAR(trViolations.createdOn) AS marker, COUNT(*) AS total");
		}
		$records['blocked'] = $records['blocked']->groupBy("marker")->get();


		$graph = [
			'datasets' => [],
			'label' => []
		];

		$label = [
			'traffic' => 'Traffic',
			'blocked' => 'Blocked Requests'
		];
		$defaultData = [];

		if ($filter['userKey'] !== 'all') {
			$site = UserWebsite::where("userKey", $filter['userKey'])->first();
		} else {
			$site = DB::table("userWebsites")
				->where("accountId", Config::get('user')->accountId)
				->selectRaw("MIN(createdOn) AS createdOn")
				->first();
		}

		if ($duration > 0) {
			for($a = 0; $a < $duration; $a ++) {
				$d = date("Y-m-d", strtotime(($duration - $a) . " days ago"));
				$graph['label'][] = $d;
				$defaultData[$d] = 0;
			}
		} else {
			$start = date("Y", strtotime($site->createdOn));
			for($a = $start; $a <= date("Y"); $a ++) {
				$graph['label'][] = $a;
				$defaultData[$a] = 0;
			}
		}

		$previousName = '';
		foreach($records as $index=>$record)
		{
			
			$graph['datasets'][] = [
				'data' => $defaultData,
				'label' => $label[$index]
			];
			foreach($record as $rec)
			{
				$graph['datasets'][count($graph['datasets']) - 1]['data'][$rec->marker] = (int)$rec->total;
			}
			$graph['datasets'][count($graph['datasets']) - 1]['data'] = array_values($graph['datasets'][count($graph['datasets']) - 1]['data']);
		}

		return $graph;
	}


	private function getListData($filter)
	{
		//IMPT: probably need a list of good bots or desirable bot names / traffic names here

		$limit = Request::get('limit', 10);
		$page = Request::get('page', 10);

		$data = DB::table('trViolations')
			->join('trViolationResponses', function($join) use($filter) {
				$join->on('trViolationResponses.violationId', '=', 'trViolations.id')
					->where('responseTaken', ResponseController::RP_BLOCKED);
					
				if ($filter['userKey'] !== 'all') $join->where('userKey', '=', $filter['userKey']);
				if (!empty($filter['duration']) && $filter['duration'] > 0)
				{
					$duration = $filter['duration'];
					$join->where("trViolationResponses.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
				}
			})
			->join('trViolationIps', 'trViolationIps.id', '=', 'trViolations.ip')
			->selectRaw("ipStr AS ip, COUNT(*) AS noRequests, MAX(trViolationResponses.createdOn) AS createdOn")
			->groupBy('ipStr')
			->orderBy('ipStr');

		if ($filter['userKey'] == 'all') {
			$data->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolations.userKey')
					->where("userWebsites.status", UserWebsitesController::ST_ACTIVE)
					->where('userWebsites.accountId', Config::get('user')->accountId);
			});
		}

		$data = $data->paginate($limit);

		LogController::QuickLog(LogController::ACT_VIEW_REPORT, [
			'title' => 'Blocked Requests',
			'userKey' => $filter['userKey'],
			'page' => $page
		]);

		return $data;

	}
	
}

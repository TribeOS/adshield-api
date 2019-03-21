<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Request;
use Config;

use App\Http\Controllers\Adshield\Protection\DummyDataController;
use App\Http\Controllers\Adshield\LogController;

use App\Model\UserWebsite;
use App\Http\Controllers\Adshield\Settings\UserWebsitesController;



class CaptchaRequestController extends BaseController
{


	public function getData()
	{
		$filter = Request::get("filter", []);

		$data = [
			'totalTrafficVsCaptcha' => $this->getTotalTrafficVsCaptcha($filter),
			'attemptsSolvedVsFailed' => $this->getAttemptsSolvedVsFailed($filter),
			'captchaRequests' => $this->getCaptchaRequests($filter)
		];

		LogController::QuickLog(LogController::ACT_VIEW_REPORT, [
			'title' => 'Captcha Requests',
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
	private function getTotalTrafficVsCaptcha($filter)
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

		//get total captcha served

		$captcha = DB::table('trViolations')
			->join('trViolationResponses', function($join) use($filter) {
				$join->on('trViolationResponses.violationId', '=', 'trViolations.id')
					->where('trViolationResponses.responseTaken', '=', 'CAPTCHA');
				if ($filter['userKey'] !== 'all') $join->where('userKey', '=', $filter['userKey']);

				if (!empty($filter['duration']) && $filter['duration'] > 0)
				{
					$duration = $filter['duration'];
					$join->where("trViolationResponses.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
				}
			});
		if ($filter['userKey'] == 'all') {
			$captcha->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolations.userKey')
					->where("userWebsites.status", UserWebsitesController::ST_ACTIVE)
					->where('userWebsites.accountId', Config::get('user')->accountId);
			});
		}
		$captcha = $captcha->count();


		$data = [
			'data' => [(int)$traffic, (int)$captcha],
			'label' => ['Total Traffic', 'CAPTCHA Served']
		];

		return $data;
	}

	private function getAttemptsSolvedVsFailed($filter)
	{
		$records = DB::table("trCaptchaLog")
			->join("trViolations", function($join) use($filter) {
				$join->on("trViolations.id", "=", "trCaptchaLog.violationId");
				if ($filter['userKey'] !== 'all') $join->where("trViolations.userKey", $filter['userKey']);
				if (!empty($filter['duration']) && $filter['duration'] > 0)
				{
					$duration = $filter['duration'];
					$join->where("trCaptchaLog.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
				}
			})
			->selectRaw("action, COUNT(*) AS total")
			->whereIn("action", ['SUCCESS', 'FAILED'])
			->groupBy("action");

		if ($filter['userKey'] == 'all') {
			$records->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolations.userKey')
					->where("userWebsites.status", UserWebsitesController::ST_ACTIVE)
					->where('userWebsites.accountId', Config::get('user')->accountId);
			});
		}
		$records = $records->get();


		$label = [
			'SUCCESS' => 'Solved',
			'FAILED' => 'Failed'
		];

		$data = [
			'data' => [],
			'label' => []
		];

		foreach($records as $d)
		{
			$data['data'][] = $d->total;
			$data['label'][] = $label[$d->action];
		}

		return $data;
	}


	/**
	 * gets total captcha and traffic count
	 * @param  [type] $filter [description]
	 * @return [type]         [description]
	 */
	private function getCaptchaRequests($filter)
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

		//get captcha served
		$records['captcha'] = DB::table('trViolations')
			->join('trViolationResponses', function($join) use($filter, $duration) {
				$join->on('trViolationResponses.violationId', '=', 'trViolations.id')
					->where('trViolationResponses.responseTaken', '=', 'CAPTCHA');
				if ($filter['userKey'] !== 'all') $join->where('userKey', '=', $filter['userKey']);
				if ($duration > 0) $join->where("trViolationResponses.createdOn", ">", gmdate("Y-m-d H:i:s", strtotime("$duration DAYS AGO")));
			});
		if ($records['captcha'] == 'all') {
			$traffic->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolations.userKey')
					->where("userWebsites.status", UserWebsitesController::ST_ACTIVE)
					->where('userWebsites.accountId', Config::get('user')->accountId);
			});
		}

		if ($duration > 0) {
			$records['captcha']->selectRaw("DATE(trViolations.createdOn) AS marker, COUNT(*) AS total");
		} else {
			$records['captcha']->selectRaw("YEAR(trViolations.createdOn) AS marker, COUNT(*) AS total");
		}
		$records['captcha'] = $records['captcha']->groupBy("marker")->get();


		$graph = [
			'datasets' => [],
			'label' => []
		];

		$label = [
			'traffic' => 'Traffic',
			'captcha' => 'Captcha Served'
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

	
}

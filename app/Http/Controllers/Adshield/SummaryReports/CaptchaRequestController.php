<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Request;

use App\Http\Controllers\Adshield\Protection\DummyDataController;
use App\Http\Controllers\Adshield\LogController;


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

		$data['totalTrafficVsCaptcha'] = DummyDataController::ApplyDuration($data['totalTrafficVsCaptcha']);
		$data['attemptsSolvedVsFailed'] = DummyDataController::ApplyDuration($data['attemptsSolvedVsFailed']);

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
				$join->on("trViolationSession.id", "=", "trViolationLog.sessionId")
					->where("trViolationSession.userKey", $filter['userKey']);

				if (!empty($filter['duration']) && $filter['duration'] > 0)
				{
					$duration = $filter['duration'];
					$join->where("trViolationLog.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
				}
			})
			->count();

		//get total captcha served

		$captcha = DB::table('trViolations')
			->join('trViolationResponses', function($join) use($filter) {
				$join->on('trViolationResponses.violationId', '=', 'trViolations.id')
					->where('userKey', '=', $filter['userKey'])
					->where('trViolationResponses.responseTaken', '=', 'CAPTCHA');

				if (!empty($filter['duration']) && $filter['duration'] > 0)
				{
					$duration = $filter['duration'];
					$join->where("trViolationResponses.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
				}
			})
			->count();


		$data = [
			'data' => [$traffic, $captcha],
			'label' => ['Total Traffic', 'CAPTCHA Served']
		];

		return $data;
	}

	private function getAttemptsSolvedVsFailed($filter)
	{
		$records = DB::table("trCaptchaLog")
			->join("trViolations", function($join) use($filter) {
				$join->on("trViolations.id", "=", "trCaptchaLog.violationId")
					->where("trViolations.userKey", $filter['userKey']);
				if (!empty($filter['duration']) && $filter['duration'] > 0)
				{
					$duration = $filter['duration'];
					$join->where("trCaptchaLog.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
				}
			})
			->selectRaw("action, COUNT(*) AS total")
			->whereIn("action", ['SUCCESS', 'FAILED'])
			->groupBy("action")
			->get();


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

	private function getCaptchaRequests($filter)
	{

		$records = [];

		//get traffic count
		$records['traffic'] = DB::table("trViolationLog")
			->join("trViolationSession", function($join) use($filter) {
				$join->on("trViolationSession.id", "=", "trViolationLog.sessionId")
					->where("trViolationSession.userKey", $filter['userKey'])
					->whereBetween("trViolationLog.createdOn", [gmdate("Y-01-01 00:00:00"), gmdate("Y-12-31 23:59:59", time())]);
			})
			->selectRaw("MONTH(trViolationLog.createdOn) AS month, COUNT(*) AS total")
			->groupBy("month")
			->get();

		//get captcha served
		$records['captcha'] = DB::table('trViolations')
			->join('trViolationResponses', function($join) use($filter) {
				$join->on('trViolationResponses.violationId', '=', 'trViolations.id')
					->where('userKey', '=', $filter['userKey'])
					->where('trViolationResponses.responseTaken', '=', 'CAPTCHA')
					->whereBetween("trViolationResponses.createdOn", [gmdate("Y-01-01 00:00:00"), gmdate("Y-12-31 23:59:59", time())]);
			})
			->selectRaw("MONTH(trViolations.createdOn) AS month, COUNT(*) AS total")
			->groupBy("month")
			->get();

		$graph = [
			'datasets' => [],
			'label' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
		];

		$label = [
			'traffic' => 'Traffic',
			'captcha' => 'Captcha Served'
		];

		$previousName = '';
		foreach($records as $index=>$record)
		{
			
			$graph['datasets'][] = [
				'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
				'label' => $label[$index]
			];

			foreach($record as $rec)
			{
				$graph['datasets'][count($graph['datasets']) - 1]['data'][$rec->month - 1] = $rec->total;
			}

		}

		return $graph;
	}

	
}

<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Request;
use Config;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use App\Http\Controllers\Adshield\Violations\ResponseController;
use App\Http\Controllers\Adshield\LogController;


class TargetedContentController extends BaseController
{


	public function getData()
	{
		$filter = Request::get("filter", []);
		$data = [
			'listData' => $this->getListData($filter),
			'responseCodesByTotalPercentage' => $this->getResponseCodesByTotalPercentage($filter),
		];

		return response()->json(['id'=>0, 'pageData' => $data]);
	}


	private function getListData($filter)
	{
		
		$page = Request::get("page", 0);
		$limit = Request::get("limit", 10);

		$data = DB::table("trViolationLog")
			->join("trViolationSession", function($join) use($filter) {
				$join->on("trViolationSession.id", "=", "trViolationLog.sessionId");
				if ($filter['userKey'] !== 'all') $join->where("trViolationSession.userKey", $filter['userKey']);
				
				if (!empty($filter['duration']) && $filter['duration'] > 0)
				{
					$duration = $filter['duration'];
					$join->where("trViolationLog.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
				}
			})
			->join("trViolations", function($join) use($filter) {
				$join->on("trViolations.ip", "=", "trViolationSession.ip")
					->on("trViolations.createdOn", "=", "trViolationLog.createdOn")
					->whereIn("trViolations.violation", [
						ViolationController::V_IS_BOT,
						ViolationController::V_BAD_UA,
						ViolationController::V_BROWSER_INTEGRITY,
						ViolationController::V_KNOWN_VIOLATOR_AUTO_TOOL,
					]);
				if ($filter['userKey'] !== 'all') $join->where("trViolations.userKey", $filter['userKey']);
			})
			->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolationSession.userKey')
					->where('userWebsites.accountId', Config::get('user')->accountId);
			})
			->selectRaw("trViolationLog.url AS path, COUNT(*) AS noRequests")
			->groupBy("trViolationLog.url")
			->orderBy("trViolationLog.url");

		$data = $data->paginate($limit);

		LogController::QuickLog(LogController::ACT_VIEW_REPORT, [
			'title' => 'Targeted Content',
			'userKey' => $filter['userKey'],
			'page' => $page
		]);

		return $data;
	}


	/**
	 * gets all the responses taken by the system for the given period
	 * @param  [type] $filter [description]
	 * @return [type]         [description]
	 */
	private function getResponseCodesByTotalPercentage($filter)
	{

		$responses = DB::table('trViolations')
			->join('trViolationResponses', function($join) use($filter) {
				$join->on('trViolationResponses.violationId', '=', 'trViolations.id');
				if ($filter['userKey'] !== 'all') $join->where('userKey', '=', $filter['userKey']);
				if ($filter['duration'] > 0) {
					$duration = $filter['duration'];
					$join->where("trViolationResponses.createdOn", ">", gmdate("Y-m-d H:i:s", strtotime("$duration days ago")));
				}
			})
			->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolations.userKey')
					->where('userWebsites.accountId', Config::get('user')->accountId);
			})
			->selectRaw("responseTaken, COUNT(*) AS total")
			->groupBy("responseTaken")
			->get();

		$data = ['data' => [], 'label' => []];

		$labels = [
			ResponseController::RP_BLOCKED => 'Blocked',
			ResponseController::RP_CAPTCHA => 'Captcha',
			ResponseController::RP_ALLOWED => 'Allowed'
		];
		
		foreach($responses as $response)
		{
			$data['data'][] = $response->total;
			$data['label'][] = $labels[$response->responseTaken];
		}

		return $data;
	}

	
}

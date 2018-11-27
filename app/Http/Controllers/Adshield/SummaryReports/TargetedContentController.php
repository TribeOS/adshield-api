<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Request;

use App\Http\Controllers\Adshield\Protection\DummyDataController;
use App\Http\Controllers\Adshield\Violations\ViolationController;


class TargetedContentController extends BaseController
{


	public function getData()
	{
		$filter = Request::get("filter", []);
		$data = [
			'listData' => $this->getListData($filter),
			'responseCodesByTotalPercentage' => $this->getResponseCodesByTotalPercentage(0),
		];

		$data['responseCodesByTotalPercentage'] = DummyDataController::ApplyDuration($data['responseCodesByTotalPercentage']);

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}


	private function getListData($filter)
	{
		function generateData($name, $noRequests) {
			return ['path' => $name, 'noRequests' => $noRequests];
		}
		
		$page = Request::get("page", 0);
		$limit = Request::get("limit", 10);

		$data = DB::table('trViolations')
			->join('trViolationSession', function($join) use($filter) {
				$join->on('trViolationSession.userKey', '=', 'trViolations.userKey')
					->on('trViolationSession.ip', '=', 'trViolations.ip')
					->where('trViolations.userKey', '=', $filter['userKey'])
					->whereIn('trViolations.violation', [
						ViolationController::V_UNCLASSIFIED_UA,
						ViolationController::V_BAD_UA,
						ViolationController::V_KNOWN_VIOLATOR_UA,
						ViolationController::V_AGGREGATOR_UA
					]);

					if (!empty($filter['duration']) && $filter['duration'] > 0)
					{
						$duration = $filter['duration'];
						$join->where("createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
					}
			})
			->join('trViolationLog', function($join) use($filter) {
				$join->on('trViolationLog.sessionId', '=', 'trViolationSession.id')
					->on('trViolationLog.infoId', '=', 'trViolations.violationInfo')
					->on('trViolationLog.createdOn', '=', 'trViolations.createdOn')
					->where('trViolations.userKey', '=', $filter['userKey'])
					->whereIn('trViolations.violation', [
						ViolationController::V_UNCLASSIFIED_UA,
						ViolationController::V_BAD_UA,
						ViolationController::V_KNOWN_VIOLATOR_UA,
						ViolationController::V_AGGREGATOR_UA
					]);

					if (!empty($filter['duration']) && $filter['duration'] > 0)
					{
						$duration = $filter['duration'];
						$join->where("createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
					}
			})
			->selectRaw("trViolationLog.url AS path, COUNT(*) AS noRequests")
			->groupBy('trViolationLog.url')
			->orderBy('trViolationLog.url', 'asc');


		$data = $data->paginate($limit);

		return $data;
	}


	private function getResponseCodesByTotalPercentage($days)
	{
		$data = [];
		$data['data'] = [19, 37, 5, 19];
		$data['label'] = ['Monitored', 'Captcha', 'Blocked', 'Dropped'];
		return $data;
	}

	
}

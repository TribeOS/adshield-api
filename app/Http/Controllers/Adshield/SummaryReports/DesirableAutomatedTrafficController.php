<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Request;

use App\Http\Controllers\Adshield\Protection\DummyDataController;
use App\Http\Controllers\Adshield\Violations\ViolationController;


class DesirableAutomatedTrafficController extends BaseController
{


	public function getData()
	{
		$filter = Request::get("filter", []);

		$data = [
			'listData' => $this->getListData($filter),
			'desirableAutomatedRequests' => $this->getDesirableAutomatedRequests($filter),
		];

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}


	private function getListData($filter)
	{
		//IMPT: probably need a list of good bots or desirable bot names / traffic names here

		$limit = Request::get('limit', 10);
		$page = Request::get('page', 10);

		$data = DB::table('trViolations')
			->join('trViolationAutoTraffic', function($join) use($filter) {
				$join->on('trViolationAutoTraffic.violationId', '=', 'trViolations.id')
					->where('userKey', '=', $filter['userKey'])
					->whereIn('violation', [
						ViolationController::V_UNCLASSIFIED_UA,
						ViolationController::V_BAD_UA,
						ViolationController::V_KNOWN_VIOLATOR_UA,
						ViolationController::V_AGGREGATOR_UA
					]);
					if (!empty($filter['duration']) && $filter['duration'] > 0)
					{
						$duration = $filter['duration'];
						$join->where("trViolations.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
					}
			})
			->selectRaw("trafficName, COUNT(*) AS noRequests")
			->groupBy('trafficName')
			->orderBy('trafficName');

		$data = $data->paginate($limit);
		return $data;

	}


	private function getDesirableAutomatedRequests($filter)
	{

		$limit = Request::get('limit', 10);
		$page = Request::get('page', 10);

		$data = DB::table('trViolations')
			->join('trViolationAutoTraffic', function($join) use($filter) {
				$join->on('trViolationAutoTraffic.violationId', '=', 'trViolations.id')
					->where('userKey', '=', $filter['userKey'])
					->whereIn('violation', [
						ViolationController::V_UNCLASSIFIED_UA,
						ViolationController::V_BAD_UA,
						ViolationController::V_KNOWN_VIOLATOR_UA,
						ViolationController::V_AGGREGATOR_UA
					])
					->whereBetween("trViolations.createdOn", [gmdate("Y-01-01 00:00:00"), gmdate("Y-12-31 23:59:59", time())]);
			})
			->selectRaw("trafficName, MONTH(trViolations.createdOn) AS month, COUNT(*) AS noRequests")
			->groupBy('trafficName', 'month')
			->get();

		$graph = [
			'datasets' => [],
			'label' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
		];


		$previousName = '';
		foreach($data as $record)
		{
			if ($record->trafficName !== $previousName)
			{
				$graph['datasets'][] = [
					'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
					'label' => $record->trafficName
				];
			}

			$graph['datasets'][count($graph['datasets']) - 1]['data'][$record->month] = $record->noRequests;
		}

		return $graph;

	}

	
}

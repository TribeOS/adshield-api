<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Request;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use App\Http\Controllers\Adshield\LogController;

use App\Model\UserWebsite;


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


		LogController::QuickLog(LogController::ACT_VIEW_REPORT, [
			'title' => 'Desirable Automated Traffic',
			'userKey' => $filter['userKey'],
			'page' => $page
		]);

		return $data;

	}


	private function getDesirableAutomatedRequests($filter)
	{

		$limit = Request::get('limit', 10);
		$page = Request::get('page', 10);
		$duration = $filter['duration'];

		$data = DB::table('trViolations')
			->join('trViolationAutoTraffic', function($join) use($filter, $duration) {
				$join->on('trViolationAutoTraffic.violationId', '=', 'trViolations.id')
					->where('userKey', '=', $filter['userKey'])
					->whereIn('violation', [
						ViolationController::V_UNCLASSIFIED_UA,
						ViolationController::V_AGGREGATOR_UA
					]);
				if ($duration > 0) $join->where("trViolations.createdOn", ">", gmdate("Y-m-d H:i:s", strtotime("$duration DAYS AGO")));
			});

		if ($duration > 0) {
			$data->selectRaw("trafficName, DATE(trViolations.createdOn) AS marker, COUNT(*) AS noRequests");
		} else {
			$data->selectRaw("trafficName, YEAR(trViolations.createdOn) AS marker, COUNT(*) AS noRequests");
		}
		$data = $data->groupBy("trafficName", "marker")
			->orderBy("trafficName")
			->get();

		$graph = [
			'datasets' => [],
			'label' => []
		];

		$defaultData = [];

		$site = UserWebsite::where("userKey", $filter['userKey'])->first();

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
		foreach($data as $record)
		{
			if ($record->trafficName !== $previousName)
			{
				try {
					$graph['datasets'][count($graph['datasets']) - 1]['data'] = array_values($graph['datasets'][count($graph['datasets']) - 1]['data']);
				} catch (\Exception $e) {}

				$graph['datasets'][] = [
					'data' => $defaultData,
					'label' => $record->trafficName
				];
				$previousName = $record->trafficName;
			}

			$graph['datasets'][count($graph['datasets']) - 1]['data'][$record->marker] = $record->noRequests;
		}
		try {
			$graph['datasets'][count($graph['datasets']) - 1]['data'] = array_values($graph['datasets'][count($graph['datasets']) - 1]['data']);
		} catch (\Exception $e) {}

		return $graph;

	}

	
}

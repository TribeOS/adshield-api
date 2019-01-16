<?php

namespace App\Http\Controllers\Adshield\TrafficSummary;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\Protection\DummyDataController;
use App\Model\UserWebsite;
use App\Http\Controllers\Adshield\Violations\ViolationController;

class TrafficSummaryController extends BaseController
{


	public function getData()
	{
		$days = Input::get('days', 60);
		$data = [
			'threatResponseProtocolsUsed' => DummyDataController::ApplyDuration($this->getThreatResponseProtocolsUsed($days)),
			'threatsAverted' => DummyDataController::ApplyDuration($this->getThreatsAverted($days)),
			'trafficGraph' => $this->getTrafficGraph($days)
		];

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}

	private function getThreatResponseProtocolsUsed($days)
	{
		$data = [
			'data' => [70, 45, 95, 64],
			'label' => ['captcha', 'blocked', 'dropped', 'monitored']
		];
		return $data;
	}

	private function getThreatsAverted($days)
	{
		$data = [
			'data' => [62, 74, 52, 40, 94],
			'label' => ['Known Violators', 'JavaScript Check Failed', 'JavaScript Not Loaded', 'Known Violator User Agent', 'Bad User Agent']
		];
		return $data;
	}

	private function getTrafficGraph($days)
	{
		$data = [];
		$data['datasets'][] = [
			'data' => [39, 48, 74, 70, 87, 34, 18],
			'label' => 'Humans'
		];
		$data['datasets'][] = [
			'data' => [46, 56, 65, 23, 34, 32, 13],
			'label' => 'Good Bots'
		];
		$data['label'] = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];

		foreach($data['datasets'] as $index=>$ds) {
			$data['datasets'][$index] = DummyDataController::ApplyDuration($data['datasets'][$index]);
		}

		return $data;
	}


	private function trafficGraph()
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
						ViolationController::V_BAD_UA,
						ViolationController::V_KNOWN_VIOLATOR_UA,
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

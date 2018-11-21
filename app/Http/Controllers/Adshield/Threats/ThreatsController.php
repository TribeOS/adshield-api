<?php

namespace App\Http\Controllers\Adshield\Threats;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\Protection\DummyDataController;

use App\Http\Controllers\Adshield\Violations\ViolationController;


class ThreatsController extends BaseController
{

	public function getGraphData()
	{
		$filter = Input::get("filter", []);

		$graphData = [
			'automatedTrafficClassification' => $this->getAutomatedTrafficClassification($filter),
			'topThreatsByCountry' => $this->getTopThreatsByCountry($filter),
			'threatsAverted' => $this->getThreatsAverted($filter)
		];
		return response()->json(['id'=>0, 'graphData' => $graphData])
			->header('Content-Type', 'application/vnd.api+json');
	}


	private function getAutomatedTrafficClassification($filter)
	{

		$labels = [
			ViolationController::V_UNCLASSIFIED_UA => 'Unclassified User Agent',
			ViolationController::V_BAD_UA => 'Bad User Agent',
			ViolationController::V_KNOWN_VIOLATOR_UA => 'Known Violator User Agent',
			ViolationController::V_AGGREGATOR_UA => 'Aggregator User Agent',
		];

		$data = DB::table('trViolations')
			->where('userKey', $filter['userKey'])
			->selectRaw("violation, COUNT(*) AS total")
			->groupBy('violation')
			->whereIn('violation', [
				ViolationController::V_UNCLASSIFIED_UA,
				ViolationController::V_BAD_UA,
				ViolationController::V_KNOWN_VIOLATOR_UA,
				ViolationController::V_AGGREGATOR_UA
			]);

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->get();
		$graphData = ['data' => [], 'label' => []];
		foreach($data as $d)
		{
			$graphData['data'][] = $d->total;
			$graphData['label'][] = $labels[$d->violation];
		}

		return $graphData;
	}

	private function getTopThreatsByCountry($filter)
	{
		$data = DB::table("trViolations")
			->join("trViolationInfo", function($join) use($filter) {
				$join->on("trViolations.violationInfo", "=", "trViolationInfo.id")
					->where("trViolations.userKey", $filter['userKey']);

			})
			->selectRaw("country, COUNT(*) AS total")
			->groupBy("country")
			->orderby("total", "DESC")
			->take(5);
	
		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->get();
		$graphData = ['data' => [], 'label' => []];
		foreach($data as $d)
		{
			$graphData['data'][] = $d->total;
			$graphData['label'][] = !empty($d->country) ? $d->country : "N/A" ;
		}

		return $graphData;
	}

	private function getThreatsAverted($filter)
	{
		//TEMPORARY
		//we're using the threats classification for now since we don't have data/logic for recording "threats averted" activity.
		
		$labels = [
			ViolationController::V_UNCLASSIFIED_UA => 'Unclassified User Agent',
			ViolationController::V_BAD_UA => 'Bad User Agent',
			ViolationController::V_KNOWN_VIOLATOR_UA => 'Known Violator User Agent',
			ViolationController::V_AGGREGATOR_UA => 'Aggregator User Agent',
		];

		$data = DB::table('trViolations')
			->where('userKey', $filter['userKey'])
			->selectRaw("violation, COUNT(*) AS total")
			->groupBy('violation')
			->whereIn('violation', [
				ViolationController::V_UNCLASSIFIED_UA,
				ViolationController::V_BAD_UA,
				ViolationController::V_KNOWN_VIOLATOR_UA,
				ViolationController::V_AGGREGATOR_UA
			]);

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->get();
		$graphData = ['data' => [], 'label' => []];
		foreach($data as $d)
		{
			$graphData['data'][] = $d->total;
			$graphData['label'][] = $labels[$d->violation];
		}

		return $graphData;
	}


	public function getAutomatedTraffic()
	{

		function createData($name, $class, $noRequests) {
			return ['name' => $name, 'classification' => $class, 'pageRequests' => $noRequests];
		}

		$filter = Input::get("filter", []);

		$data = [];

		$data['automatedTrafficList'] = [
			'total' => 5
		];
		$data['automatedTrafficList']['data'] = [
			createData('SEMRush', 'Bad User Agent',	DummyDataController::ApplyDuration(335)),
			createData('Reporting as Firefox', 'Unclassified User Agent', DummyDataController::ApplyDuration(495)),
			createData('Reporting as Chrome', 'Unclassified User Agent', DummyDataController::ApplyDuration(200)),
			createData('Reporting as Internet Explorer 9', 'Bad User Agent', DummyDataController::ApplyDuration(198)),
			createData('MJ12bot', 'Known Violator User Agent', DummyDataController::ApplyDuration(378)),
			createData('cURL', 'Known Violator User Agent', DummyDataController::ApplyDuration(282))
		];

		$data['botsByClassification'] = $this->getMostFrequentBots($filter);
		$data['mostFrequentBots'] = [
			'data' => [7, 99, 60, 10, 36],
			'label' => ['Uncategorized Bot', 'SEMRush', 'Reporting as Firefox', 'Reporting as Chrome', 'Reporting as Internets']
		];

		$data['botsByClassification'] = DummyDataController::ApplyDuration($data['botsByClassification']);
		$data['mostFrequentBots'] = DummyDataController::ApplyDuration($data['mostFrequentBots']);

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}

	private function getAutomatedTrafficList($filter)
	{

		$data = DB::table('trViolations')
			->join('trViolationAutoTraffic', function($join) {
				$join->on('trViolationAutoTraffic.violationId', '=', 'trViolations.id');
			})
			->where('userKey', $filter['userKey'])
			->selectRaw("violation, COUNT(*) AS total")
			->groupBy('violation', 'trafficName')
			->whereIn('violation', [
				ViolationController::V_UNCLASSIFIED_UA,
				ViolationController::V_BAD_UA,
				ViolationController::V_KNOWN_VIOLATOR_UA,
				ViolationController::V_AGGREGATOR_UA
			])
			->orderBy('trafficName');

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->get();
		$graphData = ['data' => [], 'label' => []];
		foreach($data as $d)
		{
			$graphData['data'][] = $d->total;
			$graphData['label'][] = $labels[$d->violation];
		}

		return $graphData;
	}

	private function getMostFrequentBots($filter)
	{
		$labels = [
			ViolationController::V_UNCLASSIFIED_UA => 'Unclassified User Agent', 
			ViolationController::V_BAD_UA => 'Bad User Agent', 
			ViolationController::V_KNOWN_VIOLATOR_UA => 'Known Violator User Agent', 
			ViolationController::V_AGGREGATOR_UA => 'Aggregator User Agent'
		];

		$data = DB::table('trViolations')
			->where('userKey', $filter['userKey'])
			->selectRaw("violation, COUNT(*) AS total")
			->groupBy('violation')
			->whereIn('violation', [
				ViolationController::V_UNCLASSIFIED_UA,
				ViolationController::V_BAD_UA,
				ViolationController::V_KNOWN_VIOLATOR_UA,
				ViolationController::V_AGGREGATOR_UA
			]);

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->get();
		$graphData = ['data' => [], 'label' => []];
		foreach($data as $d)
		{
			$graphData['data'][] = $d->total;
			$graphData['label'][] = $labels[$d->violation];
		}

		return $graphData;
	}


	public function getTrafficByOrganization()
	{
		function generateData($org, $total, $topCountry) {
			return ['organization' => $org, 'total' => $total, 'country' => $topCountry];
		}
		$data = [];
		$data['total'] = 5;
		$data['data'] = [
			generateData('OVH SAS', DummyDataController::ApplyDuration(10643), 'France'),
			generateData('Advanced Hosters B.V.', DummyDataController::ApplyDuration(5602), 'Netherlands'),
			generateData('Zscaler', DummyDataController::ApplyDuration(3326), 'United States'),
			generateData('Amazon.com', DummyDataController::ApplyDuration(1922), 'United States'),
			generateData('China Telecom Guandong', DummyDataController::ApplyDuration(1754), 'China'),
		];

		return response()->json(['id'=>0, 'listData' => $data])
			->header('Content-Type', 'application/vnd.api+json');		
	}


	public function getSuspiciousCountries()
	{
		function generateData($country, $noRequests) {
			return ['country' => $country, 'noRequests' => $noRequests];
		}
		$data = [
			generateData('United States', DummyDataController::ApplyDuration(4983)),
			generateData('France', DummyDataController::ApplyDuration(1342)),
			generateData('China', DummyDataController::ApplyDuration(8732)),
			generateData('Germany', DummyDataController::ApplyDuration(4723)),
			generateData('Australia', DummyDataController::ApplyDuration(3417)),
			generateData('Mexico', DummyDataController::ApplyDuration(1832))
		];

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');			
	}

	
}

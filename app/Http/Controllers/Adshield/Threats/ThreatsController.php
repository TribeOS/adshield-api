<?php

namespace App\Http\Controllers\Adshield\Threats;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use DB;
use Request;

use App\Http\Controllers\Adshield\Protection\DummyDataController;

use App\Http\Controllers\Adshield\Violations\ViolationController;


class ThreatsController extends BaseController
{

	const labels = [
			ViolationController::V_UNCLASSIFIED_UA => 'Unclassified User Agent', 
			ViolationController::V_BAD_UA => 'Bad User Agent', 
			ViolationController::V_KNOWN_VIOLATOR_UA => 'Known Violator User Agent', 
			ViolationController::V_AGGREGATOR_UA => 'Aggregator User Agent'
		];

	public function getGraphData()
	{
		$filter = Request::get("filter", []);

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

		$filter = Request::get("filter", []);

		$data = [];

		$data['automatedTrafficList'] = [
			'total' => 5
		];
		$data['automatedTrafficList'] = $this->getAutomatedTrafficList($filter);

		$data['botsByClassification'] = $this->getMostFrequentBotsClassification($filter);
		$data['mostFrequentBots'] = $this->getMostFrequentBots($filter);

		$data['botsByClassification'] = DummyDataController::ApplyDuration($data['botsByClassification']);
		$data['mostFrequentBots'] = DummyDataController::ApplyDuration($data['mostFrequentBots']);

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}


	/**
	 * get automated traffic classification and listing
	 */
	private function getAutomatedTrafficList($filter)
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
					]);
			})
			->selectRaw("trafficName AS name, violation AS classification, COUNT(*) AS pageRequests")
			->groupBy('violation', 'trafficName')
			->orderBy('trafficName');

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("trViolations.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->paginate($limit);

		return $data;
	}


	/**
	 * get the traffic considered as bots grouped into violation classification 
	 * (for user agents only or what the system considers automated traffic)
	 */
	private function getMostFrequentBotsClassification($filter)
	{

		$data = DB::table('trViolations')
			->where('userKey', $filter['userKey'])
			->selectRaw("violation, COUNT(*) AS total")
			->groupBy('violation')
			->whereIn('violation', [
				ViolationController::V_UNCLASSIFIED_UA,
				ViolationController::V_BAD_UA,
				ViolationController::V_KNOWN_VIOLATOR_UA,
				ViolationController::V_AGGREGATOR_UA
			])
			->orderBy("total", "desc");

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
			$graphData['label'][] = self::labels[$d->violation];
		}

		return $graphData;
	}


	/**
	 * get the traffic considered as bots grouped into violation classification 
	 * (for user agents only or what the system considers automated traffic)
	 */
	private function getMostFrequentBots($filter)
	{

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
			})
			->selectRaw("trafficName, COUNT(*) AS total")
			->groupBy("trafficName")
			->orderBy("total", "desc");

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
			$graphData['label'][] = $d->trafficName;
		}

		return $graphData;
	}


	/**
	 * get violations/threats by internet organization.
	 * Org data is based on the info fetched for the origin IP
	 */
	public function getTrafficByOrganization()
	{
		$page = Request::get("page", 0);
		$limit = Request::get("limit", 10);
		$filter = Request::get("filter", []);

		$data = DB::table("trViolations")
			->join("asIpCachedInfo", function($join) use($filter) {
				$join->on("asIpCachedInfo.id", "=", "trViolations.ip")
					->where("trViolations.userKey", "=", $filter['userKey']);
			})
			->selectRaw("org AS organization, COUNT(*) AS total, country")
			->groupBy("org", "country")
			->orderBy("org");

		$data = $data->paginate($limit);

		return response()->json(['id'=>0, 'listData' => $data])
			->header('Content-Type', 'application/vnd.api+json');		
	}


	public function getSuspiciousCountries()
	{
		$page = Request::get("page", 0);
		$limit = Request::get("limit", 10);
		$filter = Request::get("filter", []);
		$showTable = Request::get("showTable", "false");

		$data = DB::table("trViolations")
			->join("asIpCachedInfo", function($join) use($filter) {
				$join->on("asIpCachedInfo.id", "=", "trViolations.ip");
				if (!empty($filter['userKey'])) $join->where("trViolations.userKey", "=", $filter['userKey']);
			});

		if ($showTable == "false")
		{
			//get map details
			$data = $data->selectRaw("COUNT(*) AS noRequests, rawInfo, country")
				->groupBy("country", "rawInfo")
				->get();

			return response()->json(['id' => 0, 'pageData' => $data]);
		}

		$data->selectRaw("COUNT(*) AS noRequests, country")
			->orderBy("country")
			->groupBy("country");

		$data = $data->paginate($limit);

		return response()->json(['id'=>0, 'listData' => $data, 'pageData' => []])
			->header('Content-Type', 'application/vnd.api+json');			
	}

	
}

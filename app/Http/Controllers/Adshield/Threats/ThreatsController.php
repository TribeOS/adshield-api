<?php

namespace App\Http\Controllers\Adshield\Threats;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\Protection\DummyDataController;

date_default_timezone_set("America/New_York");


class ThreatsController extends BaseController
{

	public function getGraphData()
	{
		$days = Input::get("days", 7);

		$graphData = [
			'automatedTrafficClassification' => $this->getAutomatedTrafficClassification($days),
			'topThreatsByCountry' => $this->getTopThreatsByCountry($days),
			'threatsAverted' => $this->getThreatsAverted($days)
		];

		$graphData['automatedTrafficClassification'] = DummyDataController::ApplyDuration($graphData['automatedTrafficClassification']);
		$graphData['topThreatsByCountry'] = DummyDataController::ApplyDuration($graphData['topThreatsByCountry']);
		$graphData['threatsAverted'] = DummyDataController::ApplyDuration($graphData['threatsAverted']);

		return response()->json(['id'=>0, 'graphData' => $graphData])
			->header('Content-Type', 'application/vnd.api+json');
	}


	private function getAutomatedTrafficClassification($days)
	{
		$data = [
			'data' => [29, 11, 90, 17],
			'label' => ['Unclassified User Agent', 'Bad User Agent', 'Known Violator User Agent', 'Aggregator User Agent']
		];
		return $data;
	}

	private function getTopThreatsByCountry($days)
	{
		$data = [
			'data' => [19, 42, 12, 18, 21],
			'label' => ['United Sates', 'France', 'China', 'Germany', 'Poland']
		];
		return $data;
	}

	private function getThreatsAverted($days)
	{
		$data = [
			'data' => [60, 84, 49, 28, 65],
			'label' => ['Known Violators', 'Javascript Check Failed', 'Javascript Not Loaded', 'Known Violator User Agent', 'Bad User Agent']
		];
		return $data;
	}


	public function getAutomatedTraffic()
	{

		function createData($name, $class, $noRequests) {
			return ['name' => $name, 'classification' => $class, 'pageRequests' => $noRequests];
		}

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

		$data['botsByClassification'] = [
			'data' => [72, 53, 98, 55],
			'label' => ['Unclassified User Agent', 'Bad User Agent', 'Known Violator User Agent', 'Aggregator User Agent']
		];
		$data['mostFrequentBots'] = [
			'data' => [7, 99, 60, 10, 36],
			'label' => ['Uncategorized Bot', 'SEMRush', 'Reporting as Firefox', 'Reporting as Chrome', 'Reporting as Internets']
		];

		$data['botsByClassification'] = DummyDataController::ApplyDuration($data['botsByClassification']);
		$data['mostFrequentBots'] = DummyDataController::ApplyDuration($data['mostFrequentBots']);

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
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

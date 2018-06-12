<?php

namespace App\Http\Controllers\Adshield\Threats;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

date_default_timezone_set("America/New_York");


class ThreatsController extends BaseController
{


	public function getGraphData()
	{
		$days = Input::get("days", 7);

		$graphData = [
			'automatedTrafficClassification' => $this->getAutomatedTrafficClassification($days),
			'' => $this->getAutomatedTrafficClassification($days),
			'topThreatsByCountry' => $this->getTopThreatsByCountry($days),
			'threatsAverted' => $this->getThreatsAverted($days)

		];

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
		$days = Input::get("days", 7);

		function createData($name, $class, $noRequests) {
			return ['name' => $name, 'classification' => $class, 'pageRequests' => $noRequests];
		}

		$data = [];

		$data['automatedTrafficList'] = [
			'total' => 5
		];
		$data['automatedTrafficList']['data'] = [
			createData('SEMRush', 'Bad User Agent',	'495'),
			createData('Reporting as Firefox', 'Unclassified User Agent', '495'),
			createData('Reporting as Chrome', 'Unclassified User Agent', '495'),
			createData('Reporting as Internet Explorer 9', 'Bad User Agent', '495'),
			createData('MJ12bot', 'Known Violator User Agent', '495'),
			createData('cURL', 'Known Violator User Agent', '495')
		];

		$data['botsByClassification'] = [
			'data' => [72, 53, 98, 55],
			'label' => ['Unclassified User Agent', 'Bad User Agent', 'Known Violator User Agent', 'Aggregator User Agent']
		];
		$data['mostFrequentBots'] = [
			'data' => [7, 99, 60, 10, 36],
			'label' => ['Uncategorized Bot', 'SEMRush', 'Reporting as Firefox', 'Reporting as Chrome', 'Reporting as Internets']
		];

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
			generateData('OVH SAS', '10643', 'France'),
			generateData('Advanced Hosters B.V.', '5602', 'Netherlands'),
			generateData('Zscaler', '3326', 'United States'),
			generateData('Amazon.com', '1922', 'United States'),
			generateData('China Telecom Guandong', '1754', 'China'),
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
			generateData('United States', 4983),
			generateData('France', 1342),
			generateData('China', 8732),
			generateData('Germany', 4723),
			generateData('Australia', 3417),
			generateData('Mexico', 1832)
		];

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');			
	}

	
}

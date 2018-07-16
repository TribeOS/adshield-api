<?php

namespace App\Http\Controllers\Adshield\TrafficSummary;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\Protection\DummyDataController;


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

	
}

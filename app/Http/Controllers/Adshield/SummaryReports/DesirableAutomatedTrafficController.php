<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\Protection\DummyDataController;


class DesirableAutomatedTrafficController extends BaseController
{


	public function getData()
	{
		$days = Input::get('days', 60);
		$data = [
			'listData' => $this->getListData($days),
			'desirableAutomatedRequests' => $this->getDesirableAutomatedRequests($days),
		];

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}


	private function getListData($days)
	{
		function generateData($name, $noRequests) {
			return ['systemName' => $name, 'noRequests' => $noRequests];
		}
		$data = [
			generateData('Googlebot', DummyDataController::ApplyDuration(8566)),
			generateData('Yandex Bot', DummyDataController::ApplyDuration(5510)),
			generateData('Bingbot', DummyDataController::ApplyDuration(3911)),
			generateData('Baiduspider', DummyDataController::ApplyDuration(3491)),
			generateData('Googlebot Mobile', DummyDataController::ApplyDuration(2371)),
			generateData('Twitterbot', DummyDataController::ApplyDuration(265)),
			generateData('Yahoo! Slurp', DummyDataController::ApplyDuration(204)),
			generateData('MSNBot Media', DummyDataController::ApplyDuration(144))
		];
		return $data;
	}


	private function getDesirableAutomatedRequests($days)
	{
		$data = [];
		$data['datasets'][] = [
			'data' => [83, 69, 58, 53, 72, 42, 6],
			'label' => 'Googlebot'
		];
		$data['datasets'][] = [
			'data' => [54, 30, 60, 6, 34, 85, 42],
			'label' => 'Yandex Bot'
		];
		$data['label'] = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];

		foreach($data['datasets'] as $index=>$ds) {
			$data['datasets'][$index] = DummyDataController::ApplyDuration($data['datasets'][$index]);
		}

		return $data;
	}

	
}

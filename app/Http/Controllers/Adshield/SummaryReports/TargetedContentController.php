<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\Protection\DummyDataController;


class TargetedContentController extends BaseController
{


	public function getData()
	{
		$days = Input::get('days', 60);
		$data = [
			'listData' => $this->getListData($days),
			'responseCodesByTotalPercentage' => $this->getResponseCodesByTotalPercentage($days),
		];

		$data['responseCodesByTotalPercentage'] = DummyDataController::ApplyDuration($data['responseCodesByTotalPercentage']);

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}


	private function getListData($days)
	{
		function generateData($name, $noRequests) {
			return ['path' => $name, 'noRequests' => $noRequests];
		}
		$data = [
			generateData('/', DummyDataController::ApplyDuration(32)),
			generateData('/blogs.ph', DummyDataController::ApplyDuration(12)),
			generateData('/the-perfect-image-to-kick-off-the-new-year', DummyDataController::ApplyDuration(4)),
			generateData('/10-olympic-sports-you-didnt-know-existed/10', DummyDataController::ApplyDuration(4))
		];
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

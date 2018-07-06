<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\Protection\DummyDataController;


class ClickFraudController extends BaseController
{


	public function getData()
	{
		$days = Input::get('days', 60);
		$data = [
			'automatedClicksVsAdClicks' => $this->getAutomatedClicksVsAdClicks($days),
			'topAgencyByAutomatedClicks' => $this->getTopAgencyByAutomatedClicks($days),
			'clickFraudActivity' => $this->getClickFraudActivity($days)
		];

		$data['automatedClicksVsAdClicks'] = DummyDataController::ApplyDuration($data['automatedClicksVsAdClicks']);
		$data['topAgencyByAutomatedClicks'] = DummyDataController::ApplyDuration($data['topAgencyByAutomatedClicks']);
		$data['clickFraudActivity'] = DummyDataController::ApplyDuration($data['clickFraudActivity']);

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}

	private function getAutomatedClicksVsAdClicks($days)
	{
		$data = [
			'data' => [5, 8],
			'label' => ['Human Ad Clicks', 'Bad Bot Ad Clicks']
		];
		return $data;
	}

	private function getTopAgencyByAutomatedClicks($days)
	{
		$data = [
			'data' => [70, 40, 51, 22, 77],
			'label' => ['_*zp_*sc_', '3556_s6024635_rc..', '_', '143238_26099653 - rd...', '4130_6091761 - rd.co...']
		];
		return $data;
	}

	private function getClickFraudActivity($days)
	{
		$data = [];
		$data['data'] = [31, 39, 93, 70, 9, 48, 53];
		$data['label'] = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];
		return $data;
	}

	
}

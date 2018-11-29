<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\Protection\DummyDataController;
use App\Http\Controllers\Adshield\LogController;


class CaptchaRequestController extends BaseController
{


	public function getData()
	{
		$days = Input::get('days', 60);
		$data = [
			'totalTrafficVsCaptcha' => $this->getTotalTrafficVsCaptcha($days),
			'attemptsSolvedVsFailed' => $this->getAttemptsSolvedVsFailed($days),
			'captchaRequests' => $this->getCaptchaRequests($days)
		];

		$data['totalTrafficVsCaptcha'] = DummyDataController::ApplyDuration($data['totalTrafficVsCaptcha']);
		$data['attemptsSolvedVsFailed'] = DummyDataController::ApplyDuration($data['attemptsSolvedVsFailed']);

		// LogController::QuickLog(LogController::ACT_VIEW_REPORT, [
		// 	'title' => 'Captcha Requests',
		// 	'userKey' => $filter['userKey']
		// ]);

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}

	private function getTotalTrafficVsCaptcha($days)
	{
		$data = [
			'data' => [46, 26],
			'label' => ['Total Traffic', 'CAPTCHA Served']
		];
		return $data;
	}

	private function getAttemptsSolvedVsFailed($days)
	{
		$data = [
			'data' => [2, 12, 54],
			'label' => ['Solved', 'Failed', 'No Attempt']
		];
		return $data;
	}

	private function getCaptchaRequests($days)
	{
		$data = [];
		$data['datasets'][] = [
			'data' => [3, 48, 38, 1, 21, 91, 6],
			'label' => 'Total Traffic'
		];
		$data['datasets'][] = [
			'data' => [47, 57, 90, 87, 59, 13, 31],
			'label' => 'Served Total'
		];
		$data['label'] = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];

		foreach($data['datasets'] as $index=>$ds) {
			$data['datasets'][$index] = DummyDataController::ApplyDuration($data['datasets'][$index]);
		}

		return $data;
	}

	
}

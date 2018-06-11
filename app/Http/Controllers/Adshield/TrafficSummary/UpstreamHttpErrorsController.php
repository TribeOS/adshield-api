<?php

namespace App\Http\Controllers\Adshield\TrafficSummary;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;



class UpstreamHttpErrorsController extends BaseController
{


	public function getData()
	{
		$days = Input::get('days', 60);
		$data = [
			'errorsList' => $this->getErrorsList($days),
			'errorGraph' => $this->getErrorGraph($days)
		];

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}


	private function getErrorsList($days)
	{
		function generate($time, $error1, $error2, $total) {
			return ['time' => $time, '4xx' => $error1, '5xx' => $error2, 'total' => $total];
		}
		$data = [
			generate('today', 142, 6, 148),
			generate('2017-07-07', 199, 6, 216),
			generate('2017-07-06', 204, 20, 224),
			generate('2017-07-05', 220, 18, 238),
			generate('2017-07-04', 189, 17, 203),
			generate('2017-07-03', 190, 19, 209),
			generate('2017-07-02', 266, 26, 292),
			generate('2017-07-01', 224, 30, 254)
		];

		return $data;
	}

	private function getErrorGraph($days)
	{
		$data = [];
		$data['datasets'][] = [
			'data' => [7, 80, 25, 15, 7, 54, 18],
			'label' => 'Number of Requests'
		];
		$data['label'] = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];

		return $data;
	}

	
}

<?php

namespace App\Http\Controllers\Adshield\SummaryReports;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;



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
			generateData('Googlebot', 8566),
			generateData('Yandex Bot', 5510),
			generateData('Bingbot', 3911),
			generateData('Baiduspider', 3491),
			generateData('Googlebot Mobile', 2371),
			generateData('Twitterbot', 265),
			generateData('Yahoo! Slurp', 204),
			generateData('MSNBot Media', 144)
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

		return $data;
	}

	
}

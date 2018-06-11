<?php

namespace App\Http\Controllers\Adshield\TrafficSummary;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;



class CacheAnalysisController extends BaseController
{


	public function getData()
	{
		$days = Input::get('days', 60);
		$data = [
			'cacheEffectivity' => $this->getCacheEffectivity($days),
			'cssServedFrom' => $this->getCssServedFrom($days),
			'jsServedFrom' => $this->getJsServedFrom($days)
		];

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}


	private function getCacheEffectivity($days)
	{
		$data = [
			'data' => [48, 69],
			'label' => ['Objects served from our cache', 'Objects served from your cache']
		];

		return $data;
	}

	private function getCssServedFrom($days)
	{
		$data = [
			'data' => [30, 35],
			'label' => ['Our cache', 'Your cache']
		];

		return $data;
	}

	private function getJsServedFrom($days)
	{
		$data = [
			'data' => [75, 38],
			'label' => ['Our cache', 'Your cache']
		];

		return $data;
	}

	
}

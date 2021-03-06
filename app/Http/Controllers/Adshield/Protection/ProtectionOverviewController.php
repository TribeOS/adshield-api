<?php

namespace App\Http\Controllers\Adshield\Protection;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Config;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use App\Http\Controllers\Adshield\LogController;
use App\Http\Controllers\Adshield\Settings\UserWebsitesController;



class ProtectionOverviewController extends BaseController
{

	private $labels = [
		ViolationController::V_KNOWN_VIOLATOR => 'Known Violators',
		ViolationController::V_JS_CHECK_FAILED => 'Javascript Check Failed',
		ViolationController::V_NO_JS => 'Javascript Not Loaded',
		ViolationController::V_KNOWN_VIOLATOR_UA => 'Known Violator User Agent',
		ViolationController::V_KNOWN_DC => 'Known Violator Data Center'
	];

	public function getList()
	{
	
	}

	public function getGraphData()
	{
		$filter = Input::get("filter", []);
		
		$data = DB::table('trViolations')
			->selectRaw("violation, COUNT(*) AS total")
			->groupBy('violation')
			->whereIn('violation', [
				ViolationController::V_KNOWN_VIOLATOR,
				ViolationController::V_JS_CHECK_FAILED,
				ViolationController::V_NO_JS,
				ViolationController::V_KNOWN_VIOLATOR_UA,
				ViolationController::V_KNOWN_DC
			]);

		if ($filter['userKey'] !== 'all') {
			$data->where('userKey', $filter['userKey']);
		} else {
			$data->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolations.userKey')
					->where("userWebsites.status", UserWebsitesController::ST_ACTIVE)
					->where('userWebsites.accountId', Config::get('user')->accountId);
			});
		}

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("trViolations.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("trViolations.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->get();
		$graphData = ['data' => [], 'label' => []];
		foreach($data as $d)
		{
			$graphData['data'][] = $d->total;
			$graphData['label'][] = $this->labels[$d->violation];
		}


		LogController::QuickLog(LogController::ACT_VIEW_REPORT, [
			'title' => 'Protection Overview',
			'userKey' => $filter['userKey']
		]);

		return response()->json(['id'=>0, 'graphData' => $graphData])
			->header('Content-Type', 'application/vnd.api+json');
	}

	
}

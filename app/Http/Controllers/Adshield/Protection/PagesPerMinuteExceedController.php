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



class PagesPerMinuteExceedController extends BaseController
{


	private $labels = [
		ViolationController::V_IS_BOT => 'Automated Browsers',  
		ViolationController::V_PAGES_PER_MINUTE_EXCEED => 'Pages Per Minute Exceed',
		ViolationController::V_PAGES_PER_SESSION_EXCEED => 'Pages Per Session Exceeded'
	];

	public function getList()
	{
		$page = Input::get('page', 0);
		$limit = Input::get('limit', 10);
		$filter = Input::get('filter', []);

		$data = DB::table("trViolations")
			->join("trViolationIps", "trViolationIps.id", "=", "trViolations.ip")
			->select(DB::raw("ipStr AS ip, COUNT(*) as total"))
			->groupBy('ipStr')
			->where('violation', ViolationController::V_PAGES_PER_MINUTE_EXCEED)
			->orderBy('ipStr', 'asc');

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

		$data = $data->paginate($limit);
		$data->appends([
			'limit' => $limit
		]);

		LogController::QuickLog(LogController::ACT_VIEW_REPORT, [
			'title' => 'Pages Per Minute Exceeded',
			'userKey' => $filter['userKey'],
			'page' => $page
		]);

		return response()->json(['id' => 0, 'listData' => $data]);
	}

	public function getGraphData()
	{
		$filter = Input::get("filter", []);
		$ip = $filter['ip'];

		$violation = DB::table("trViolations")
			->join("trViolationIps", function($join) use($ip) {
				$join->on("trViolationIps.id", "=", "trViolations.ip")
					->where("trViolationIps.ip", "=", inet_pton($ip));
			})
			->select(DB::raw("violation, COUNT(*) AS total"))
			->whereIn("violation", [
				ViolationController::V_IS_BOT,
				ViolationController::V_PAGES_PER_MINUTE_EXCEED,
				ViolationController::V_PAGES_PER_SESSION_EXCEED
			])
			->groupBy("violation");

		if ($filter['userKey'] !== 'all') {
			$violation->where('userKey', $filter['userKey']);
		} else {
			$violation->join('userWebsites', function($join) {
				$join->on('userWebsites.userKey', '=', 'trViolations.userKey')
					->where("userWebsites.status", UserWebsitesController::ST_ACTIVE)
					->where('userWebsites.accountId', Config::get('user')->accountId);
			});
		}

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$violation->where("trViolations.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$violation = $violation->get();

		$info = IpInfoController::GetIpInfo($ip);
		$graphData = [
			'data' => [],
			'label' => [],
			'info' => [
				'ip' => $ip,
				'loc' => $info['city'] . ', ' . $info['country'],
				'org' => $info['org'],
				'isp' => $info['isp']
			]
		];

		foreach($violation as $v)
		{
			$graphData['data'][] = $v->total;
			$graphData['label'][] = $this->labels[$v->violation];
		}

		LogController::QuickLog(LogController::ACT_VIEW_GRAPH_IP, [
			'title' => 'Pages Per Minute Exceeded',
			'userKey' => $filter['userKey'],
			'ip' => $ip
		]);

		return response()->json(['id'=>0, 'graphData' => $graphData]);
	}

	
}

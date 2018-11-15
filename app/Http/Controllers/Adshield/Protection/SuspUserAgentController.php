<?php

namespace App\Http\Controllers\Adshield\Protection;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;


use App\Http\Controllers\Adshield\Violations\ViolationController;


class SuspUserAgentController extends BaseController
{

	private $labels = [
		ViolationController::V_KNOWN_VIOLATOR => 'Identities', 
		ViolationController::V_KNOWN_VIOLATOR_UA => 'Known Signatures', 
		ViolationController::V_SESSION_LENGTH_EXCEED => 'Session Length Exceeded'
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
			->where('violation', ViolationController::V_SUSPICIOUS_UA)
			->where('userKey', $filter['userKey'])
			->orderBy('ipStr', 'asc');

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->paginate($limit);
		$data->appends([
			'limit' => $limit
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
				ViolationController::V_KNOWN_VIOLATOR,
				ViolationController::V_KNOWN_VIOLATOR_UA,
				ViolationController::V_SESSION_LENGTH_EXCEED,
			])
			->groupBy("violation")
			->get();

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

		return response()->json(['id'=>0, 'graphData' => $graphData]);
	}
	
}

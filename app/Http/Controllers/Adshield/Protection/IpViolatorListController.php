<?php

namespace App\Http\Controllers\Adshield\Protection;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\ApiStatController;
use App\Http\Controllers\Adshield\Violations\ViolationController;


class IpViolatorListController extends BaseController
{

	public function getList()
	{
		$page = Input::get('page', 0);
		$limit = Input::get('limit', 10);
		$filter = Input::get('filter', []);

		$data = DB::table("trViolations")
			->join("trViolationIps", "trViolationIps.id", "=", "trViolations.ip")
			->select(DB::raw("ipStr AS ip, COUNT(*) as total"))
			->groupBy('ipStr')
			->where('violation', ViolationController::V_KNOWN_VIOLATOR)
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
			->whereIn("violation", [ViolationController::V_KNOWN_VIOLATOR])
			->groupBy("violation")
			->get();

		//TEST
		$data = [$violation[0]->total, 0, 0];

		$info = IpInfoController::GetIpInfo($ip);
		$graphData = [
			'data' => $data,
			'label' => ['Identities', 'Known Signatures', 'Session Length Exceed'],
			'info' => [
				'ip' => $ip,
				'loc' => $info['city'] . ', ' . $info['country'],
				'org' => $info['org'],
				'isp' => $info['isp']
			]
		];


		return response()->json(['id'=>0, 'graphData' => $graphData]);
	}

}

<?php

namespace App\Http\Controllers\Adshield\Protection;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use App\Model\Violation;


class JsNotLoadedController extends BaseController
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
			->where('violation', ViolationController::V_NO_JS)
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
			->whereIn("violation", [ViolationController::V_NO_JS])
			->groupBy("violation")
			->get();

		//TEST
		$data = [$violation[0]->total, 0];

		$info = IpInfoController::GetIpInfo($ip);
		$graphData = [
			'data' => $data,
			'label' => ['Automated Browsers', 'Rate Unlimited'],
			'info' => [
				'ip' => $ip,
				'loc' => (isset($info['city']) ? $info['city'] : '') . ', ' . (isset($info['country']) ? $info['country'] : ''),
				'org' => isset($info['org']) ? $info['org'] : '',
				'isp' => isset($info['isp']) ? $info['isp'] : ''
			]
		];


		return response()->json(['id'=>0, 'graphData' => $graphData]);
	}
	
}

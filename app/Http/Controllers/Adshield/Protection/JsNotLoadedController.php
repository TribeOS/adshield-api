<?php

namespace App\Http\Controllers\Adshield\Protection;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

date_default_timezone_set("America/New_York");


class JsNotLoadedController extends BaseController
{

	public function getList()
	{
		// $page = Input::get('page', 0);
		// $limit = Input::get('limit', 10);
		// $sortBy = Input::get('sortBy', 'last_updated');
		// $sortDir = Input::get('sortDir', 'DESC');

		// $filter = Input::get('filter', []);

		// $data = DB::table("asListIp")
		// 	->join('asStatInfo', 'asListIp.ip', '=', 'asStatInfo.ip')
		// 	->leftJoin('asStat', function($join) use($filter) {
		// 		$join->on('asStat.info_id', '=', 'asStatInfo.id');
		// 		if (!empty($filter['duration']) && $filter['duration'] > 0)
		// 		{
		// 			$duration = $filter['duration'];
		// 			$join->where("asStat.date_added", ">=", "DATE_SUB(NOW(), INTERVAL $duration DAY)");
		// 		}
		// 	})
		// 	->select(DB::raw("asListIp.ip, COUNT(*) as total"))
		// 	->take($limit)->skip($page * $limit)
		// 	->groupBy('asListIp.ip')
		// 	->orderBy($sortBy, $sortDir);

		// if (!empty($filter['dateFrom']) && !empty($filter['dateTo']))
		// 	$data->whereBetween("last_updated", [$filter['dateFrom'], $filter['dateTo']]);

		// if ($filter['status'] !== null) $data->where("status", $filter['status']);
		// if (!empty($filter['ip']))
		// {
		// 	try {
		// 		$ip = inet_pton($filter['ip']);
		// 		$data->where("asListIp.ip", inet_pton($filter['ip']));
		// 	} catch (Exception $e) {}
		// }

		// $data = $data->paginate($limit);
		// foreach($data as $d)
		// {
		// 	$d->ip = ApiStatController::IPFromBinaryString($d->ip);
		// }

		// $data->appends([
		// 	'sortBy' => $sortBy,
		// 	'sortDir' => $sortDir,
		// 	'limit' => $limit
		// ]);
		
		$data = [];
		$data = DummyDataController::GetIps(Input::get('limit', 10), Input::get('page', 1));

		return response()->json(['id' => 0, 'listData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}

	public function getGraphData()
	{
		$filter = Input::get("filter", []);
		$ip = $filter['ip'];

		//sample data
		$data = DummyDataController::IpGetGraphData(2);

		$info = IpInfoController::GetIpInfo($ip);
		$graphData = [
			'data' => $data[$ip]['violations'],
			'label' => ['Automated Browsers', 'Rate Unlimited'],
			'info' => [
				'ip' => $ip,
				'loc' => $info['city'] . ', ' . $info['country'],
				'org' => $info['org'],
				'isp' => $info['isp']
			]
		];


		return response()->json(['id'=>0, 'graphData' => $graphData])
			->header('Content-Type', 'application/vnd.api+json');
	}
	
}

<?php

namespace App\Http\Controllers\Adshield\Protection;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use DB;
use Illuminate\Support\Facades\Input;

use App\Http\Controllers\Adshield\ApiStatController;


class IpViolatorListController extends BaseController
{

	public function getList()
	{
		$page = Input::get('page', 0);
		$limit = Input::get('limit', 10);
		$sortBy = Input::get('sortBy', 'last_updated');
		$sortDir = Input::get('sortDir', 'DESC');

		$filter = Input::get('filter', []);

		$data = DB::table("asListIp")
			->join('asStatInfo', function($join) {
				$join->on('asListIp.ip', '=', 'asStatInfo.ip')
					->where('asListIp.status', '=', '0');
			})
			->join('asStat', function($join) use($filter) {
				$join->on('asStat.info_id', '=', 'asStatInfo.id')
					->where('asStat.userKey', '=', $filter['userKey']);
				if (!empty($filter['duration']) && $filter['duration'] > 0)
				{
					$duration = $filter['duration'];
					$join->where("asStat.date_added", ">=", "DATE_SUB(NOW(), INTERVAL $duration DAY)");
				}
			})
			->select(DB::raw("asListIp.ip, COUNT(*) as total"))
			->groupBy('asListIp.ip')
			->orderBy($sortBy, $sortDir);

		if (!empty($filter['dateFrom']) && !empty($filter['dateTo']))
			$data->whereBetween("last_updated", [$filter['dateFrom'], $filter['dateTo']]);

		if ($filter['status'] !== null) $data->where("status", $filter['status']);
		if (!empty($filter['ip']))
		{
			try {
				$ip = inet_pton($filter['ip']);
				$data->where("asListIp.ip", inet_pton($filter['ip']));
			} catch (Exception $e) {}
		}

		$data = $data->paginate($limit);
		foreach($data as $d)
		{
			$d->ip = ApiStatController::IPFromBinaryString($d->ip);
		}

		$data->appends([
			'sortBy' => $sortBy,
			'sortDir' => $sortDir,
			'limit' => $limit
		]);

		return response()->json(['id' => 0, 'listData' => $data]);
	}

	public function getGraphData()
	{
		$filter = Input::get("filter", []);
		$ip = $filter['ip'];
		$duration = $filter['duration'];
		// get stat access with the given ip.
		$stats = DB::table('asStat')
			->join('asStatInfo', function($join) use($duration) {
				$join->on('asStat.info_id', '=', 'asStatInfo.id');
				if ($duration > 0) {
					$join->where("asStat.date_added", ">=", "DATE_SUB(NOW(), INTERVAL $duration DAY)");
				}
			})
			->join('asListIp', function($join) use($ip) {
				$join->on('asListIp.ip', '=', 'asStatInfo.ip')
					->where('asListip.status', '=', '0')
					->where('asListIp.ip', '=', inet_pton($ip));
			})
			->select(DB::raw('COUNT(*) AS totalIdentities'))
			->groupBy('asListIp.ip')
			->first();

		//generate dummy data by random
		// $data = DummyDataController::IpGetGraphData(3);
		$data = [$stats->totalIdentities, 0, 0];

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

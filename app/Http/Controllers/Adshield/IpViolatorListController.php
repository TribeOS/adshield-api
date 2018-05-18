<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

date_default_timezone_set("America/New_York");


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
			->join('asStatInfo', 'asListIp.ip', '=', 'asStatInfo.ip')
			->join('asStat', 'asStat.info_id', '=', 'asStatInfo.id')
			->select(DB::raw("asListIp.ip, status, MAX(last_updated)"))
			->take($limit)->skip($page * $limit)
			->groupBy('asListIp.ip', 'status')
			->orderBy($sortBy, $sortDir);

		if (!empty($filter['dateFrom']) && !empty($filter['dateTo']))
			$data->whereBetween("last_updated", [$filter['dateFrom'], $filter['dateTo']]);

		if ($filter['status'] !== null) $data->where("status", $filter['status']);
		if (!empty($filter['ip'])) {
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

		return response()->json(['id' => 0, 'listData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}

	public function getGraphData()
	{
		$filter = Input::get("filter", []);
		//get stat access with the given ip.
		$data = DB::table('asStat')
			->join('asStatInfo', 'asStat.info_id', '=', 'asStatInfo.id')
			->join('asListIp', function($join) use($filter) {
				$join->on('asListIp.ip', '=', 'asStatInfo.ip');
				if (!empty($filter['ip'])) $join->where('asListIp.ip', '=', inet_pton($filter['ip']));
			})
			->select(DB::raw('COUNT(*) AS total, DATE(asStat.date_added) AS added_on'))
			->groupBy(['asListIp.ip', 'added_on'])
			->orderBy('added_on', 'asc')
			->take(30);

		if (!empty($filter['dateFrom']) && !empty($filter['dateTo']))
		{
			$data->whereBetween("asStat.date_added", [$filter['dateFrom'], $filter['dateTo']]);
		}

		if (!empty($filter['ip'])) $data->where('asListIp.ip', inet_pton($filter['ip']));
		if (!empty($filter['status'])) $data->where('asListIp.status', $filter['status']);

		$data = $data->get();
		$graphData = ['dates' => [], 'totals' => []];
		foreach($data as $d) {
			$graphData['dates'][] = $d->added_on;
			$graphData['totals'][] = $d->total;
		}

		return response()->json(['id'=>0, 'graphData' => $graphData])
			->header('Content-Type', 'application/vnd.api+json');
	}
	
}

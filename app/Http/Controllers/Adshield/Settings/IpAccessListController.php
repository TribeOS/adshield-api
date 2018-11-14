<?php

namespace App\Http\Controllers\Adshield\Settings;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Adshield\ApiStatController;

date_default_timezone_set("America/New_York");


class IpAccessListController extends BaseController
{

	public function getList()
	{
		$page = Input::get('page', 0);
		$limit = Input::get('limit', 10);
		$sortBy = Input::get('sortBy', 'date_added');
		$sortDir = Input::get('sortDir', 'DESC');

		$filter = Input::get('filter', []);

		// $data = DB::table("asStat")
		// 	->join("asStatInfo", "asStatInfo.id", "=", "asStat.info_id")
		// 	->leftJoin("asUrlFilter", "asUrlFilter.hash", "=", "asStat.referer_url")
		// 	->select(DB::raw("ip, date_added, visitUrl"))
		// 	->take($limit)->skip($page * $limit)
		// 	->orderBy($sortBy, $sortDir);
		
		$data = DB::table("trViolationLog")
			->join("trViolationSession", function($join) use($filter) {
				$join->on("trViolationLog.sessionId", "=", "trViolationSession.id");
				if (!empty($filter['userKey'])) $join->where('trViolationSession.userKey', $filter['userKey']);
			})
			->join("trViolationIps", "trViolationIps.id", "=", "trViolationSession.ip")
			->selectRaw("ipStr, trViolationLog.createdOn, trViolationLog.url");

		if (!empty($filter['dateFrom']) && !empty($filter['dateTo']))
			// $data->whereBetween("asStat.date_added", [
			$data->whereBetween("trViolationLog.createdOn", [
				date("Y-m-d 00:00:00", strtotime($filter['dateFrom'])), 
				date("Y-m-d 23:59:59", strtotime($filter['dateTo']))
			]);

		if (!empty($filter['userKey'])) $data->where('userKey', $filter['userKey']);

		$data = $data->paginate($limit);
		// foreach($data as $d)
		// {
		// 	$d->ip = ApiStatController::IPFromBinaryString($d->ip);
		// }

		$data->appends([
			'sortBy' => $sortBy,
			'sortDir' => $sortDir,
			'limit' => $limit
		]);

		return response()->json(['id' => 0, 'listData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}
	
}

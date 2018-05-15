<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

date_default_timezone_set("America/New_York");


class IpAccessListController extends BaseController
{

	public function getList()
	{
		$page = Input::get('page', 0);
		$limit = Input::get('limit', 10);
		$sortBy = Input::get('sortBy', 'date_added');
		$sortDir = Input::get('sortDir', 'DESC');

		$filter = [];
		if (Input::get('dateFrom')) $filter['dateFrom'] = Input::get('dateFrom');
		if (Input::get('dateTo')) $filter['dateTo'] = Input::get('dateTo');
		if (Input::get('userKey')) $filter['userKey'] = Input::get('userKey');

		$data = DB::table("asStat")
			->join("asStatInfo", "asStatInfo.id", "=", "asStat.info_id")
			->take($limit)->skip($page * $limit)
			->orderBy($sortBy, $sortDir);

		if (!empty($filter['dateFrom']) && !empty($filte['dateTo']))
			$data->whereBetween("asStat.date_added", [$filter['dateFrom'], $filter['dateTo']]);

		if (!empty($filter['userKey'])) $data->where('userKey', $filter['userKey']);

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

		return response()->json($data)
			->header('Content-Type', 'application/vnd.api+json');
	}
	
}

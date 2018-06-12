<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;



class LogController extends BaseController
{

	public function handle(Request $request)
	{
		if ($request->isMethod('get'))
		{
			return $this->getData();
		}
	}


	private function getData()
	{
		$days = Input::get("duration", 7);
		$data = [];

		function generateData($action, $user, $date) {
			return ['count' => 0, 'action' => $action, 'user' => $user, 'date' => $date];
		}

		$data[] = generateData('view report', 'aman adriano', '2018-06-02 05:32:12');
		$data[] = generateData('view report', 'aman adriano', '2018-06-04 01:17:11');
		$data[] = generateData('view report', 'aman adriano', '2018-06-05 15:58:15');
		$data[] = generateData('view report', 'aman adriano', '2018-06-06 18:02:32');

		return response()->json(['id'=>1, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');

	}

}

<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

date_default_timezone_set("America/New_York");


class IpInfoController extends BaseController
{

	public static function GetIpInfo($ip)
	{
		$url = 'http://ip-api.com/json/' . $ip;
		$response = file_get_contents($url);
		return json_decode($response, true);
	}
	
}

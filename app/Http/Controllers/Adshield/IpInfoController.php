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
		$ipBinary = inet_pton($ip);
		$info = DB::table("asIpCachedInfo")
			->where("ip", $ipBinary)
			->first();
		if (empty($info))
		{
			$url = 'http://ip-api.com/json/' . $ip;
			$response = file_get_contents($url);
			$info = json_decode($response, true);
			self::SaveIpInfo($ip, $info, $response);
		}
		else if (strtotime($info->updatedOn) < strtotime("15 days ago"))
		{
			$url = 'http://ip-api.com/json/' . $ip;
			$response = file_get_contents($url);
			$info = json_decode($response, true);
			self::SaveIpInfo($ip, $info, $response, true);
		}
		else
		{
			$info = json_decode($info->rawInfo, true);
		}

		return $info;
	}


	public static function SaveIpInfo($ip, $info, $response, $expired = false)
	{
		//insert/replace?
		if (!$expired)
		{
			DB::table("asIpCachedInfo")
				->insert([
					'ipStr' => $ip,
					'ip' => inet_pton($ip),
					'org' => $info['org'],
					'isp' => $info['isp'],
					'city' => $info['city'],
					'country' => $info['country'],
					'updatedOn' => gmdate("Y-m-d H:i:s"),
					'rawInfo' => $response
				]);
		}
		else
		{
			DB::table("asIpCachedInfo")
				->update([
					'org' => $info['org'],
					'isp' => $info['isp'],
					'city' => $info['city'],
					'country' => $info['country'],
					'updatedOn' => gmdate("Y-m-d H:i:s"),
					'rawInfo' => $response
				])
				->where('ip', inet_pton($ip));
		}
	}
	
}

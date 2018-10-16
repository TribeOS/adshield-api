<?php

namespace App\Http\Controllers\Adshield\Protection;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

date_default_timezone_set("America/New_York");


class IpInfoController extends BaseController
{

	/**
	 * gets the info of the given IP through ip-api.com service
	 * IMPT this service has limitations. (like 100 requests per hour or something)
	 * @param [type] $ip [description]
	 */
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


	/**
	 * saves the given parameters into our IP info cache table
	 * @param [type]  $ip       [description]
	 * @param [type]  $info     [description]
	 * @param [type]  $response [description]
	 * @param boolean $expired  [description]
	 */
	public static function SaveIpInfo($ip, $info, $response, $expired = false)
	{
		//insert/replace?
		if (!$expired)
		{
			DB::table("asIpCachedInfo")
				->insert([
					'ipStr' => $ip,
					'ip' => inet_pton($ip),
					'org' => self::RetVal($info['org']),
					'isp' => self::RetVal($info['isp']),
					'city' => self::RetVal($info['city']),
					'country' => self::RetVal($info['country']),
					'updatedOn' => gmdate("Y-m-d H:i:s"),
					'rawInfo' => $response
				]);
		}
		else
		{
			DB::table("asIpCachedInfo")
				->update([
					'org' => self::RetVal($info['org']),
					'isp' => self::RetVal($info['isp']),
					'city' => self::RetVal($info['city']),
					'country' => self::RetVal($info['country']),
					'updatedOn' => gmdate("Y-m-d H:i:s"),
					'rawInfo' => $response
				])
				->where('ip', inet_pton($ip));
		}
	}


	public static function RetVal($var)
	{
		if (!isset($var)) return "";
		if (empty($var)) return "";
		return $var;
	}
	
}

<?php

namespace App\Http\Controllers\Adshield\Protection;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

date_default_timezone_set("America/New_York");


class IpInfoController extends BaseController
{

	const MAX_DAYS_OLD_IP = 30;

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
		$id = 0;
		if (empty($info))
		{
			$url = "https://api.ipgeolocation.io/ipgeo?apiKey=a9ac9ebcfef3462aa81f1be0aa3101a2&ip=$ip";
			// $url = 'http://ip-api.com/json/' . $ip;
			$response = file_get_contents($url);
			$info = json_decode($response, true);
			unset($info['time_zone']);
			unset($info['currency']);
			$response = json_encode($info);
			$id = self::SaveIpInfo($ip, $info, $response);
			$info = [
				'country' => $info['country_name'],
				'city' => $info['city'],
				'org' => $info['organization'],
				'isp' => $info['isp']
			];
		}
		else if (strtotime($info->updatedOn) < strtotime(self::MAX_DAYS_OLD_IP . " days ago"))
		{
			$id = $info->id;
			$url = "https://api.ipgeolocation.io/ipgeo?apiKey=a9ac9ebcfef3462aa81f1be0aa3101a2&ip=$ip";
			// $url = 'http://ip-api.com/json/' . $ip;
			$response = file_get_contents($url);
			$info = json_decode($response, true);
			unset($info['time_zone']);
			unset($info['currency']);
			$response = json_encode($info);
			self::SaveIpInfo($ip, $info, $response, true);
			$info = [
				'country' => $info['country_name'],
				'city' => $info['city'],
				'org' => $info['organization'],
				'isp' => $info['isp']
			];
		}
		else
		{
			$record = [
				'city' => $info->city,
				'country' => $info->country,
				'org' => $info->org,
				'isp' => $info->isp,
			];
			$info = $record;
		}

		if (!isset($info['city'])) $info['city'] = '';
		if (!isset($info['country'])) $info['country'] = '';
		if (!isset($info['org'])) $info['org'] = '';
		if (!isset($info['isp'])) $info['isp'] = '';
		$info['id'] = $id;

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
		$id = 0;
		//insert/replace?
		if (!$expired)
		{
			$id = DB::table("asIpCachedInfo")
				->insertGetId([
					'ipStr' => $ip,
					'ip' => inet_pton($ip),
					'org' => $info['organization'] ?? '',
					'isp' => $info['isp'] ?? '',
					'city' => $info['city'] ?? '',
					'country' => $info['country_name'] ?? '',
					'updatedOn' => gmdate("Y-m-d H:i:s"),
					'rawInfo' => $response
				]);
		}
		else
		{
			DB::table("asIpCachedInfo")
				->where('ip', inet_pton($ip))
				->update([
					'org' => $info['organization'] ?? '',
					'isp' => $info['isp'] ?? '',
					'city' => $info['city'] ?? '',
					'country' => $info['country_name'] ?? '',
					'updatedOn' => gmdate("Y-m-d H:i:s"),
					'rawInfo' => $response
				]);
		}
		return $id;
	}


	//TODO:: implement an IP look up that could handle huge traffic!!
	//

}

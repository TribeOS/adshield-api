<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;
use App\Model\ViolationIp;

/**
 * check the user agent for any known aggregator user agents
 */
class ViolationPagesPerMinuteController extends ViolationController {

	const MaxPagesPerMinute = 10; //needs to be set from db

	/**
	 * TODO:::
	 * we use this structure for config
	 * RequestStat : { pagesPerMinute : 10, pagesPerSession : 10, etc... }
	 * save this structure along with the website config as a json object
	 */

	/**
	 * check if user has exceeded the pages per minute limit
	 */
	public static function hasViolation($userKey, $ip, $data, $config)
	{
		$max = self::MaxPagesPerMinute;
		if (!empty($config['RequestStat']['pagesPerMinute'])) $max = $config['RequestStat']['pagesPerMinute'];
		if (self::hasExceed($userKey, $ip, $data, $max)) return true;
		return false;
	}


	/**
	 * check if user has exceeded the max number of page request per minute
	 */
	private static function hasExceed($userKey, $ip, $data, $max)
	{
		date_default_timezone_set("UTC");
		$ipId = ViolationIp::where("ip", $ip)->first();
		$lastDate = DB::select("
			SELECT createdOn FROM trViolationLog
			WHERE userKey = ? AND ip = ?
			ORDER BY createdOn DESC
			LIMIT 2, 1", [$userKey, $ipId->id]);

		if (!empty($lastDate)) 
		{
			$lastDate = $lastDate[0]->createdOn;
			if (strtotime($lastDate) < strtotime("1 minute ago")) self::removeLogs($ip, $userKey, $lastDate);
		}
		// check against logs for the past 1 minute if records exceed for this IP on this website
		// only consider check after the last time the user has a pagesPerMinute violation, otherwise don't filter logs
		$logCount = DB::table("trViolationLog")
			->join("trViolationIps", function($join) use($ip, $userKey) {
				$join->on("trViolationIps.id", "=", "trViolationLog.ip")
					->where("trViolationIps.ip", "=", $ip)
					->where("trViolationLog.userKey", "=", $userKey);
			})
			->where("trViolationLog.createdOn", ">=", "DATE_SUB(NOW(), INTERVAL 1 MINUTE)")
			->count();

		//if user has exceeded, lets remove its logs from the database
		//and issue a ViolationLog for exceeding the pages per minute rule
		if ($logCount > $max) 
		{
			//TODO: confirm if we need to delete logs
			self::removeLogs($ip, $userKey);
			return true;
		}

		return false;
	}

	private static function removeLogs($ip, $userKey, $lastDate=null)
	{
		$where = '';
		if ($lastDate !== null) $where = " AND trViolationLog.createdOn <= '$lastDate'";
		DB::delete("DELETE trViolationLog.* FROM trViolationLog
			INNER JOIN trViolationIps ON
				trViolationLog.ip = trViolationIps.id
				AND trViolationIps.ip = ?
				AND trViolationLog.userKey = ? $where", [$ip, $userKey]);
	}

}
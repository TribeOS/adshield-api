<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

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

		//check if ip and userkey last log was more than 1 minute ago, remove all logs
		$lastWasPast = DB::table("trViolationLog")
			->join("trViolationIps", function($join) use($ip, $userKey) {
				$join->on("trViolationIps.id", "=", "trViolationLog.ip")
					->where("trViolationIps.ip", "=", $ip)
					->where("trViolationLog.userKey", "=", $userKey);
			})
			->select(DB::raw("
				IF(MAX(createdOn) < DATE_SUB(NOW(), INTERVAL 1 MINUTE), 1, 0) AS oldLogs
			"))
			->first();
		if ($lastWasPast->oldLogs == 1) self::removeLogs($ip, $userKey);
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

	private static function removeLogs($ip, $userKey)
	{
		DB::table("trViolationLog")
			->join("trViolationIps", function($join) use($ip, $userKey) {
				$join->on("trViolationIps.id", "=", "trViolationLog.ip")
					->where("trViolationIps.ip", "=", $ip)
					->where("trViolationLog.userKey", "=", $userKey);
			})
			->delete("trViolationLog.*");
	}

}
<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;
use App\Model\ViolationIp;
use App\Model\ViolationSession;

/**
 * check the user agent for any known aggregator user agents
 */
class ViolationPagesPerMinuteController extends ViolationController {

	const MaxPagesPerMinute = 15; //needs to be set from db

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
		$sessionId = self::GetSession();
		$violationSession = ViolationSession::where("id", $sessionId)->first();
		//get total pages (or request) for this session
		$totalPages = DB::table("trViolationLog")
			->where("sessionId", $sessionId)
			->count();
		//get total time elapsed
		$totalMinutes = time() - strtotime($violationSession->createdOn); //get the difference in Seconds
		$totalMinutes = ceil($totalMinutes / 60); //convert it to Minutes
		if ($totalMinutes < 1) $totalMinutes = 1;
		//get average page per minute
		$pagePerMinute = $totalPages / $totalMinutes;

		//if user has exceeded, lets remove its logs from the database
		//and issue a ViolationLog for exceeding the pages per minute rule
		if ($pagePerMinute > $max) 
		{
			return true;
		}

		return false;
	}

	/**
	 * do we need to remove all logs or just leave it there?
	 */
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
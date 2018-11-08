<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

/**
 * check the user agent for any known aggregator user agents
 */
class ViolationPagesPerMinuteController extends ViolationController {

	const MaxPagesPerMinute = 30; //needs to be set from db

	/**
	 * check if user has exceeded the pages per minute limit
	 */
	public static function hasViolation($ip, $data, $config)
	{
		$max = self::MaxPagesPerMinute;
		if (!empty($config['RequestStat']['pagesPerMinute'])) $max = $config['RequestStat']['pagesPerMinute'];
		if (self::hasExceed($ip, $data, $max)) return true;
		return false;
	}


	/**
	 * check if user has exceeded the max number of page request per minute
	 */
	private static function hasExceed($ip, $data, $max)
	{
		// check against logs for the past 1 minute if records exceed for this IP on this website
		// only consider check after the last time the user has a pagesPerMinute violation, otherwise don't filter logs
		$logCount = DB::table("trViolationLog")
			->join("trViolationIps", function($join) use($ip) {
				$join->on("trViolationIps.id", "=", "trViolationLog.ip")
					->where("trViolationIps.ip", "=", $ip);
			})
			->where("trViolationLog.createdOn", ">=", "DATE_SUB(NOW(), INTERVAL 1 MINUTE)")
			->count();
		//if user has exceeded, lets remove its logs from the database
		//and issue a ViolationLog for exceeding the pages per minute rule
		if ($logCount > $max) 
		{
			//TODO: confirm if we need to delete logs
			DB::table("trViolationLog")
				->where("userKey", $data['userKey'])
				->delete();
			return true;
		}

		return false;
	}

}
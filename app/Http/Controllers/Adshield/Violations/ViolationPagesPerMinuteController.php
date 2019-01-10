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

	const MaxPagesPerMinute = 30; //needs to be set from db

	/**
	 * TODO:::
	 * we use this structure for config
	 * RequestStat : { pagesPerMinute : 10, pagesPerSession : 10, etc... }
	 * save this structure along with the website config as a json object
	 */

	/**
	 * check if user has exceeded the pages per minute limit
	 */
	public static function hasViolation($config)
	{
		$max = self::MaxPagesPerMinute;
		if (!empty($config['contentProtection']['sessions']['pagesPerMinute'])) $max = $config['contentProtection']['sessions']['pagesPerMinute'];
		if (self::hasExceed($max)) return true;
		return false;
	}


	/**
	 * check if user has exceeded the max number of page request per minute
	 */
	private static function hasExceed( $max)
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

		//if user has exceeded issue a ViolationLog for exceeding the pages per minute rule
		if ($pagePerMinute > $max) return true;
		return false;
	}


	public static function Respond($config)
	{
		try {
			$setting = $config['contentProtection']['threatResponse']['pagesPerMinuteExceed'];
		} catch (\Exception $e) {
			$setting = 'block';
		}

		return $setting;
	}

}
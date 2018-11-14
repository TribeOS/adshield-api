<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;
use App\Model\ViolationIp;
use App\Model\ViolationSession;

/**
 * Class for checking if session exceeds the allowed maximum session length (in seconds)
 */
class ViolationSessionLengthExceedController extends ViolationController {

	const MaxSessionLength = 1800; //maximum allowed session length in seconds

	/**
	 * TODO:::
	 * we use this structure for config
	 * RequestStat : { pagesPerMinute : 10, pagesPerSession : 10, etc... }
	 * save this structure along with the website config as a json object
	 */

	/**
	 * check if we exceed the maximum session length 
	 */
	public static function hasViolation($config)
	{
		$max = self::MaxSessionLength;
		if (!empty($config['RequestStat']['maxSessionLength'])) $max = $config['RequestStat']['maxSessionLength'];
		if (self::hasExceed($max)) return true;
		return false;
	}


	/**
	 * check if session exceeds the given maximum session length
	 */
	private static function hasExceed($max)
	{
		date_default_timezone_set("UTC");
		$sessionId = self::GetSession();
		$violationSession = ViolationSession::where("id", $sessionId)->first();
		//get total pages (or request) for this session
		$totalPages = DB::table("trViolationLog")
			->where("sessionId", $sessionId)
			->count();
		//get total time elapsed
		$totalSeconds = time() - strtotime($violationSession->createdOn); //get the difference in Seconds

		//if user has exceeded issue a ViolationLog for exceeding the pages per minute rule
		if ($totalSeconds > $max) return true;
		return false;
	}


}
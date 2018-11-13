<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;
use App\Model\ViolationIp;
use App\Model\ViolationSession;
use App\Model\ViolationRequestLog;

date_default_timezone_set("UTC");

/**
 * check the user agent for any known aggregator user agents
 */
class ViolationPagesPerSessionController extends ViolationController {

	const MaxPagesPerSession = 30; //needs to be set from db
	const SessionTimeout = 30; //how long (in seconds) is the allowed interval for each request to be considered as a new session

	/**
	 * Uses the same config structure with PagesPerMinute code (they share the same category/config container)
	 */
	

	/**
	 * check if user has exceeded the pages per minute limit
	 */
	public static function hasViolation($userKey, $ip, $data, $config)
	{
		$max = self::MaxPagesPerSession;
		if (!empty($config['RequestStat']['pagesPerSession'])) $max = $config['RequestStat']['pagesPerSession'];
		if (self::hasExceed($max)) return true;
		return false;
	}


	/**
	 * check if user has exceeded the max number of page request per minute
	 * this check is dependent to PagesPerMinute and vice versa
	 */
	private static function hasExceed($max)
	{
		$sessionId = self::GetSession();
		$totalPages = ViolationRequestLog::where('sessionId', $sessionId)->count();
		if ($totalPages >= $max) return true;
		return false;
	}


	public static function CreateSession($ipId, $userKey)
	{
		$session = new ViolationSession();
		$session->ip = $ipId;
		$session->userKey = $userKey;
		$session->createdOn = gmdate("Y-m-d H:i:s");
		$session->updatedOn = gmdate("Y-m-d H:i:s");
		$session->save();
		return $session;
	}

	public static function StartSession($ip, $userKey)
	{
		$violationIp = ViolationIp::where("ip", $ip)->first();
		$session = ViolationSession::where([
			'ip' => $violationIp->id,
			'userKey' => $userKey
		])
		->orderBy('createdOn', 'desc')
		->first();

		//no existing session for the given IP and userkey/website
		if (empty($session))
		{
			//create new session
			$session = self::CreateSession($violationIp->id, $userKey);
		}
		//check if session is more than the allowed session interval time (continuation of session)
		else if (time() - strtotime($session->updatedOn) >=  self::SessionTimeout)
		{
			//session expired, create/update into new session record
			$session = self::CreateSession($violationIp->id, $userKey);
		}
		//session is still active, +1 to session's pages count, then check if we've exceeded the limit
		else
		{
			//in session (not expired)
			$session->updatedOn = gmdate("Y-m-d H:i:s");
			$session->save();
		}
		self::SetSession($session->id);
	}


}
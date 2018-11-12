<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;
use App\Model\ViolationIp;
use App\Model\ViolationSession;

/**
 * check the user agent for any known aggregator user agents
 */
class ViolationPagesPerSessionController extends ViolationController {

	const MaxPagesPerSession = 30; //needs to be set from db
	const SessionTimeout = 300; //how long (in seconds) is the allowed interval for each request to be considered as a new session

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
		if (self::hasExceed($userKey, $ip, $data, $max)) return true;
		return false;
	}


	/**
	 * check if user has exceeded the max number of page request per minute
	 */
	private static function hasExceed($userKey, $ip, $data, $max)
	{
		date_default_timezone_set("UTC");
		//LOGIC
		//how to determine what a session is? (start/end) 
		//(we set a specific time/seconds/minutes on what is considered as a session/new session)
		//- sessions are saved on table as per IP and userkey
		//- for each request :
		//	- check for existing session for this user/website 
		//	(existing session that is within the allowed time for one session e.g. 10 mins request interval to consider as new session?)
		//	IMPT::: need to consider how much time we allow to consider the request as a new session?
		//	
		//	- NONE : create a new session entry 
		//	- YES : check if current session is considered as a continuation of existing session. (session expired/not expired)
		//		- EXPIRED : delete old session, create new session entry
		//		- NOT EXPIRED : we update the number of pages/request with +1. check if total pages/request for the current session has exceeded the current max pages per session config.
		//			- EXCEED : hasExceed() returns TRUE (this will log a pagesPerSessionExceed violation)
		
		//	TEST CODE
		$violationIp = ViolationIp::where("ip", $ip)->first();
		$session = ViolationSession::where([
			'ip' => $violationIp->id,
			'userKey' => $userKey
		])->first();

		if (empty($session))
		{
			//create new session
			$session = new ViolationSession();
			$session->ip = $violationIp->id;
			$session->userKey = $userKey;
			$session->createdOn = gmdate("Y-m-d H:i:s");
			$session->pages = 1;
		}
		else if (time() - strtotime($session->createdOn) >  self::SessionTimeout)
		{
			//session expired, create/update into new session record
			$session->createdOn = gmdate("Y-m-d H:i:s");
			$session->pages = 1;
		}
		else
		{
			//in session (not expired)
			$session->pages ++;
			if ($session->pages >= $max) return true;
		}


		return false;
	}



}
<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;
use App\Model\ViolationIp;
use App\Model\ViolationSession;
use App\Model\ViolationRequestLog;

date_default_timezone_set("UTC");

/**
 * Class for checking if we exceeded the maximum pages per session allowed
 */
class ViolationPagesPerSessionController extends ViolationController {

	const MaxPagesPerSession = 60; //needs to be set from db
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


	/**
	 * creates a session record 
	 * @param [type] $ipId    [description]
	 * @param [type] $userKey [description]
	 */
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


	/**
	 * gets the IP id if it already exists, creates if it not
	 * @param [type] $ipBinary [description]
	 * @param [type] $ipStr    [description]
	 */
	private static function GetIpId($ipBinary, $ipStr)
	{
		$ip = ViolationIp::where('ip', $ipBinary)->first();
		if (empty($ip))
		{
			$ip = new ViolationIp();
			$ip->ip = $ipBinary;
			$ip->ipStr = $ipStr;
			$ip->save();
		}
		return $ip->id;
	}


	/**
	 * starts the session for this user/website pair
	 * we create a session OR continue a pre existing session given it hasn't expired yet
	 * @param [type] $ip      [description]
	 * @param [type] $ipStr   [description]
	 * @param [type] $userKey [description]
	 */
	public static function StartSession($ip, $ipStr, $userKey)
	{
		$ipId = self::GetIpId($ip, $ipStr);
		$session = ViolationSession::where([
			'ip' => $ipId,
			'userKey' => $userKey
		])
		->orderBy('createdOn', 'desc')
		->first();

		//no existing session for the given IP and userkey/website
		if (empty($session))
		{
			//create new session
			$session = self::CreateSession($ipId, $userKey);
		}
		//check if session is more than the allowed session interval time (continuation of session)
		else if (time() - strtotime($session->updatedOn) >=  self::SessionTimeout)
		{
			//session expired, create/update into new session record
			$session = self::CreateSession($ipId, $userKey);
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
<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

/**
 * checks the passed user agent for irregularities
 * and decides if its suspicious or not
 */
class ViolationSuspiciousUAController extends ViolationController {

	/**
	 * checks if IP is a data center's IP
	 * @param  string  $ip raw IP. ready to be compared to binary IP in database
	 * @return boolean     [description]
	 */
	public static function hasViolation($userAgent)
	{
		$serverUserAgent = $_SERVER['HTTP_USER_AGENT'];
		return $userAgent !== $serverUserAgent;
	}

}
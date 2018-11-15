<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;
use Request;

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
		if ($userAgent !== $_SERVER['HTTP_USER_AGENT']) return true;
		if ($userAgent !== Request::header('user-agent', '')) return true;
		return false;
	}

}
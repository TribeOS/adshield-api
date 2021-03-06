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
	 * checks if useragent exists. 
	 * TODO:: should add more checks for suspicious requests
	 * @param	String	useragent of available
	 * @return boolean     [description]
	 */
	public static function hasViolation($userAgent)
	{
		if ($userAgent !== $_SERVER['HTTP_USER_AGENT']) return true;
		if ($userAgent !== Request::header('user-agent', '')) return true;
		return false;
	}


	public static function Respond($config)
	{
		try {
			$setting = $config['contentProtection']['threatResponse']['suspiciousUA'];
		} catch (\Exception $e) {
			$setting = 'block';
		}

		return $setting;
	}

}
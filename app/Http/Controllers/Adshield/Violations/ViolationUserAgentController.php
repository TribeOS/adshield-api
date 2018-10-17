<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use App\Model\ViolationInfo;

/**
 * handles additional check for violators detected via existing user agent in our database
 * this is like a passive function that ViolationController will call to perform check/log without interrupting the log flow
 */
class ViolationUserAgentController extends ViolationController {


	/**
	 * checks if this user agent exists on our violation info.
	 * @param  string  $userAgent [description]
	 * @return boolean            [description]
	 */
	public static function hasViolation($userAgent='')
	{
		$violation = ViolationInfo::where('userAgent', $userAgent)->first();
		return !empty($violation);
	}

}
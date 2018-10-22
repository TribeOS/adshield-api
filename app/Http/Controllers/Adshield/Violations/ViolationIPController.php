<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use App\Model\ViolationIp;

/**
 * handles additional check for violators detected via existing IP in our database
 */
class ViolationIPController extends ViolationController {


	/**
	 * checks for existing violation record with the same $ip
	 * @param  string  $ip raw IP. ready to be compared to binary IP in database
	 * @return boolean     [description]
	 */
	public static function hasViolation($ip='')
	{
		$violation = ViolationIp::where('ip', $ip)->first();
		//check agains blacklist IP's as well
		return !empty($violation);
	}

}
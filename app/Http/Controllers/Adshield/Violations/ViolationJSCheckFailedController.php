<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

/**
 * checks if the request is from a bot or not. 
 */
class ViolationJSCheckFailedController extends ViolationController {


	/**
	 * js check is done on the JS lib. 
	 * we catch the result of that check here and store the result
	 */
	public static function hasViolation($isOK=false)
	{
		//add extra checking here in the future (if necessary)
		return !$isOK;
	}

}
<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

/**
 * check the client's browser passes our own distinction for a regular/normal browser
 */
class ViolationBrowserIntegrityCheckController extends ViolationController {


	/**
	 * 
	 */
	public static function hasViolation()
	{

		return false;
	}

}
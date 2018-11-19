<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

use Request;

/**
 * determine which traffic automation to label the current request
 * should be able to indicate the traffic name based on identified characteristics of the request
 */
class AutomatedTrafficCheckController extends ViolationController {


	/**
	 * main logging function for the identified automated traffic
	 */
	public static logAutomatedTraffic($violationLogId, $data=[])
	{
		$headers = Request::header();

		$name = self::IdentifyName($data, $headers);
		self::LogTrafficName($name, $violationLogId);
	}


	/**
	 * try to identify what kind of traffic is this or what bot name is this
	 * based on what we have on our database and what the characterisitcs of the request is
	 * @param [type] $data    [description]
	 * @param [type] $headers [description]
	 */
	private static function IdentifyName($data, $headers)
	{
		//TODO::
	}
	

	/**
	 * create log for recording traffic name or bot name for specific traffic or violation log
	 * @param [type] $name           [description]
	 * @param [type] $violationLogId [description]
	 */
	private static function LogTrafficName($name, $violationLogId)
	{
		// TODO
		// DB::table("trVaiolation")
	}

}
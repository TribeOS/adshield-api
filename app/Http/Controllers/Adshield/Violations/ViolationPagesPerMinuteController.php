<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

/**
 * check the user agent for any known aggregator user agents
 */
class ViolationPagesPerMinuteController extends ViolationController {

	const MaxPagesPerMinute = 10; //needs to be set from db

	/**
	 * check if user has exceeded the pages per minute limit
	 */
	public static function hasViolation($data, $config)
	{
		$max = self::MaxPagesPerMinute;
		if (!empty($config['RequestStat']['pagesPerMinute'])) $max = $config['RequestStat']['pagesPerMinute'];
		if (self::hasExceed($data, $max)) return true;
		return false;
	}


	/**
	 * check if user has exceeded the max number of page request per minute
	 */
	private static function hasExceed($data, $max)
	{
		//TODO: 
		// check against logs for the past 1 minute if records exceed for this IP on this website
		// only consider check after the last time the user has a pagesPerMinute violation, otherwise don't filter logs
	}

}
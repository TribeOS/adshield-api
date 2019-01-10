<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

/**
 * check the user agent for any known aggregator user agents
 */
class ViolationAggregatorUserAgentController extends ViolationController {


	/**
	 * performs a series of tests on the request data we have for possible
	 * bot/spam/crawler based on data like user agent and other request data and headers
	 */
	public static function hasViolation($data)
	{	
		$botname = self::isRobot(isset($data['userAgent']) ? $data['userAgent'] : "");
		if ($botname !== false) return $botname;
		return false;
	}


	/**
	 * check if userAgent has a matching agent string in our known agents 
	 * that are marked as bad robot/crawler
	 */
	private static function isRobot($userAgent)
	{
		$knownAgent = DB::table("knownAgents")
			->select("description")
			->where("uaString", "like", '%' . trim($userAgent) . '%')
			->where('type', 'like', '%R%')
			->first();

		if (!empty($knownAgent)) return $knownAgent->description;
		return false;
	}


	public static function Respond($config)
	{
		try {
			$setting = $config['contentProtection']['threatResponse']['aggregatorUA'];
		} catch (\Exception $e) {
			$setting = 'block';
		}

		return $setting;
	}


}
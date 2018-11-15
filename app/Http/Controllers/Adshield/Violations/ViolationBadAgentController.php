<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;
use Request;

/**
 * check the user agent for any known aggregator user agents
 */
class ViolationBadAgentController extends ViolationController {


	/**
	 * performs a series of tests on the request data we have for possible
	 * bot/spam/crawler based on data like user agent and other request data and headers
	 */
	public static function hasViolation()
	{
		$userAgent = Request::header('user-agent', '');
		if (self::isBadUserAgent($userAgent)) return true;
		return false;
	}


	/**
	 * check if userAgent has a matching agent string in our bad bots phrases
	 * also checks against our list of known agents that belongs to crawlers, robots
	 * generally those traffic that are not genuine users accessing the site as intended.
	 */
	private static function isBadUserAgent($userAgent)
	{
		$badAgent = DB::table('badAgents')
			->whereRaw("? LIKE CONCAT('%', phrase, '%')", [$userAgent])
			->first();
		if (!empty($badAgent)) return true;

		$knownAgent = DB::table("knownAgents")
			->where("uaString", "like", '%' . trim($userAgent) . '%')
			->where(function($query) {
				$query->where('type', 'like', '%S%')
					->orWhere('type', 'like', '%R%');
			})
			->first();

		return !empty($knownAgent);
	}

}
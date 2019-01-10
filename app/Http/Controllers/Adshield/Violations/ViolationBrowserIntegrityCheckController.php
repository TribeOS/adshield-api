<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

/**
 * check the client's browser passes our own distinction for a regular/normal browser
 */
class ViolationBrowserIntegrityCheckController extends ViolationController {


	/**
	 * performs a series of tests on the request data we have for possible
	 * bot/spam/crawler based on data like user agent and other request data and headers
	 * TODO: need to research
	 */
	public static function hasViolation($data)
	{
		//empty/blank useragent treated as a browser integrity flaw
		if (empty($data['userAgent'])) return true;
		//check if useragent exists in our knownagents and is marked as spam/bad bot
		if (self::isBadBot($data['userAgent'])) return true;
		return false;
	}


	/**
	 * check if userAgent has a matching agent string in our known agents 
	 * that are marked as bad bot/spam
	 */
	private static function isBadBot($userAgent)
	{
		$badAgent = DB::table('badAgents')
			->whereRaw("? LIKE CONCAT('%', phrase, '%')", [$userAgent])
			->first();
		if (!empty($badAgent)) return true;

		$knownAgent = DB::table("knownAgents")
			->where("uaString", "like", '%' . trim($userAgent) . '%')
			->where('type', 'like', '%S%')
			->first();

		return !empty($knownAgent);
	}


	public static function Respond($config)
	{
		try {
			$setting = $config['contentProtection']['threatResponse']['browserIntegrity'];
		} catch (\Exception $e) {
			$setting = 'block';
		}

		return $setting;
	}

}
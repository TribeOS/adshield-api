<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

/**
 * handles additional check for violators detected via existing user agent in our database
 * this is like a passive function that ViolationController will call to perform check/log without interrupting the log flow
 */
class ViolationUserAgentController extends ViolationController {


	/**
	 * checks if this user agent exists on our violation info.
	 * we perform additional check to make sure : 
	 * - we're not using any newly saved violation record along with the current request/checking (IP checking is run before this)
	 * - thus making sure we are only checking user agents of existing violations prior to this request
	 * @param  string  $userAgent [description]
	 * @return boolean            [description]
	 */
	public static function hasViolation($userAgent='', $newViolationId=0)
	{
		$violation = DB::table("trViolationInfo")
			->join("trViolations", function($join) use($userAgent, $newViolationId) {
				$join->on("trViolations.violationInfo", "=", "trViolationInfo.id")
					->where("trViolations.id", '!=', $newViolationId)
					->whereIn("trViolations.violation", [
						self::V_BAD_UA,
						self::V_UNCLASSIFIED_UA,
						self::V_AGGREGATOR_UA
					])
					->where('userAgent', '=', $userAgent);
			})
			->select("trViolations.id")
			->orderBy('trViolations.createdOn', 'desc')
			->first();

		if (!empty($violation))
		{
			$trafficName = DB::table("trViolationAutoTraffic")
				->where("violationId", $violation->id)
				->selectRaw("trafficName")
				->first();
			if (empty($trafficName)) return "n/a";
			return $trafficName->trafficName;
		}
		
		return false;
	}

	public static function Respond($config)
	{
		try {
			$setting = $config['contentProtection']['threatResponse']['knownViolatorUA'];
		} catch (\Exception $e) {
			$setting = 'block';
		}

		return $setting;
	}

}
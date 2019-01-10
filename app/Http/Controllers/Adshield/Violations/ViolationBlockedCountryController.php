<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

/**
 * checks if the given IP address is in the range of our known data center IP Range
 */
class ViolationBlockedCountryController extends ViolationController {


	/**
	 * checks if IP is a data center's IP
	 * @param  string  $ip raw IP. ready to be compared to binary IP in database
	 * @return boolean     [description]
	 */
	public static function hasViolation($userKey='', $country='')
	{
		$violation = DB::table('asBlockedCountries')
			->join('countries', function($join) use($userKey, $country) {
				$join->on('countries.id', '=', 'asBlockedCountries.country')
					->whereRaw('UCASE(countries.countryName) = ?', [strtoupper($country)])
					->where('asBlockedCountries.userKey', '=', $userKey);
			})
			->first();

		return !empty($violation);
	}


	public static function Respond($config)
	{
		try {
			$setting = $config['contentProtection']['threatResponse']['blockedCountry'];
		} catch (\Exception $e) {
			$setting = 'block';
		}

		return $setting;
	}

}
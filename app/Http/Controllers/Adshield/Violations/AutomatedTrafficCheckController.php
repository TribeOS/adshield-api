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
	public static function logAutomatedTraffic($violationLogId, $data=[], $trafficName=null)
	{
		$headers = Request::header();

		//perform traffic name determination 
		if ($trafficName == null || $trafficName == 'n/a')
		{
			$name = self::IdentifyName($data, $headers);
		}
		else 
		{
			$name = $trafficName;
		}
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
		$userAgent = Request::header('user-agent', '');

		//detect which automatic traffic this is


		//detect which browser request looks like
		$browser = self::DetectBrowser($userAgent);
		if (!empty($browser)) return "Reporting as $browser";
	}
	

	/**
	 * create log for recording traffic name or bot name for specific traffic or violation log
	 * @param [type] $name           [description]
	 * @param [type] $violationLogId [description]
	 */
	private static function LogTrafficName($name, $violationLogId)
	{
		DB::table("trViolationAutoTraffic")
			->insert([
				'trafficName' => $name,
				'violationId' => $violationLogId
			]);
	}


	/**
	 * detect which browser the request came from (appears to be)
	 * via its user agent string
	 * @param [type] $userAgent [description]
	 */
	private static function DetectBrowser($userAgent)
	{
		$arr_browsers = ["Firefox", "Chrome", "Safari", "Opera", 
                    "MSIE", "Trident", "Edge"];

        $user_browser = '';
		foreach ($arr_browsers as $browser)
		{
		    if (strpos($userAgent, $browser) !== false)
		    {
		        $user_browser = $browser;
		        break;
		    }   
		}
		 
		switch ($user_browser) {
		    case 'MSIE':
		        $user_browser = 'Internet Explorer';
		        break;
		 
		    case 'Trident':
		        $user_browser = 'Internet Explorer';
		        break;
		 
		    case 'Edge':
		        $user_browser = 'Internet Explorer';
		        break;
		}

		return $user_browser;
	}


	private static function DetectBot($userAgent)
	{

	}

}
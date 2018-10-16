<?php

namespace App\Http\Controllers\Adshield\Violations;

use Illuminate\Routing\Controller as BaseController;
use App\Http\Controllers\Adshield\ApiStatController;
use App\Http\Controllers\Adshield\Protection\IpInfoController;
use App\Model\Violation;
use App\Model\ViolationInfo;
use DB;


/**
 * main class for violations controllers
 */
class ViolationController extends BaseController {

	const V_KNOWN_VIOLATOR = 'KNOWN_VIOLATOR';
	const V_NO_JS = 'NO_JS';
	const V_JS_CHECK_FAILED = 'JS_CHECK_FAILED';
	const V_KNOWN_VIOLATION_UA = 'KNOWN_VIOLATOR_UA';
	const V_SUSPICIOUS_UA = 'SUSPICIOUS_UA';
	const V_BROWSER_INTEGRITY = 'BROWSER_INTEGRITY';
	const V_KNOWON_DC = 'KNOWN_DATA_CENTER';
	const V_PAGES_PER_MINUTE_EXCEED = 'PAGES_PER_MINUTE_EXCEED';
	const V_PAGES_PER_SESSION_EXCEED = 'PAGES_PER_SESSION_EXCEED';
	const V_BLOCKED_COUNTRY = 'BLOCKED_COUNTRY';
	const V_AGGREGATOR_UA = 'AGGREGATOR_UA';
	const V_KNOWN_VIOLATOR_AUTO_TOOL = 'KNOWN_VIOLATOR_AUTO_TOOL';

	/**
	 * get user IP via ApiStatController's method.
	 * we fetch both String IP "123.123.10.34" and its Binary Form for storage
	 * @return [type] [description]
	 */
	protected function GetUserIp()
	{
		$ips = ApiStatController::GetIPBinary(true);
		return ['binary' => $ips[0], 'string' => $ips[1]];
	}

	/**
	 * get the info of the IP using existing function in the IpInfoController class
	 * will return information from third party provider
	 * IMPT this has limits pls check main function for details
	 * @param  [type] $ip [description]
	 * @return [type]     [description]
	 */
	protected function GetIpInfo($ip)
	{
		return IpInfoController::GetIpInfo($ip);
	}



	protected function logViolation($userKey, $ip, $ipStr, $violationType, $data)
	{
		$infoId = 0;
		//check if info exists, if so use its id. otherwise create new entry.
		$info = DB::table("trViolationInfo")
			->where([
				'userAgent' => !empty($data['userAgent']) ? $data['userAgent'] : '',
				'fullUrl' => !empty($data['fullUrl']) ? $data['fullUrl'] : '',
				'country' => !empty($data['country']) ? $data['country'] : '',
				'city' => !empty($data['city']) ? $data['city'] : ''
			])->first();
		
		if (empty($info))
		{
			//create new violation info for recording
			$info = new ViolationInfo();
			$info->userAgent = !empty($data['userAgent']) ? $data['userAgent'] : '';
			$info->fullUrl = !empty($data['fullUrl']) ? $data['fullUrl'] : '';
			$info->country = !empty($data['country']) ? $data['country'] : '';
			$info->city = !empty($data['city']) ? $data['city'] : '';
			$info->save();
		} 
		$infoId = $info->id;

		//create new violation record
		$violation = new Violation();
		$violation->createdOn = gmdate("Y-m-d H:i:s");
		$violation->ip = $ip;
		$violation->ipStr = $ipStr;
		$violation->violation = $violationType;
		$violation->violationInfo = $infoId;
		$violation->userKey = $userKey;
		$violation->save();

	}

	/**
	 * make sure userKey passed exists in our database
	 * @param [type] $userKey [description]
	 */
	protected function VerifyKey($userKey=null)
	{
		if (empty($userKey)) return false;
		$website = DB::table('userWebsites')->where('userKey', $userKey)->first();
		if (empty($website)) return false;
		return true;
	}

}
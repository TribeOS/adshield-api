<?php

namespace App\Http\Controllers\Adshield\Violations;

use Illuminate\Routing\Controller as BaseController;
use App\Http\Controllers\Adshield\ApiStatController;
use App\Http\Controllers\Adshield\Protection\IpInfoController;
use App\Model\Violation;
use App\Model\ViolationInfo;
use DB;

use App\Http\Controllers\Adshield\Violations\ViolationUserAgentController;
use App\Http\Controllers\Adshield\Violations\ViolationIPController;
use App\Http\Controllers\Adshield\Violations\ViolationDataCenterController;
use App\Http\Controllers\Adshield\Violations\ViolationBlockedCountryController;


/**
 * main class for violations controllers
 */
class ViolationController extends BaseController {

	const V_KNOWN_VIOLATOR = 'KNOWN_VIOLATOR';
	const V_NO_JS = 'NO_JS';
	const V_JS_CHECK_FAILED = 'JS_CHECK_FAILED';
	const V_KNOWN_VIOLATOR_UA = 'KNOWN_VIOLATOR_UA';
	const V_SUSPICIOUS_UA = 'SUSPICIOUS_UA';
	const V_BROWSER_INTEGRITY = 'BROWSER_INTEGRITY';
	const V_KNOWN_DC = 'KNOWN_DATA_CENTER';
	const V_PAGES_PER_MINUTE_EXCEED = 'PAGES_PER_MINUTE_EXCEED';
	const V_PAGES_PER_SESSION_EXCEED = 'PAGES_PER_SESSION_EXCEED';
	const V_BLOCKED_COUNTRY = 'BLOCKED_COUNTRY';
	const V_AGGREGATOR_UA = 'AGGREGATOR_UA';
	const V_KNOWN_VIOLATOR_AUTO_TOOL = 'KNOWN_VIOLATOR_AUTO_TOOL';
	const V_NONE = 'none'; //pass this to logViolation()'s violationType to perform other passive checks only

	function __construct() {
		//used for testing data
		// ViolationDataCenterController::hasViolation(inet_pton('5.77.36.7'));
	}

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

	/**
	 * subclass access to log save function
	 * @param  [type] $userKey       [description]
	 * @param  [type] $ip            [description]
	 * @param  [type] $ipStr         [description]
	 * @param  [type] $violationType [description]
	 * @param  [type] $data          [description]
	 * @return [type]                [description]
	 */
	protected function logViolation($userKey, $ip, $ipStr, $violationType, $data)
	{
		$violations = [];

		//check if useragent has an existing violation
		if (ViolationUserAgentController::hasViolation($data['userAgent'])) {
			$this->doLog($userKey, $ip, $ipStr, self::V_KNOWN_VIOLATOR_UA, $data);
			$violations[] = self::V_KNOWN_VIOLATOR_UA;
		}

		//check if IP has an existing violation
		if (ViolationIPController::hasViolation($ip)) {
			$this->doLog($userKey, $ip, $ipStr, self::V_KNOWN_VIOLATOR, $data);
			$violations[] = self::V_KNOWN_VIOLATOR;
		}

		//check if IP belongs to a data center IP range
		if (ViolationDataCenterController::hasViolation($ip)) {
			$this->doLog($userKey, $ip, $ipStr, self::V_KNOWN_DC, $data);
			$violations[] = self::V_KNOWN_DC;
		}

		//check for blocked country
		if (ViolationBlockedCountryController::hasViolation(
				isset($data['country']) ? $data['country'] : '')
			) {
			$this->doLog($userKey, $ip, $ipStr, self::V_BLOCKED_COUNTRY, $data);
			$violations[] = self::V_BLOCKED_COUNTRY;
		}

		if ($violationType !== self::V_NONE) {
			$this->doLog($userKey, $ip, $ipStr, $violationType, $data);
			$violations[] = $violationType;
		}

		return $violations;
	}


	/**
	 * performs the actual saving of log
	 * @param  [type] $userKey       [description]
	 * @param  [type] $ip            [description]
	 * @param  [type] $ipStr         [description]
	 * @param  [type] $violationType [description]
	 * @param  [type] $data          [description]
	 * @return [type]                [description]
	 */
	private function doLog($userKey, $ip, $ipStr, $violationType, $data)
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
		$msg = "Invalid request!";
		if (empty($userKey)) die($msg);
		$website = DB::table('userWebsites')->where('userKey', $userKey)->first();
		if (empty($website)) die($msg);
	}

}
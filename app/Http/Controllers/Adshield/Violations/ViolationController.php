<?php

namespace App\Http\Controllers\Adshield\Violations;

use Illuminate\Routing\Controller as BaseController;
use App\Http\Controllers\Adshield\ApiStatController;
use App\Http\Controllers\Adshield\Protection\IpInfoController;
use App\Model\Violation;
use App\Model\ViolationInfo;
use App\Model\ViolationIp;
use App\Model\UserConfig;
use App\Model\ViolationRequestLog;
use DB;
use Session;

use App\Http\Controllers\Adshield\Violations\ViolationUserAgentController;
use App\Http\Controllers\Adshield\Violations\ViolationIPController;
use App\Http\Controllers\Adshield\Violations\ViolationDataCenterController;
use App\Http\Controllers\Adshield\Violations\ViolationBlockedCountryController;
use App\Http\Controllers\Adshield\Violations\ViolationSuspiciousUAController;
use App\Http\Controllers\Adshield\Violations\ViolationBrowserIntegrityCheckController;
use App\Http\Controllers\Adshield\Violations\ViolationPagesPerMinuteController;
use App\Http\Controllers\Adshield\Violations\ViolationSessionLengthExceedController;
use App\Http\Controllers\Adshield\Violations\ViolationAutomationToolController;


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
	const V_SESSION_LENGTH_EXCEED = 'SESSION_LENGTH_EXCEED';
	const V_NONE = 'none'; //pass this to logViolation()'s violationType to perform other passive checks only

	//session name for storing cross object data
	const SESSION_ID_NAME = 'V_SESSION_NAME_NEEDED';

	//we use this to indicate if the log has created a new violation record and/or new info record
	private $newViolationInfoRecord = false, $newViolationIp= false;
	
	//we store the current time and use for all logs on the current request
	//to make sure they all have the same date/time
	private $currentTime = '';

	//website's config
	protected $config = [];

	function __construct() {
		//used for testing data
		// ViolationDataCenterController::hasViolation(inet_pton('5.77.36.7'));
		//we store the current date/time upon arrival of request
		$this->currentTime = gmdate("Y-m-d H:i:s");
	}


	/**
	 * gets the current config for the given website (userkey)
	 */
	protected function GetConfig($userKey='')
	{
		$config = UserConfig::where('userKey', $userKey)->first();
		if (empty($config)) return false;
		return json_decode($config->config, 1);
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
	 * this method performs most of the violation checks and creates a log for each violations
	 * this returns all the violations and positive checks in an Array[]
	 * @param  [type] $userKey       [description]
	 * @param  [type] $ip            [description]
	 * @param  [type] $ipStr         [description]
	 * @param  [type] $violationType [description]
	 * @param  [type] $data          [description]
	 * @return [type]                [description]
	 */
	protected function logViolation($userKey, $ip, $ipStr, $violationType, $data)
	{
		$newViolationId = 0;
		$violations = [];

		//check if IP has an existing violation
		if (ViolationIPController::hasViolation($ip)) 
		{
			$newViolationId = $this->doLog($userKey, $ip, $ipStr, self::V_KNOWN_VIOLATOR, $data);
			$violations[] = self::V_KNOWN_VIOLATOR;
		}


		//check if useragent has an existing violation
		if (ViolationUserAgentController::hasViolation(
				isset($data['userAgent']) ? $data['userAgent'] : '', $newViolationId)
			) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_KNOWN_VIOLATOR_UA, $data);
			$violations[] = self::V_KNOWN_VIOLATOR_UA;
		}

		//check if IP belongs to a data center IP range
		if (ViolationDataCenterController::hasViolation($ip)) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_KNOWN_DC, $data);
			$violations[] = self::V_KNOWN_DC;
		}

		//check for blocked country
		if (ViolationJSCheckFailedController::hasViolation(
				isset($data['jsCheck']) ? $data['jsCheck'] : false)
			) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_JS_CHECK_FAILED, $data);
			$violations[] = self::V_JS_CHECK_FAILED;
		}

		//check for blocked country
		if (ViolationBlockedCountryController::hasViolation(
				isset($data['country']) ? $data['country'] : '')
			) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_BLOCKED_COUNTRY, $data);
			$violations[] = self::V_BLOCKED_COUNTRY;
		}

		//check for suspiciouse user agent
		if (ViolationSuspiciousUAController::hasViolation(
				isset($data['userAgent']) ? $data['userAgent'] : '')
			) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_SUSPICIOUS_UA, $data);
			$violations[] = self::V_SUSPICIOUS_UA;
		}

		//check browser integrity
		if (ViolationBrowserIntegrityCheckController::hasViolation($data)) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_BROWSER_INTEGRITY, $data);
			$violations[] = self::V_BROWSER_INTEGRITY;
		}

		//check aggregator user agent
		if (ViolationAggregatorUserAgentController::hasViolation($data)) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_AGGREGATOR_UA, $data);
			$violations[] = self::V_AGGREGATOR_UA;
		}

		//check if this is an automation tool
		if (ViolationAutomationToolController::hasViolation($data)) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_KNOWN_VIOLATOR_AUTO_TOOL, $data);
			$violations[] = self::V_KNOWN_VIOLATOR_AUTO_TOOL;
		}

		//check pages per session
		if (ViolationPagesPerSessionController::hasViolation($this->config)) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_PAGES_PER_SESSION_EXCEED, $data);
			$violations[] = self::V_PAGES_PER_SESSION_EXCEED;
		}

		//check pages per minute 
		if (ViolationPagesPerMinuteController::hasViolation($this->config)) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_PAGES_PER_MINUTE_EXCEED, $data);
			$violations[] = self::V_PAGES_PER_MINUTE_EXCEED;
		}

		//check session length
		if (ViolationSessionLengthExceedController::hasViolation($this->config)) 
		{
			$this->doLog($userKey, $ip, $ipStr, self::V_SESSION_LENGTH_EXCEED, $data);
			$violations[] = self::V_SESSION_LENGTH_EXCEED;
		}

		if ($violationType !== self::V_NONE) {
			$this->doLog($userKey, $ip, $ipStr, $violationType, $data);
			$violations[] = $violationType;
		}

		return $violations;
	}


	/**
	 * check if info exists, if so use its id. otherwise create new entry.
	 */
	private function getInfoId($data)
	{
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
			$this->newViolationInfoRecord = true;
		}
		return $info->id;
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
		$infoId = $this->getInfoId($data);

		//store violation ip if non-existent
		$violationIp = ViolationIp::where('ip', $ip)->first();
		if (empty($violationIp))
		{
			$violationIp = new ViolationIp();
			$violationIp->ip = $ip;
			$violationIp->ipStr = $ipStr;
			$violationIp->save();
			$this->newViolationIp = true;
		}

		//create new violation record
		$violation = new Violation();
		$violation->createdOn = $this->currentTime;
		$violation->ip = $violationIp->id;
		$violation->violation = $violationType;
		$violation->violationInfo = $infoId;
		$violation->userKey = $userKey;
		$violation->save();
		return $violation->id;
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

	protected function LogRequest($ipBinary, $ipStr, $userKey, $data)
	{
		$infoId = $this->getInfoId($data);
		$log = new ViolationRequestLog();
		$log->createdOn = gmdate("Y-m-d H:i:s");
		$log->sessionId = self::GetSession();
		$log->infoId = $infoId;
		$log->url = isset($data['visitUrl']) ? $data['visitUrl'] : '';
		$log->save();
	}

	/**
	 * sets/stores the ViolationSessionID
	 * this is used by pages per minute and pages per session functions
	 */
	public static function SetSession($sessionId)
	{
		session([self::SESSION_ID_NAME => $sessionId]);
	}

	/**
	 * gets the stored violation session ID
	 * this is used by pages per minute and pages per session functions
	 */
	public static function GetSession()
	{
		return session(self::SESSION_ID_NAME);
	}

}
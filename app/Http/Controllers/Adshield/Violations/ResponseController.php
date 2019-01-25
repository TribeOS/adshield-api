<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;

use App\Model\UserConfig;
use App\Model\ViolationResponse;
use App\Model\UserWebsite;
use DB;


use App\Http\Controllers\Adshield\Violations\ViolationUserAgentController;
use App\Http\Controllers\Adshield\Violations\ViolationIPController;
use App\Http\Controllers\Adshield\Violations\ViolationDataCenterController;
use App\Http\Controllers\Adshield\Violations\ViolationBlockedCountryController;
use App\Http\Controllers\Adshield\Violations\ViolationSuspiciousUAController;
use App\Http\Controllers\Adshield\Violations\ViolationBrowserIntegrityCheckController;
use App\Http\Controllers\Adshield\Violations\ViolationPagesPerMinuteController;
use App\Http\Controllers\Adshield\Violations\ViolationPagesPerSessionController;
use App\Http\Controllers\Adshield\Violations\ViolationSessionLengthExceedController;
use App\Http\Controllers\Adshield\Violations\ViolationAggregatorUserAgentController;
use App\Http\Controllers\Adshield\Violations\ViolationAutomationToolController;
use App\Http\Controllers\Adshield\Violations\ViolationBadAgentController;
use App\Http\Controllers\Adshield\Violations\AutomatedTrafficCheckController;
use App\Http\Controllers\Adshield\Violations\ViolationJSCheckFailedController;


/**
 * handles all responses for the given violations
 */
class ResponseController {


	//IMPT:: still to be decided later on
	//response values
	const RP_BLOCKED = 'BLOCKED';
	const RP_ALLOWED = 'ALLOWED';
	const RP_CAPTCHA = 'CAPTCHA';
	
	private $config=null;
	private $userKey;
	private $violations;
	private $info;
	private $jsCode;


	function __construct($userKey=null, $config=null, $violations=[], $info="")
	{
		$this->userKey = $userKey;
		$this->violations = $violations;
		$this->info = $info;

		//fetch account config
		$this->config = $config;

		$this->jsCode = '';
		$userWebiste = UserWebsite::where("userKey", $userKey)->first();
		$this->jsCode = $userWebiste->jsCode;
	}


	/**
	 * check config and violations
	 * create a response string for the frontend to use
	 */
	public function CreateResponse()
	{
		//no config yet? don't perform response
		if ($this->config == null) return;

		$violationId = 0;
		$response = 'allow';
		$info = [];

		foreach($this->violations as $violation => $data)
		{
			if ($violation == ViolationController::V_KNOWN_VIOLATOR)
			{
				$response = ViolationIPController::Respond($this->config);
			} 
			else if (isset($this->violations[V_NO_JS]))
			{
				//no response
				//js won't be running anyway
				$response = 'block';
			} 
			else if (isset($this->violations[V_JS_CHECK_FAILED])) 
			{
				$response = ViolationJSCheckFailedController::Respond($this->config);
			} 
			else if (isset($this->violations[V_KNOWN_VIOLATOR_UA])) 
			{
				$response = ViolationUserAgentController::Respond($this->config);
			} 
			else if (isset($this->violations[V_SUSPICIOUS_UA])) 
			{
				$response = ViolationSuspiciousUAController::Respond($this->config);
			} 
			else if (isset($this->violations[V_BROWSER_INTEGRITY])) 
			{
				$response = ViolationBrowserIntegrityCheckController::Respond($this->config);
			} 
			else if (isset($this->violations[V_KNOWN_DC])) 
			{
				$response = ViolationDataCenterController::Respond($this->config);
			} 
			else if (isset($this->violations[V_PAGES_PER_MINUTE_EXCEED])) 
			{
				$response = ViolationPagesPerMinuteController::Respond($this->config);
			} 
			else if (isset($this->violations[V_PAGES_PER_SESSION_EXCEED])) 
			{
				$response = ViolationPagesPerSession::Respond($this->config);
			} 
			else if (isset($this->violations[V_BLOCKED_COUNTRY])) 
			{
				$response = ViolationBlockedCountryController::Respond($this->config);
			} 
			else if (isset($this->violations[V_AGGREGATOR_UA])) 
			{
				$response = ViolationAggregatorUserAgentController::Respond($this->config);
			} 
			else if (isset($this->violations[V_KNOWN_VIOLATOR_AUTO_TOOL])) 
			{
				$response = ViolationAutomationToolController::Respond($this->config);
			} 
			else if (isset($this->violations[V_SESSION_LENGTH_EXCEED])) 
			{
				$response = ViolationSessionLengthExceedController::Respond($this->config);
			} 
			else if (isset($this->violations[V_BAD_UA])) 
			{
				$response = ViolationBadAgentController::Respond($this->config);
			} 
			else if (isset($this->violations[V_UNCLASSIFIED_UA]))
			{
				try {
					$response = $this->config['contentProtection']['threatResponse']['unclassifiedUA'];
				} catch (\Exception $e) {
				}
			} 
			else if (isset($this->violations[V_IS_BOT])) 
			{
				try {
					$response = $this->config['contentProtection']['threatResponse']['bot'];
				} catch (\Exception $e) {
				}
			}

			$violationId = $data;
			break;
		}

		$response = strtolower($response);
		if ($response == 'block') {
			$response = self::RP_BLOCKED;
		} else if ($response == 'captcha') {
			$response = self::RP_CAPTCHA;
		} else if ($response == 'allow') {
			$response = self::RP_ALLOWED;
		} else {
			$response = self::RP_BLOCKED;
		}

		$this->LogResponse($violationId, $response, $info);

		if ($response == self::RP_BLOCKED) {
			return $this->Block();
		} else if ($response == self::RP_CAPTCHA) {
			return $this->Captcha($violationId);
		} else if ($response == self::RP_ALLOWED) {
			return $this->Allow();
		}

	}


	private function Allow()
	{
		return response()->json(['action' => 'allow', 'jsCode' => $this->jsCode]);
	}

	/**
	 * compose message to inform frontend that we are not showing ads (due to violation/threats)
	 */
	private function Block()
	{
		return response()->json(['action' => 'block']);
	}


	/**
	 * compose response message for showing captcha
	 * @param int $violationId Violation ID of current check
	 */
	private function Captcha($violationId)
	{
		return response()->json(['action' => 'captcha', 'violationId' => $violationId, 'jsCode' => $this->jsCode]);
	}


	/**
	 * saves the response taken to our database
	 */
	private function LogResponse($violationId, $response, $info='')
	{
		$log = new ViolationResponse();
		$log->violationId = $violationId;
		$log->createdOn = gmdate("Y-m-d H:i:s");
		$log->responseTaken = $response;
		$log->info = is_array($info) ? json_encode($info) : $info;
		$log->save();
	}

	
}
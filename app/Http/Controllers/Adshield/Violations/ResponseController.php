<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;

use App\Model\UserConfig;
use App\Model\ViolationResponse;
use DB;


use App\Http\Controllers\Adshield\Violations\ViolationIPController;
use App\Http\Controllers\Adshield\Violations\ViolationDataCenterController;


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


	function __construct($userKey=null, $config=null, $violations=[], $info="")
	{
		$this->userKey = $userKey;
		$this->violations = $violations;
		$this->info = $info;

		//fetch account config
		$this->config = $config;
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
			if ($violation == ViolationController::V_KNOWN_VIOLATOR) {
				$response = ViolationIPController::Respond($this->config);
			} else if (isset($this->violations[V_NO_JS])) {
				//no response
				//js won't be running anyway
				$response = 'block';
			} else if (isset($this->violations[V_JS_CHECK_FAILED])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_KNOWN_VIOLATOR_UA])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_SUSPICIOUS_UA])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_BROWSER_INTEGRITY])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_KNOWN_DC])) {
				$response = ViolationDataCenterController::Respond($this->config);
			} else if (isset($this->violations[V_PAGES_PER_MINUTE_EXCEED])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_PAGES_PER_SESSION_EXCEED])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_BLOCKED_COUNTRY])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_AGGREGATOR_UA])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_KNOWN_VIOLATOR_AUTO_TOOL])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_SESSION_LENGTH_EXCEED])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_BAD_UA])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_UNCLASSIFIED_UA])) {
				//block
				$response = 'block';
			} else if (isset($this->violations[V_IS_BOT])) {
				//block
				$response = 'block';
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
		} else if ($repsonse == self::RP_CAPTCHA) {
			return $this->Captcha($violationId);
		} else if ($response == self::RP_ALLOWED) {
			return $this->Allow();
		}

	}


	private function Allow()
	{
		return response()->json(['action' => 'allow']);
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
		return response()->json(['action' => 'captcha', 'violationId' => $violationId]);
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
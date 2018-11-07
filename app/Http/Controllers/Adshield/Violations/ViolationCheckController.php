<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;
use Request;

/**
 * MAIN handler for checking violations.
 * this will handle the call made by our JS library
 * main flow would be : 
 * - js gathers info from the frontend and sends it over to our backend
 * - this function handles the request
 * - checks for any violations
 * - logs any violations made
 * - based on violations made, return the proper return value for the front JS lib to act accordingly
 * (e.g. open Block page/div, redirect page, etc... <<  still for clarification/confirmation)
 */
class ViolationCheckController extends ViolationController {


	/**
	 * main function used for checking traffic
	 */
	public function Check($userKey='')
	{
		$this->VerifyKey($userKey);
		$this->config = $this->GetConfig($userKey);
		//get user information
		$ip = $this->GetUserIp();
		$info = Request::all();
		if (!empty($ip['string']))
		{
			try {
				$ipInfo = $this->GetIpInfo($ip['string']);
				$info['country'] = $ipInfo['country'];
				$info['city'] = $ipInfo['city'];
			} catch (\Exception $e) {}
		}

		//try to Log traffic here as well for monitoring pages per minute and pages per session?
		$this->LogRequest($ip['binary'], $ip['string']);
		//=======================

		$violations = [];
		//call other logs to perform logging
		try {
			$violations = $this->logViolation($userKey, $ip['binary'], $ip['string'], ViolationController::V_NONE, $info);
			//get config and return action for JS lib to perform
		} catch (\Exception $e) {
			die();
		}

		return $this->Response($userKey, $violations, $info);

	}

	

	/**
	 * compose command/response for JS lib to follow
	 */
	private function Response($userKey, $violations=[], $info=[])
	{
		//check the violations.
		$config = $this->GetConfig($userKey);
		//get the config for that violation
		//return signal/action for JS to interpret and perform frontend functions

		return 0;
	}


}
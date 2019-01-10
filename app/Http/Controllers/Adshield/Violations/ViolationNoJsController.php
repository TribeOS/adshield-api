<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;


/**
 * handles No JS violation. (when JS is disabled on the client/browser)
 */
class ViolationNoJsController extends ViolationController {


	public function log($userKey)
	{
		$this->VerifyKey($userKey);
		$this->config = $this->GetConfig($userKey);
		//get user information
		$ip = $this->GetUserIp();
		$info = [
			'userAgent' => empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'],
			'fullUrl' => '',
			'jsCheck' => true,
			'userKey' => $userKey
		];
		if (!empty($ip['string']))
		{
			try {
				$ipInfo = $this->GetIpInfo($ip['string']);
				$info['country'] = $ipInfo['country'];
				$info['city'] = $ipInfo['city'];
			} catch (\Exception $e) {}
		}

		ViolationPagesPerSessionController::StartSession($ip['binary'], $ip['string'], $userKey, $this->config);
		$this->LogRequest($ip['binary'], $ip['string'], $userKey, $info);

		//save user info and violation
		try {
			$this->logViolation($userKey, $ip['binary'], $ip['string'], ViolationController::V_NO_JS, $info);
		} catch (\Exception $e) { }

		return response('', 200)
			->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
	}


	public static function Respond($config)
	{
		//nothing to respond.
		//JS doesn't work when this violation is raised
		return 'block';
	}



}
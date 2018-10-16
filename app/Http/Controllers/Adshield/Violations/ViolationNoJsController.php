<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;


/**
 * handles No JS violation. (when JS is disabled on the server)
 */
class ViolationNoJsController extends ViolationController {

	public function log($userKey)
	{
		//get user information
		$ip = $this->GetUserIp();
		
		$info = [
			'userAgent' => $_SERVER['HTTP_USER_AGENT'],
			'fullUrl' => ''
		];
		if (!empty($ip['string']))
		{
			try {
				$ipInfo = $this->GetIpInfo($ip['string']);
				$info['country'] = $ipInfo['country'];
				$info['city'] = $ipInfo['city'];
			} catch (\Exception $e) {}
		}

		//save user info and violation
		try {
			$this->logViolation($ip['binary'], $ip['string'], ViolationController::V_NO_JS, $info);
		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

}
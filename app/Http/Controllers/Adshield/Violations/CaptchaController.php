<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Model\UserConfig;
use App\Model\CaptchaLog;
use DB;
use Request;


/**
 * handles all captcha related messages from AdshieldJS captcha function
 */
class CaptchaController {


	var $sessionId = 0; //currently generated session id for this request
	var $violationId = 0; //violation ID (reason why we are showing a captcha?)


	/**
	 * main entry point for messages from adshieldjs captcha
	 * @return [type] [description]
	 */
	public function receive($userkey, $act)
	{
		//userkey 
		//act = action taken (shown, success, failed, cancelled)
		$action = 'shown';
		switch ($act) {
			case 'shown' : $action = 'SHOWN'; break;
			case 'success' : $action = 'SUCCESS'; break;
			case 'failed' : $action = 'FAILED'; break;
			case 'cancelled' : $action = 'CANCELLED'; break;
		}

		$data = Request::all();

		$log = new CaptchaLog();
		$log->violationId = $data['violationId'];
		$log->action = $action;
		$log->createdOn = gmdate("Y-m-d H:i:s");
		$log->save();
	}


}
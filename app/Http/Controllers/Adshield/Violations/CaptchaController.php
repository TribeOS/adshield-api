<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Model\UserConfig;
use DB;


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
	}


	/**
	 * captcha shown
	 * @return [type] [description]
	 */
	private function shown()
	{

	}

	/**
	 * captcha answer failed
	 * @return [type] [description]
	 */
	private function failed()
	{

	}


	/**
	 * captcha answer successfuly
	 * @return [type] [description]
	 */
	private function success()
	{

	}


	/**
	 * captcha was cancelled by the user
	 * @return [type] [description]
	 */
	private function cancelled()
	{

	}


}
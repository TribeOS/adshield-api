<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Model\UserConfig;
use DB;



/**
 * handles all responses for the given violations
 */
class ResponseController {

	
	private $config=null;
	private $userKey;
	private $violations;
	private $info;


	function __construct($userKey, $violations, $info)
	{
		$this->userKey = $userKey;
		$this->violations = $violations;
		$this->info = $info;

		//fetch account config
		$config = UserConfig::where('userKey', $userKey)->first();
		if (!empty($config)) {
			$this->config = json_decode($config->config, 1);
		}
	}


	/**
	 * check config and violations
	 * create a response string for the frontend to use
	 */
	public function CreateResponse()
	{
		//no config yet? don't perform response
		if ($this->config == null) return;

		//check violations for occurence of violations with responses.
		//compose response text
		//return to caller

		return '';
	}


	private function Block()
	{

	}


	private function Captcha()
	{

	}

	
}
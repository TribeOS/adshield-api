<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Model\UserConfig;
use App\Model\ViolationResponse;
use DB;



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
		//
		//we'll need a heirarchy of violations to follow, which one should be considered first to take action on before others.
		
		//call logging code here to log before performing the action
		// $this->LogResponse();

		return '';
	}


	private function Block()
	{

	}


	private function Captcha()
	{

	}


	private function LogResponse($violationId, $response, $info='')
	{
		$log = new ViolationResponse();
		$log->violationId = $violationId;
		$log->createdOn = gmdate("Y-m-d H:i:s");
		$log->responseTaken = $response;
		$log->info = $info;
		$log->save();
	}

	
}
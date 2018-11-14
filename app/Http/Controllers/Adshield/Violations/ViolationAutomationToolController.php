<?php

namespace App\Http\Controllers\Adshield\Violations;

use App\Http\Controllers\Adshield\Violations\ViolationController;
use DB;

/**
 * Class for checking if request/origin came from an automation tool like
 * phantomjs, slimerjs, selenium, etc...
 */
class ViolationAutomationToolController extends ViolationController {

	/**
	 * check if request triggers any known automation tool signature
	 */
	public static function hasViolation($data)
	{
		//if JS passes this request as an automation tool we assume it is an automation tool otherwise, perform the other tests!!!
		$isAuto = isset($data['isAuto']) ? $data['isAuto'] : false;
		if ($isAuto) return true;
		if (self::isPhantomJS($data)) return true;
		return false;
	}


	/**
	 * we check the header of the request if it fits the signature for phantomJS
	 * default test to pass as an automation is to get at least 3 out of 4
	 * https://blog.shapesecurity.com/2015/01/22/detecting-phantomjs-based-visitors/
	 * @param  [type]  $data [description]
	 * @return boolean       [description]
	 */
	private static function isPhantomJS($data)
	{
		$score = 0;
		$headers = getallheaders();
		if (array_search('Host', array_keys($headers)) == count($headers) - 1) $score ++;
		$connection = isset($headers['Connection']) ? $headers['Connection'] : '';
		if ($connection !== strtolower($connection) && $connection !== strtoupper($connection)) $score ++;
		if (strtolower($headers['Accept-Encoding']) == 'gzip') $score ++;
		if (strpos('PhantomJS', $headers['User-Agent']) !== false) $score ++;

		return $score > 2;
	}

}
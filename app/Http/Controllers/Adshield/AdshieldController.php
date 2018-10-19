<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

/**
 * handles importing of installing/inserting of Adshield(TribeOS)
 * to third party websites
 */
class AdshieldController extends BaseController
{

	/**
	 * main function to call to import adshield to third party websites
	 */
	public function ImportAdshield()
	{
		header("Content-Type: application/javascript");
		$shieldType = 3; //default to use the "guess estimate" function to capture clicks on ad

		// $jsContent = Storage::get('libraries/adshield.min.js');
		$jsContent = Storage::get('libraries/adshield.js');
		$urls = [
			'statlog' => route('AdshieldLogstat'),
    		'checkReferrer' => route('AdshieldCheckUrl'),
    		'adShieldHandler' => route('AdshieldHandler'),
    		'vlog' => route('CheckViolation', ['userKey' => ''])
    	];

		$jsContent .= "\nAdShield.urls = " . json_encode($urls) . ';';
		$jsContent .= "AdShield.Init();";
		
		return $jsContent;
	}

}

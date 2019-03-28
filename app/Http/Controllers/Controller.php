<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Config;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * returns the user's local date/time (Y-m-d H:i:s) converted to UTC
     * UTC is what the system uses for internal storage.
     * @param String $format date("format") values. e.g. "Y-m-d H:i:s"
     * @param int $timestamp time(). date/time presentation in unix timestamp
     * @return String date/time converted to UTC formatted into $format 
     */
   	protected function getUtc($format, $timestamp="now") 
   	{	
   		$user = Config::get('user');
   		$localOffset = empty($user->timeZone) ? 0 : $user->timeZone; //est. we'll get this value from the request (to be added on each request)
   		$localTimezone = new DateTimeZone($localOffset);
   		$dateTime = new DateTime($timestamp, $localTimezone);
   		$dateTime->setTimeZone(new DateTimeZone("UTC"));
   		return $dateTime->format($format);
   	}

}

<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    /**
     * returns the Local date/time (Y-m-d H:i:s) of the given date/time converted to UTC
     * UTC is what the system uses for internal storage.
     */
   	protected function getUtc($format, $timestamp="now") 
   	{
   		$localOffset = '-0400'; //est. we'll get this value from the request (to be added on each request)
   		$localTimezone = new DateTimeZone($localOffset);
   		$dateTime = new DateTime($timestamp, $localTimezone);
   		$dateTime->setTimeZone(new DateTimeZone("UTC"));
   		return $dateTime->format($format);
   	}

}

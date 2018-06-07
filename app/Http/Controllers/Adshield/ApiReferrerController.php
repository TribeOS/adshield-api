<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;


class ApiReferrerController extends BaseController
{
	//results returned by check()
	const STATUS_SAFE = 1; //found as SAFE from google safe browsing
	const STATUS_UNKNOWN = 2; //no status from google safe browsing yet
 	const STATUS_UNSAFE = 0; //found as unsafe based on google safe browsing
	const STATUS_IFRAME = 5; //detected running under iframe
	const STATUS_BOT = 6; //used to mark occurence of suspected BOT traffic
	const STATUS_NO_REFERRER = 7; //no referrer or direct access
	const STATUS_EXPIRED = 4; //when the url is last updated more than the limit duration

	const EXPIRE_DAY = "7 days ago"; //number of days to hold the result before querying again on safebrowsing

	const API_KEY = 'AIzaSyCK5yNHdQf2QodfVVaOM5ifyp3odr1OOvY';


	/**
	 * main function to call for checking if url is unsafe
	 */
	public function Check()
	{
		if (!Input::has('url')) {
			echo json_encode(array('result' => -1));
			exit();
		}
		$url = Input::get("url");
		$fullUrl = Input::get("fullUrl");
		$result = $this->CheckUrl($url);

		$source = Input::get("source", ""); //defaults to blank
		$subSource = Input::get("sub_source", ""); //defaults to blank
		$userAgent = Input::get("user_agent", ""); //defaults to blank
		$userKey = Input::get("userKey", ""); //to which this stat belongs to
		$hash = md5($url);

		$stat = new ApiStatController();
		if ($result == self::STATUS_UNSAFE)
		{
			$stat->LogStat($hash, urldecode($fullUrl), $result, $source, $subSource, $userAgent, $userKey);
		}
		else if ($result == self::STATUS_SAFE)
		{}
		echo json_encode(array('result'=>"$result"));
		VisualizerController::BroadcastStats();
	}

	/**
	 * main checking function
	 */
	private function CheckUrl($url=null) 
	{
		if ($url == null) return self::STATUS_UNKNOWN;
		$hash = md5($url);
		//check if another process is already ongoing for this url
		//also checks if url is in our unsafe list
		$status = $this->getUrlStatus($url, $hash);
		if ($status != self::STATUS_EXPIRED) return $status;
		//check against google safebrowsing (if url is in the list, its a threat)
		if ($this->isOnSafeBrowsing($url, $hash)) return self::STATUS_UNSAFE;
		return self::STATUS_SAFE;
	}

	/**
	 * adds new url/record to unsafe list
	 */
	private function addToUrlList($url, $hash)
	{
		//add this referrer url to the list
		DB::table('asUrlFilter')->insert(
			array(
				'url' => $url, 
				'last_updated' => date('Y-m-d H:i:s'), 
				'status' => self::STATUS_UNKNOWN,
				'hash' => $hash
			)
		);
	}

	/**
	 * removes url from the database of unsafe urls
	 */
	private function removeFromUnsafeList($hash)
	{
		DB::table('asUrlFilter')->where('hash', '=', $hash)->delete();
	}

	/**
	 * set status of the url
	 * this helps prevent multiple request to google safebrowsing for the same url
	 */
	private function setUrlStatus($hash, $status)
	{
		DB::table('asUrlFilter')
			->where('hash', '=', $hash)
			->update(array('status' => $status, 'last_updated' => date('Y-m-d H:i:s')));
	}

	private function getUrlStatus($url, $hash)
	{
		$rec = DB::table('asUrlFilter')
			->where('hash', '=', $hash)
			->first();

		//no entry for this url yet on our database
		if ($rec == null)
		{
			//add to our database
			$this->addToUrlList($url, $hash); //we set the default status of url record to UNKOWN, since its going to get processed
			return self::STATUS_EXPIRED; //not processed before/ not existing in our database
			                             //we return STATUS_EXPIRED so it'll run against safebrowsing api
			                             //instead of being returned as unknown
		}

		//check if expired
		if ( strtotime($rec->last_updated) < strtotime(self::EXPIRE_DAY) ) return self::STATUS_EXPIRED;

		return $rec->status; //this can be : 0, 1, 2
	}

	/**
	 * check agains google safebrowsing
	 */
	public function isOnSafeBrowsing($url, $hash)
	{
		$api_key = self::API_KEY;	//our api key for safebrowsing admin@tr.be
		$gsb_url = "https://safebrowsing.googleapis.com/v4/threatMatches:find?key=$api_key";

		$data = array(
			'client' => array(
				'clientId' => 'share.cat',
				"clientVersion" => "1.5.2"
			),
			'threatInfo' => array(
				'threatTypes' => array('MALWARE', 'SOCIAL_ENGINEERING', 'POTENTIALLY_HARMFUL_APPLICATION', 'UNWANTED_SOFTWARE'),
				'platformTypes' => array('ANY_PLATFORM'),
				'threatEntryTypes' => array('URL'),
				'threatEntries' => array('url' => $url)
			)
		);

		$data = json_encode($data);

		$params = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                'Content-Length: ' . strlen($data)
            )
		);

		$req = curl_init($gsb_url);
		curl_setopt_array($req, $params);
		$response = curl_exec($req);
		$response = json_decode($response, 1);

		//if response is blank/empty that means URL is not on safebrowsing list of unsafe sites.
		if (empty($response) || count($response) == 0)
		{
			$this->setUrlStatus($hash, self::STATUS_SAFE);
			return false;
		}

		//url is on the list. its unsafe/threat
		$this->setUrlStatus($hash, self::STATUS_UNSAFE);
		return true;
	}


}

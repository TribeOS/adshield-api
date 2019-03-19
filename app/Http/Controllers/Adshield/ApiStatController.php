<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;


class ApiStatController extends BaseController
{

	/**
	 * returns a string of IP from binary value
	 */
	public static function IPFromBinaryString($binary)
	{
	    if (strlen($binary) == 4) {
	        return inet_ntop(pack("A4", $binary));
	    } else if (strlen($binary == 16)) {
	        return inet_ntop(pack("A16", $binary));
	    }
	}

	/**
	 * get user's IP and convert to varbinary for storage
	 */
	public static function GetIPBinary($raw=false)
	{
	    $headers = apache_request_headers();
	    if (empty($headers['X-Forwarded-For']))
	    {
	    	if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
		    {
		    	$userIP = $_SERVER['HTTP_CLIENT_IP'];
		    }
		    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
		    {
		    	$userIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
		    }
		    else
		    {
		     	$userIP = $_SERVER['REMOTE_ADDR'];
		    }
	    }
	    else
	    {
	    	$fwdIPs = $headers['X-Forwarded-For'];
	    	list($userIP) = preg_split("/,/", $fwdIPs);
	    }
	    $ip = inet_pton($userIP);
	    if ($raw) return [$ip, $userIP];
	    return $ip;
	}

	/**
	 * public function to be called via HTTP call
	 * that proxies LogStat() function
	 */
	public function DoLog()
	{
		$url = md5(Input::get('url', ''));
		$fullUrl = urldecode(Input::get('full_url', ''));
		$status = Input::get('status', '');
		$source = Input::get('source', '');
		$subSource = Input::get('sub_source', '');
		$userAgent = Input::get('user_agent', '');
		$visitUrl = urldecode(Input::get('visitUrl', ''));
		$userKey = Input::get('key', '');
		$ip = Input::get('ip', null);
		$this->LogStat($url, $fullUrl, $status, $source, $subSource, $userAgent, $visitUrl, $userKey, $ip);
	}

	/**
	 * logs visitor's ip and referrering url
	 */
	public function LogStat(
		$url, $fullUrl, $status, $source='', $subSource='', $userAgent='',
		$visitUrl='', $userKey='', $userIp=null
	)
	{
		if ($userIp == null)
		{
			$ip = self::GetIPBinary();
		}
		else
		{
			$ip = inet_pton($userIp);
		}
		$id = $this->LogStatInfo([
			'full_url' => $fullUrl,
			'source' => $source,
			'sub_source' => $subSource,
			'ip' => $ip,
			'user_agent' => $userAgent
		]);

		DB::table('asStat')->insert(
			array(
				'referer_url' => $url,
				'date_added' => gmdate('Y-m-d H:i:s'),
				'info_id' => $id,
				'filter_result' => $status,
				'visitUrl' => $visitUrl,
				'userKey' => $userKey
			)
		);
	}

	/**
	 * saves the extra info of the user
	 * makes sure we only save data once (no duplicates on all columns)
	 */
	private function LogStatInfo($data)
	{
		$rec = DB::table('asStatInfo')
			->where('full_url', $data['full_url'])
			->where('source', $data['source'])
			->where('sub_source', $data['sub_source'])
			->where('ip', $data['ip'])
			->first();
		$id = null;
		if ($rec == null) {
			$id = DB::table('asStatInfo')->insertGetId($data);
		} else {
			$id = $rec->id;
		}

		return $id;
	}


	/**
	 * get stats for the given userKey. (pass blank userkey to get all stat)
	 * number of traffic per status for the given period
	 */
	public static function GetStats($accountId=0, $userKey=null, $dateFrom, $dateTo)
	{
		$params = [
			gmdate("Y-m-d H:i:s", strtotime($dateFrom)),
			gmdate("Y-m-d H:i:s", strtotime($dateTo))
		];
		$data = DB::table("asStat")
			->select(DB::raw("filter_result, COUNT(*) AS total"))
			->groupBy("filter_result")
			->orderBy("filter_result");

		if (empty($dateFrom))
		{
			$data->where("date_added", "<=", $params[1]);
		}
		else
		{
			$data->whereBetween("date_added", $params);
		}

		if (!empty($userKey) && $userKey !== 'all') {
			$data->where("asStat.userKey", $userKey);
		} else if (!empty($accountId)) {
			$data->join("userWebsites", function($join) use($accountId) {
				$join->on("userWebsites.userKey", "=", "asStat.userKey")
					->where("userWebsites.accountId", "=", $accountId);
			});
		}

		$data = $data->get();

		$result = [
			['status' => 0, 'title' => 'Unsafe', 'count' => 0], //unsafe
			['status' => 1, 'title' => 'Safe', 'count' => 0], //safe
			['status' => 5, 'title' => 'IFramed', 'count' => 0],	//iframe
			['status' => 6, 'title' => 'Bot', 'count' => 0],	//bot
			['status' => 7, 'title' => 'Direct Access', 'count' => 0]  //direct access (no referrer)
		];

		foreach($data as $d)
		{
			foreach($result as $i => $res)
			{
				if ($res['status'] == $d->filter_result)
				{
					$result[$i]['count'] = $d->total;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * 1. get count of all stats that were saved for the past x seconds
	 * 2. get transactions count for every given interval (total every x seconds)
	 */
	public static function GetTotalTransactionsSince(
		$accountId=0, $userKey=null, $timeElapsed="3 seconds ago", $returnData=false, $interval=2
	)
	{
		$params = [
			gmdate("Y-m-d H:i:s", strtotime($timeElapsed)),
			gmdate("Y-m-d H:i:s", time())
		];
		// $data = DB::table("asStat")->whereBetween("date_added", $params)
		// 	->select(DB::raw("COUNT(*) AS total"));
		$data = DB::table("trViolationLog")
			->join("trViolationSession", "trViolationSession.id", "=", "trViolationLog.sessionId")
			->whereBetween("trViolationLog.createdOn", $params)
			->selectRaw("COUNT(*) AS total");

		if ($returnData)
		{
			// $data->selectRaw("COUNT(*) AS total, UNIX_TIMESTAMP(date_added) DIV $interval AS d, TIME(date_added) AS dOn")
			$data->selectRaw("COUNT(*) AS total, UNIX_TIMESTAMP(trViolationLog.createdOn) DIV $interval AS d, TIME(trViolationLog.createdOn) AS dOn")
				->groupBy(DB::RAW("d"));
		}

		if (!empty($userKey) && $userKey !== 'all') {
			$data->where("trViolationSession.userKey", $userKey);
		} else if (!empty($accountId)) {
			$data->join("userWebsites", function($join) use($accountId) {
				$join->on("trViolationSession.userKey", "=", "userWebsites.userKey")
					->where("userWebsites.accountId", "=", $accountId);
			});
		}

		if ($returnData)
		{
			$data = $data->get();
			return $data;
		}

		$data = $data->first();
		if (empty($data)) return 0;
		return $data->total;
	}


	/**
	 * fetches the live data we want to show to the live transactions graph widget
	 */
	public static function GetLiveTransactions($accountId=0, $userKey=null, $time)
	{
		/*
		- get the last log time for the given userkey (if no userkey just get the last log before $time)
		- get all the stats since that time up to now
		*/
		
		$lastTime = DB::table("trViolationLog")
			->join("trViolationSession", function($join) use($userKey) {
				$join->on("trViolationSession.id", "=", "trViolationLog.sessionId");
				if ($userKey !== null) $join->where("trViolationSession.userKey", "=", $userKey);
			})
			->where("trViolationLog.createdOn", "<", $time)
			->selectRaw("MAX(trViolationLog.createdOn) AS lastTime")
			->first();

		if (empty($lastTime)) {
			$lastTime = gmdate("Y-m-d H:i:s", strtotime("2 seconds ago"));
		} else {
			$lastTime = $lastTime->lastTime;
		}

		$data = DB::table("trViolationLog")
			->join("trViolationSession", "trViolationSession.id", "=", "trViolationLog.sessionId")
			->where("trViolationLog.createdOn", ">", $lastTime)
			->where("trViolationLog.createdOn", "<=", $time)
			->selectRaw("COUNT(*) AS total");

		if (!empty($accountId)) {
			$data->join("userWebsites", function($join) use($accountId) {
				$join->on("trViolationSession.userKey", "=", "userWebsites.userKey")
					->where("userWebsites.accountId", "=", $accountId);
			});
		}

		$data = $data->first();
		if (empty($data)) return 0;
		return $data->total;
	}

}
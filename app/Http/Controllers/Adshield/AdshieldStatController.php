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
class AdshieldStatController extends BaseController
{

	public function adShield()
	{
		if (Input::has('checkip'))
		{
			$result = $this->CheckIP();
			return response()->json($result);
		}
		else if (Input::has('lgadclk'))
		{
			//insert log
			$user_agent = urldecode(Input::get('userAgent', ''));
        	$referrer_url = urldecode(Input::get('refererUrl', ''));
        	$target_url = urldecode(Input::get('targetUrl', ''));
        	$sub_source = urldecode(Input::get('subsource', ''));
			$ad_type = Input::get('ad_type', null);
        	$status = Input::get('status', 0);
        	$userKey = Input::get('key', '');
			$result = $this->AddClickLog($user_agent, $referrer_url, $target_url, $sub_source, $ad_type, $status, $userKey);
			return response()->json(['id'=>$result[0], 'status'=>$result[1]]);
		}
		else if (Input::has('lgadclk_up'))
		{
			$id = Input::get('id', null);
			if ($id == null) exit(); //probably fraud/hack
			//set lgadclck to 1 (captcha was successfully completed)
			$this->successAdClickLog($id);
		}
		else if (Input::has('clklog_captcha'))
		{
			$response = Input::get("response", null);
			$result = $this->VerifyCaptcha($response);
			return response()->json($result);
		}
		else if (Input::has('logCaptcha'))
		{
			//log captcha
			$log_id = Input::get('log_id');
			$status = Input::get('status');
			$this->logCaptcha($log_id, $status);
		}

	}

	/**
	 * log the captcha status
	 * 0:failed, 1:passed, 2:showed, 3:cancelled
	 */
	private function logCaptcha($asLogAdClick_id, $status=2)
	{
		$result = DB::table('log_captcha')->insert(array(
			'asLogAdClick_id' => $asLogAdClick_id,
			'date_created' => date("Y-m-d H:i:s"),
			'status' => $status
		));
	}

	/**
	 * perform checking of ip from our IP list
	 * check if IP is whitelisted, greylisted, blacklisted
	 * 0:blacklist,1:whitelist,2:greylist
	 * otherwise return true
	 */
	private function CheckIP()
	{
		$ip = ApiStatController::GetIPBinary(true);
		$data = DB::table("asListIp")
			->where("ip", $ip[0])
			->first();

		$status = -1;
		if (!empty($data)) $status = $data->status;

		return ['ip'=>$ip[1], 'result'=>false, 'status'=>$status];

	}

	/**
	 * inserts log when user clicks on the ad SHIELD
	 * then checker will use this table for enabling or disabling ad shield for that ip
	 */
	private function AddClickLog(
		$user_agent='', $referrer_url='', $target_url='', 
		$sub_source='', $ad_type=null, $status=0, $userKey=''
	)
	{
		$ip = ApiStatController::GetIPBinary();
		$id = DB::table('asLogAdClick')->insertGetId([
			'ip' => $ip,
			'user_agent' => $user_agent,
			'referrer_url' => $referrer_url,
			'target_url' => $target_url,
			'sub_source' => $sub_source,
			'status' => $status,
			'ad_type' => $ad_type,
			'userKey' => $userKey
		]);

		if ($ad_type != null)
		{
			$status = $this->UpdateListIp($ip, $ad_type);
			if ($status === false) {
				//set the log status to -1 for ad_click
				DB::table('asLogAdClick')
					->where('id', $id)
					->update(['status' => -1]);
			}
		}
		else
		{
			//check/update list IP
			$status = $this->UpdateListIp($ip);
		}

		return [$id, $status];
	}

	private function successAdClickLog($id)
	{
		DB::table('asLogAdClick')
				->where('id', $id)
				->update(['status' => 1]);
		$this->whitelistIpById($id); //automatically whitelist this ip
	}

	private function VerifyCaptcha($response)
	{
		$url = "https://www.google.com/recaptcha/api/siteverify";
		$secret = '6LfgFhEUAAAAAMqP1RFwv8iFZs9YK-i9pGRrkcJJ';
		$result = ['success'=>false];
		$data = ['secret' => $secret, 'response'=>$response];
		$data = http_build_query($data);
		//verify response to google (POST Call)
		$params = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => 2,
				CURLOPT_POSTFIELDS => $data
		);

		$req = curl_init($url);
		curl_setopt_array($req, $params);
		$response = curl_exec($req);
		curl_close($req);
		$result = json_decode($response, 1);
		return $result;
	}

	/**
	 * performs the check if IP should be whitelist, blacklist, greylisted
	 * then updates the list IP record for this ip
	 */
	private function UpdateListIp($ip, $ad_type=null)
	{
		//prevent whitelisted IP's from getting automatically blocked/greylisted
		$data = DB::table("asListIp")->where("ip", $ip)->first();
		if (!empty($data))
		{
			if ($data->status == 1) return 1;
		}

		if ($ad_type != null)
		{
			//perform ad_type checking instead
			//status = -1 (ad clicks is within allowed limit)
			//status = 0 (ad click was over the allowed limit)
			$data = DB::select("SELECT COUNT(*) AS total, ad_type
					FROM asLogAdClick WHERE ip = ? AND (status = 0 OR status = -1)
						AND click_date > NOW() - INTERVAL 2 DAY
					GROUP BY ad_type", [$ip]);
			if (count($data) == 0) return false;
			$status = null;
			foreach($data as $d)
			{
				if ($d->total > 2 && $d->ad_type == 0)
				{
					// clicks > 2 on display ads for past 48 hours = greylist
					$status = 2;
					break;
				}
				else if ($d->total > 10 && $d->ad_type == 1)
				{
					// clicks > 10 on native ads for past 48 hours = greylist
					$status = 2;
					break;
				}
			}
			if ($status == null) return false;
		}
		else
		{
			//count IP from asLogAdClick (count status=1 and status=0)
			$data = DB::select("SELECT status, COUNT(*) AS total, MAX(click_date) AS last_date
					FROM asLogAdClick WHERE ip = ? AND ad_type IS NULL 
					GROUP BY status ORDER BY last_date DESC", 
				[$ip]);
			if (count($data) == 0) return false;
			$status = 0;
			if ($data[0]->status == 1)
			{
				$status = 1;    //whitelist
			}
			else if ($data[0]->total >= 10)
			{
				$status = 0;    //blacklist
			}
			else if ($data[0]->total >= 5)
			{
				$status = 2;    //greylist
			}
			else
			{
				return false;
			}
			//if status = 1 set ip to whitelist on asListIp
			//if status = 0 > 10 set to black list, if > 5 set to greylist
		}

		//for updating follow this :
		//check if ip exist on our list, if so, perform update on the IP
		//otherwise, create ip_list record with new status
		DB::statement("REPLACE INTO asListIp (ip, status) VALUES(?, ?)", [$ip, $status]);
		return $status;
	}

	/**
	 * shorthand method for whitelisting an ip
	 */
	private function whitelistIpById($id)
	{
		$data = DB::select("SELECT ip FROM asLogAdClick WHERE id = ?", [$id]);
		if (count($data) == 0) return;
		$ip = $data[0]->ip;
		DB::statement("REPLACE INTO asListIp (ip, status) VALUES (?, ?)", [$ip, 1]);
	}

	/**
	 * returns the status "word" value of the int status
	 */
	private function getIpStatus($i)
	{
		switch($i) {
				case 0: return "Blacklisted";
				case 1: return "Whitelisted";
				case 2: return "Greylisted";
				case 3: return "Unlisted";
				default: return "Unknown";
		}
	}


	private function getCaptchaStatus($status) {
		switch($status) {
			case 0: return 'Failed';
			case 1: return 'Passed';
			case 2: return 'Showed';
			case 3: return 'Cancelled';
			default: return "Unknown";
		}
	}


	public static function GetAdClicks($accountId, $userKey, $sinceWhen='')
	{
		$clicks = DB::table('asLogAdClick')
			->select(DB::raw('COUNT(*) AS total'))
			->where("click_date", ">=", $sinceWhen);

		if (!empty($userKey) && $userKey !== 'all') {
			$clicks->where("asLogAdClick.userKey", $userKey);
		} else if (!empty($accountId)) {
			$clicks->join("userWebsites", function($join) use($accountId) {
				$join->on("asLogAdClick.userKey", "=", "userWebsites.userKey")
					->where("userWebsites.accountId", "=", $accountId);
			});
		}

		$clicks = $clicks->first();
		if (empty($clicks)) return 0;
		return $clicks->total;
	}


	public static function GetPreviousTicks($userKey)
	{
		$timeAgo = 2 * floor(strtotime("2 minutes ago") / 2);
		$timeNow = 2 * floor(strtotime("NOW") / 2);

		$data = DB::table("trViolationLog")
			->join("trViolationSession", function($join) use($userKey) {
				$join->on("trViolationSession.id", "=", "trViolationLog.sessionId")
					->where("userKey", $userKey);
			})
			->selectRaw("COUNT(*) as total, 
				2 * FLOOR(UNIX_TIMESTAMP(trViolationLog.createdOn)/2) AS createdOn")
			->where("trViolationLog.createdOn", ">=", gmdate("Y-m-d H:i:s",$timeAgo))
			->groupBy("createdOn")
			->orderBy("createdOn")
			->get();

		$ticks = [];
		while($timeAgo < $timeNow)
		{
			$ticks[$timeAgo] = 0;
			$timeAgo += 2;
		}

		foreach($data as $d)
		{
			$ticks[$d->createdOn] = $d->total;
		}

		return $ticks;
	}

}

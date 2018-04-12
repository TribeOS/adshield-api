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
			$result = $this->AddClickLog($user_agent, $referrer_url, $target_url, $sub_source, $ad_type, $status);
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

	private function fetchPage()
	{
		$from = Input::get("date_from", date("Y-m-1"));
		$to = Input::get("date_to", date("Y-m-d"));

		$tz = Input::get("tz", "America/New_York");
		$ret = TimeZoneController::convertFromToUtc($from, $to, $tz);
		$from = $ret[0];
		$to = $ret[1];

		$offset = Input::get('start', 0);

		$limit = Input::get('length', 10);
		$search = Input::get('search')['value'];

		//$filter
		$filter = Input::get('filter', []);

		//sorting
		$order = Input::get('columns')[Input::get('order')[0]['column']]['data'] . " " . Input::get('order')[0]['dir'];

		$grouped = Input::get("group", false);

		if ($grouped) {
			$data = $this->fetchDataGrouped($from, $to, $offset, $limit, $filter, $order);
		} else {
			$data = $this->fetchData($from, $to, $offset, $limit, $filter, $order);
		}

		//post process data
		$final_data = array();

		if ($grouped)
		{
			$mediums = array(); //holds the existing mediums parsed out (for GROUP)
			foreach($data[2] as $d)
			{
				$d->ip = ApiStatController::IPFromBinaryString($d->ip);
				$final_data[] = $d; 
			}
		}
		else
		{
			foreach($data[2] as $d)
			{
				$d->ip = ApiStatController::IPFromBinaryString($d->ip);
				if ($filter['log_source'] == 'revcontent')
				{
					//revcontent. parse out the utm_medium values from referrer url
					$med = explode("utm_medium=", $d->referrer_url);
					if (count($med) > 1) {
						$med = explode("&", $med[1])[0];
					} else {
						$med = "";
					}
				}
				else
				{
					//distil
				}
				$d->utm_medium = $med;
				$final_data[] = $d;
			}
		}

		return array(
			'draw' => Input::get('draw', 0),
			'recordsTotal' => $data[0],
			'recordsFiltered' => $data[1],
			'data' => $final_data
		);

	}

	private function fetchData($from, $to, $offset, $limit, $search, $order) {
		$total_rows = DB::select("SELECT COUNT(*) AS total FROM log_revenue");
		$total_rows = $total_rows[0]->total;

		// $from = date("Y-m-d 00:00:00", strtotime($from));
		// $to = date("Y-m-d 23:59:59", strtotime($to));

		$searchParams = [$from, $to];
		$where = array();
		foreach($search as $i=>$s) {
			if ($i == 'log_source') {
				$where[] = "$i = ?";
				$searchParams[] = $s;
			} else if ($i == 'ip') {
				$where[] = "$i = ?";
				$searchParams[] = inet_pton($s);
			} else {
				$where[] = "$i LIKE ?";
				$searchParams[] = '%' . $s . '%';
			}
		}
		$whereSql = "1";
		if (count($where) > 0) $whereSql = implode(" AND ", $where);

		$current_rows = DB::select("SELECT 1
				FROM log_revenue
				WHERE click_date BETWEEN ? AND ?
				AND $whereSql", $searchParams);

				if (count($current_rows) > 0) {
					$current_rows = count($current_rows);
				} else {
					$current_rows = 0;
				}

		if (!empty($order)) $order = "ORDER BY " . $order;

		$data = DB::select("SELECT CONVERT_TZ(click_date, 'UTC', 'EST') as click_date, ip, utm_medium, referrer_url, 1 AS total
				FROM log_revenue
				WHERE click_date BETWEEN ? AND ?
				AND $whereSql
				$order
				LIMIT $offset, $limit", $searchParams);

		return [$total_rows, $current_rows, $data];
	}


	//fetch grouped values by ip and referrer url
	private function fetchDataGrouped($from, $to, $offset, $limit, $search, $order)
	{		
		$searchParams = [$from, $to];
		$where = array();
		foreach($search as $i=>$s)
		{
			if ($i == 'log_source')
			{
				$where[] = "$i = ?";
				$searchParams[] = $s;
			}
			else if ($i == 'ip')
			{
				$where[] = "$i = ?";
				$searchParams[] = inet_pton($s);
			}
			else
			{
				$where[] = "$i LIKE ?";
				$searchParams[] = '%' . $s . '%';
			}
		}

		$whereSql = "1";
		if (count($where) > 0) $whereSql = implode(" AND ", $where);

		$data = DB::select("SELECT * FROM 
			(
				SELECT ip, 
					IF (SUBSTRING_INDEX(SUBSTRING_INDEX(referrer_url, 'utm_medium=', -1), '&', 1) = referrer_url, 
						IF(SUBSTRING_INDEX(SUBSTRING_INDEX(impression_url, 'utm_medium=', -1), '&', 1) = impression_url, 
							'', SUBSTRING_INDEX(SUBSTRING_INDEX(impression_url, 'utm_medium=', -1), '&', 1)), 
							SUBSTRING_INDEX(SUBSTRING_INDEX(referrer_url, 'utm_medium=', -1), '&', 1)) as medium,
				COUNT(*) AS total
					FROM log_revenue
					WHERE click_date BETWEEN ? AND ?
					AND $whereSql
					GROUP BY ip, medium
			) AS x 
			-- WHERE x.total > 3
			LIMIT $offset, $limit", $searchParams);

		return [0, 0, $data];
	}

	/**
	 * perform checking of ip from our IP list
	 * check if IP is whitelisted, greylisted, blacklisted
	 * otherwise return true
	 */
	private function CheckIP()
	{
		$ip = ApiStatController::GetIPBinary();
		$data = DB::table("asListIp")
			->where("ip", $ip)
			->first();

		$status = -1;
		if (empty($data)) {
			$status = -1;
		} else {
			$status = $data->status;
		}

		return ['ip'=>$ip, 'result'=>false, 'status'=>$status];

	}

	/**
	 * inserts log when user clicks on the ad SHIELD
	 * then checker will use this table for enabling or disabling ad shield for that ip
	 */
	private function AddClickLog(
		$user_agent='', $referrer_url='', $target_url='', 
		$sub_source='', $ad_type=null, $status=0
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
					'ad_type' => $ad_type
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
	 * handles the viewing of logs
	 */
	public function showAdshieldLogs()
	{
		if (Input::has("view"))
			{
				$from = Input::get("date_from", date("Y-m-1"));
				$to = Input::get("date_to", date("Y-m-d"));
				$offset = Input::get('start', 0);
				$limit = Input::get('length', 10);
				$filter = Input::get('filter', []);

				$tz = Input::get("tz", "America/New_York");
				$ret = TimeZoneController::convertFromToUtc($from, $to, $tz);
				$from = $ret[0];
				$to = $ret[1];

				//sorting
				$order = Input::get('columns')[Input::get('order')[0]['column']]['data'] . " " . Input::get('order')[0]['dir'];
				//show main logs. json
				$data = $this->fetchAdshieldLogs($from, $to, $offset, $limit, $filter, $order);
				return array(
						'draw' => Input::get('draw', 0),
						'recordsTotal' => $data[0],
						'recordsFiltered' => $data[1],
						'data' => $data[2]
				);
		}
		else if (Input::has("download"))
		{
				$from = Input::get("date_from", date("Y-m-1"));
				$to = Input::get("date_to", date("Y-m-d"));
				$offset = Input::get('start', 0);
				$limit = Input::get('length', 10);
				$filter = Input::get('filter', []);

				$tz = Input::get("tz", "America/New_York");
				$ret = TimeZoneController::convertFromToUtc($from, $to, $tz);
				$from = $ret[0];
				$to = $ret[1];

				//show main logs. json
				$data = $this->fetchAdshieldLogs($from, $to, $offset, $limit, $filter, null);
				return array(
					'data' => $data[2]
				);
		}

		return View::make('admin/reports/adshield');
	}


	private function fetchAdshieldLogs($from, $to, $offset, $limit, $search, $order)
	{
		// $total_rows = DB::select("SELECT COUNT(*) AS total FROM asListIp");
		// $total_rows = $total_rows[0]->total;
		$total_rows = DB::select("
			SELECT 1
			FROM asLogAdClick AS y LEFT JOIN asListIp AS x ON x.ip = y.ip
			WHERE click_date BETWEEN ? AND ?
			GROUP BY y.ip, sub_source
		");
		$total_rows = count($total_rows);

		// $from = date("Y-m-d 00:00:00", strtotime($from));
		// $to = date("Y-m-d 23:59:59", strtotime($to));

		$searchParams = [$from, $to];
		$where = array();
		foreach($search as $i=>$s) {
				if ($i == 'ip') {
					$where[] = "x.ip = ?";
					$searchParams[] = inet_pton(trim($s));
				} else if ($i == 'status') {
					$where[] = "x.status = ?";
					$searchParams[] = trim($s);
				} else {
					$where[] = "$i LIKE ?";
					$searchParams[] = '%' . trim($s) . '%';
				}
		}
		$whereSql = "1";
		if (count($where) > 0) $whereSql = implode(" AND ", $where);

		// $sql = "
		// 		SELECT x.ip, x.status, y.sub_source, count(y.ip) as total
		// 		FROM asListIp as x
		// 		LEFT JOIN (
		// 				SELECT click_date, ip, sub_source 
		// 				FROM asLogAdClick
		// 				where click_date BETWEEN ? AND ?
		// 				) AS y ON x.ip = y.ip 
		// 		WHERE $whereSql
		// 		GROUP BY x.ip, y.sub_source
		// ";
		$sql = "
			SELECT 1
			FROM asLogAdClick AS y
			LEFT JOIN asListIp AS x ON x.ip = y.ip
			WHERE click_date BETWEEN ? AND ? AND $whereSql
			GROUP BY y.ip, sub_source
		";

		$current_rows = DB::select("$sql", $searchParams);

		if (count($current_rows) > 0) {
				$current_rows = count($current_rows);
		} else {
				$current_rows = 0;
		}

		if (!empty($order)) $order = "ORDER BY " . $order;

		$sql = "
			SELECT click_date, y.ip, sub_source, IF(x.status IS NULL, 3, x.status) AS status, count(*) as total
			FROM asLogAdClick AS y
			LEFT JOIN asListIp AS x ON x.ip = y.ip
			WHERE click_date BETWEEN ? AND ? AND $whereSql
			GROUP BY y.ip, sub_source";

		$data = DB::select("$sql $order LIMIT $offset, $limit", $searchParams);

		//convert ip's
		foreach($data as $d) {
				$d->ip = ApiStatController::IPFromBinaryString($d->ip);
				$d->status = $this->getIpStatus($d->status);
		}

		return [$total_rows, $current_rows, $data];
	}

	/**
	 * returns the IP List data (for datatable) on frontend
	 * also handles the download request
	 */
	public function showIpList()
	{
		if (Input::has("view")) {
				$offset = Input::get('start', 0);
				$limit = Input::get('length', 10);
				$filter = Input::get('filter', []);
				//sorting
				$order = Input::get('columns')[Input::get('order')[0]['column']]['data'] . " " . Input::get('order')[0]['dir'];
				//show main logs. json
				$data = $this->fetchIpList($offset, $limit, $filter, $order);
				return array(
						'draw' => Input::get('draw', 0),
						'recordsTotal' => $data[0],
						'recordsFiltered' => $data[1],
						'data' => $data[2]
				);
		} else if (Input::has("download")) {
				$offset = Input::get('start', 0);
				$limit = Input::get('length', 10);
				$filter = Input::get('filter', []);
				//show main logs. json
				$data = $this->fetchAdshieldLogs($from, $to, $offset, $limit, $filter, null);
				return array(
					'data' => $data[2]
				);
		}

		return View::make('admin/reports/revenue');

	}

	/**
	 * performs the query for fetching IP list
	 */
	private function fetchIpList($offset, $limit, $search, $order)
	{

			$total_rows = DB::select("SELECT COUNT(*) AS total FROM asListIp");
			$total_rows = $total_rows[0]->total;

			$searchParams = array();
			$where = array();
			foreach($search as $i=>$s) {
					if ($i == 'ip') {
						$where[] = "ip = ?";
						$searchParams[] = inet_pton(trim($s));
					} else if ($i == 'status') {
						$where[] = "status = ?";
						$searchParams[] = trim($s);
					} else {
						$where[] = "$i LIKE ?";
						$searchParams[] = '%' . trim($s) . '%';
					}
			}
			$whereSql = "1";
			if (count($where) > 0) $whereSql = implode(" AND ", $where);

			$sql = "
				SELECT ip, status
				FROM asListIp
				WHERE $whereSql
			";

			$current_rows = DB::select("$sql", $searchParams);

			if (count($current_rows) > 0) {
					$current_rows = count($current_rows);
			} else {
					$current_rows = 0;
			}

			if (!empty($order)) $order = "ORDER BY " . $order;

			$data = DB::select("$sql $order LIMIT $offset, $limit", $searchParams);

			//convert ip's
			foreach($data as $d) {
					$d->ip = ApiStatController::IPFromBinaryString($d->ip);
					$d->status = $this->getIpStatus($d->status);
			}

			return [$total_rows, $current_rows, $data];

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

}

<?php

namespace App\Http\Controllers\Adshield\Protection;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;


class SafeBrowsingCheckController extends BaseController
{

	const API_KEY = 'AIzaSyCK5yNHdQf2QodfVVaOM5ifyp3odr1OOvY';

	public function PerformRecheck()
	{
		$batch = 450;
		$this->DoRecheck(0, $batch);
	}

	private function DoRecheck($offset=0, $limit=450)
	{
		$domains = $this->GetDomains($offset, $limit);
		$list = array();
		foreach($domains as $domain) $list[] = array(['url' => $domain->url]);
		if (count($list)  == 0)
		{
			return;
		}
		else
		{
			$this->BatchSafeBrowsingcheck($list);
			$this->DoRecheck($offset + $limit, $limit);
		}
	}


	private function GetDomains($offset, $limit)
	{
		$domains = DB::table("asUrlFilter")
			->where("status", 1)
			->orderBy("id")
			->skip($offset)->take($limit)
			->get();
		return $domains;
	}

	private function SetUrlStatus($hash, $status)
	{
		DB::table("asUrlFilter")
			->update([
				'status' => $status,
				'last_updated' => gmdate("Y-m-d H:i:s")
			])
			->where("hash", $hash);
	}

	public function BatchSafeBrowsingcheck($urls)
	{
		$api_key = self::API_KEY;	//our api key for safebrowsing admin@tr.be
		$gsb_url = "https://safebrowsing.googleapis.com/v4/threatMatches:find?key=$api_key";
		$data = [
			'client' => [
				'clientId' => 'share.cat',
				"clientVersion" => "1.5.2"
			],
			'threatInfo' => [
				'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'POTENTIALLY_HARMFUL_APPLICATION', 'UNWANTED_SOFTWARE'],
				'platformTypes' => ['ANY_PLATFORM'],
				'threatEntryTypes' => ['URL'],
				'threatEntries' => $urls
			]
		];

		$data = json_encode($data);

		$params = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $data,
			CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                'Content-Length: ' . strlen($data)
            ]
		];

		$req = curl_init($gsb_url);
		curl_setopt_array($req, $params);
		$response = curl_exec($req);
		$response = json_decode($response, 1);

		//if response is blank/empty we have nothing to process
		if (empty($response) || count($response) == 0) return;

		if (!isset($response['matches'])) return;

		$response = $response['matches'];
		foreach($response as $res)
		{
			$tmp = $res['threat']['url'];
			$tmp = str_replace(array('http://', 'https://'), '', $tmp);
			$hash = md5($tmp);
			$this->SetUrlStatus($hash, 0);
		}
	}

}

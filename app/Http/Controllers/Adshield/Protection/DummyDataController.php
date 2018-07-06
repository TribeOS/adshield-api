<?php

namespace App\Http\Controllers\Adshield\Protection;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

date_default_timezone_set("America/New_York");


class DummyDataController extends BaseController
{


	public static function GetIps($limit, $page)
	{
		$ips = [
			['ip' => '67.197.148.127', 'total' => 112],
			['ip' => '24.107.198.190', 'total' => 12],
			['ip' => '69.76.60.76', 'total' => 119],
			['ip' => '50.37.77.29', 'total' => 12],
			['ip' => '68.98.121.115', 'total' => 11],
			['ip' => '68.53.8.86', 'total' => 111],
			['ip' => '45.19.109.15', 'total' => 116],
			['ip' => '68.115.3.49', 'total' => 13],
			['ip' => '68.13.116.36', 'total' => 11],
			['ip' => '69.123.62.30', 'total' => 15],

			['ip' => '168.170.3.30', 'total' => 123],
			['ip' => '63.166.62.68', 'total' => 11],
			['ip' => '81.62.41.103', 'total' => 121],
			['ip' => '197.99.220.205', 'total' => 18],
			['ip' => '94.29.228.74', 'total' => 12],
			['ip' => '69.234.85.233', 'total' => 17],
			['ip' => '138.182.129.138', 'total' => 19],
			['ip' => '163.153.18.12', 'total' => 117],
			['ip' => '2.162.185.152', 'total' => 19],
			['ip' => '219.61.18.25', 'total' => 13],
			['ip' => '51.88.193.113', 'total' => 118],
			['ip' => '55.69.238.25', 'total' => 18],

		];

		$filter = Input::get('filter', []);
		$duration = $filter['duration'] / 100;

		$data['data'] = [];
		$data['total'] = count($ips);
		$data['current_page'] = $page;
		$data['last_page'] = ceil(count($ips) / $limit);
		$start = ($data['current_page'] - 1) * $limit;
		for($a = $start; $a < $start + $limit; $a ++) {
			if (!isset($ips[$a])) break;
			if ($duration > 0) $ips[$a]['total'] = floor($ips[$a]['total'] * $duration);
			$data['data'][] = $ips[$a];
		}

		return $data;
	}


	public static function IpGetGraphData($columns=1, $duration=0)
	{
		function generateData($ip, $vs) {
			return [
				'violations' => $vs, 
				'info' => ['ip' => $ip]
			];
		}

		$data = [];
		if ($columns == 1) {
			$data = [
				'67.197.148.127' =>  generateData('67.197.148.127', [112]),
				'24.107.198.190' =>  generateData('24.107.198.190', [12]),
				'69.76.60.76' =>  generateData('69.76.60.76', [119]),
				'50.37.77.29' =>  generateData('50.37.77.29', [12]),
				'68.98.121.115' =>  generateData('68.98.121.115', [11]),
				'68.53.8.86' =>  generateData('68.53.8.86', [111]),
				'45.19.109.15' =>  generateData('45.19.109.15', [116]),
				'68.115.3.49' =>  generateData('68.115.3.49', [13]),
				'68.13.116.36' =>  generateData('68.13.116.36', [11]),
				'69.123.62.30' =>  generateData('69.123.62.30', [15]),

				'168.170.3.30' => generateData('168.170.3.30', [123]),
				'63.166.62.68' => generateData('63.166.62.68', [11]),
				'81.62.41.103' => generateData('81.62.41.103', [121]),
				'197.99.220.205' => generateData('197.99.220.205', [18]),
				'94.29.228.74' => generateData('94.29.228.74', [12]),
				'69.234.85.233' => generateData('69.234.85.233', [17]),
				'138.182.129.138' => generateData('138.182.129.138', [19]),
				'163.153.18.12' => generateData('163.153.18.12', [117]),
				'2.162.185.152' => generateData('2.162.185.152', [19]),
				'219.61.18.25' => generateData('219.61.18.25', [13]),
				'51.88.193.113' => generateData('51.88.193.113', [118]),
				'55.69.238.25' => generateData('55.69.238.25', [18]),
			];
		} else if ($columns == 2) {
			$data = [
				'67.197.148.127' =>  generateData('67.197.148.127', [112,80]),
				'24.107.198.190' =>  generateData('24.107.198.190', [12, 43]),
				'69.76.60.76' =>  generateData('69.76.60.76', [119, 32]),
				'50.37.77.29' =>  generateData('50.37.77.29', [12, 99]),
				'68.98.121.115' =>  generateData('68.98.121.115', [11, 20]),
				'68.53.8.86' =>  generateData('68.53.8.86', [111, 90]),
				'45.19.109.15' =>  generateData('45.19.109.15', [116, 77]),
				'68.115.3.49' =>  generateData('68.115.3.49', [13, 90]),
				'68.13.116.36' =>  generateData('68.13.116.36', [11, 32]),
				'69.123.62.30' =>  generateData('69.123.62.30', [15, 12]),

				'168.170.3.30' => generateData('168.170.3.30', [123, 67]),
				'63.166.62.68' => generateData('63.166.62.68', [11, 23]),
				'81.62.41.103' => generateData('81.62.41.103', [121, 74]),
				'197.99.220.205' => generateData('197.99.220.205', [18, 3]),
				'94.29.228.74' => generateData('94.29.228.74', [12, 17]),
				'69.234.85.233' => generateData('69.234.85.233', [17, 1]),
				'138.182.129.138' => generateData('138.182.129.138', [19, 39]),
				'163.153.18.12' => generateData('163.153.18.12', [117, 29]),
				'2.162.185.152' => generateData('2.162.185.152', [19, 61]),
				'219.61.18.25' => generateData('219.61.18.25', [13, 39]),
				'51.88.193.113' => generateData('51.88.193.113', [118, 24]),
				'55.69.238.25' => generateData('55.69.238.25', [18, 7]),
			];
		} else if ($columns == 3) {
			$data = [
				'67.197.148.127' =>  generateData('67.197.148.127', [53, 65, 34]),
				'24.107.198.190' =>  generateData('24.107.198.190', [31, 51, 34]),
				'69.76.60.76' =>  generateData('69.76.60.76', [72, 12, 34]),
				'50.37.77.29' =>  generateData('50.37.77.29', [91, 51, 34]),
				'68.98.121.115' =>  generateData('68.98.121.115', [51, 73, 34]),
				'68.53.8.86' =>  generateData('68.53.8.86', [34, 97]),
				'45.19.109.15' =>  generateData('45.19.109.15', [22, 154, 34]),
				'68.115.3.49' =>  generateData('68.115.3.49', [25, 61, 34]),
				'68.13.116.36' =>  generateData('68.13.116.36', [54, 52, 34]),
				'69.123.62.30' =>  generateData('69.123.62.30', [37, 26, 34]),

				'168.170.3.30' => generateData('168.170.3.30', [72, 67, 26]),
				'63.166.62.68' => generateData('63.166.62.68', [79, 23, 50]),
				'81.62.41.103' => generateData('81.62.41.103', [22, 74, 47]),
				'197.99.220.205' => generateData('197.99.220.205', [51, 3, 34]),
				'94.29.228.74' => generateData('94.29.228.74', [44, 17, 33]),
				'69.234.85.233' => generateData('69.234.85.233', [70, 1, 14]),
				'138.182.129.138' => generateData('138.182.129.138', [55, 39, 6]),
				'163.153.18.12' => generateData('163.153.18.12', [61, 29, 6]),
				'2.162.185.152' => generateData('2.162.185.152', [67, 61, 38]),
				'219.61.18.25' => generateData('219.61.18.25', [22, 39, 14]),
				'51.88.193.113' => generateData('51.88.193.113', [51, 24, 3]),
				'55.69.238.25' => generateData('55.69.238.25', [33, 7, 65]),
			];
		}


		return $data;

	}
	

	public static function GetDurationMultiplier()
	{
		$filter = Input::get('filter', []);
		$duration = $filter['duration'] / 100;
		return $duration;
	}

	public static function ApplyDuration($gd)
	{
		$duration = self::GetDurationMultiplier();

		if (is_numeric($gd)) {
			if ($duration > 0) $gd = ceil($gd * $duration);
			return number_format($gd);
		}
		
		foreach($gd['data'] as $gdKey=>$gdata) {
			if ($duration > 0) $gd['data'][$gdKey] = number_format(ceil($gd['data'][$gdKey] * $duration));
		}
		return $gd;
	}


}

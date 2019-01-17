<?php

namespace App\Http\Controllers\Adshield\TrafficSummary;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Request;

use App\Http\Controllers\Adshield\Protection\DummyDataController;
use App\Model\UserWebsite;
use App\Http\Controllers\Adshield\Violations\ViolationController;
use App\Http\Controllers\Adshield\Violations\ResponseController;

/**
 * handle the backend for the page "traffic-summary"
 */
class TrafficSummaryController extends BaseController
{

	const labels = [
		ViolationController::V_KNOWN_VIOLATOR => 'Known Violator',
		ViolationController::V_NO_JS => 'JS not loaded',
		ViolationController::V_JS_CHECK_FAILED => 'JS Check Failed',
		ViolationController::V_KNOWN_VIOLATOR_UA => 'Known Violator User Agent',
		ViolationController::V_SUSPICIOUS_UA => 'Suspicious User Agent',
		ViolationController::V_BROWSER_INTEGRITY => 'Browser Integrity Failed',
		ViolationController::V_KNOWN_DC => 'Known Data Center IP',
		ViolationController::V_PAGES_PER_MINUTE_EXCEED => 'Pages Per Minute Exceed',
		ViolationController::V_PAGES_PER_SESSION_EXCEED => 'Pages Per Session Exceed',
		ViolationController::V_BLOCKED_COUNTRY => 'Blocked Country',
		ViolationController::V_AGGREGATOR_UA => 'Aggregator User Agent',
		ViolationController::V_KNOWN_VIOLATOR_AUTO_TOOL => 'Known Violator Automation Tool',
		ViolationController::V_SESSION_LENGTH_EXCEED => 'Session Length Exceed',
		ViolationController::V_BAD_UA => 'Bad User Agent',
		ViolationController::V_UNCLASSIFIED_UA => 'Unclassified User Agent',
		ViolationController::V_IS_BOT => 'Bot',
	];

	public function getData()
	{

		$filter = Request::get("filter", []);

		$data = [
			'threatResponseProtocolsUsed' => $this->getThreatResponseProtocolsUsed($filter),
			'threatsAverted' => $this->getThreatsAverted($filter),
			'trafficGraph' => $this->getTrafficGraph($filter)
		];

		return response()->json(['id'=>0, 'pageData' => $data])
			->header('Content-Type', 'application/vnd.api+json');
	}

	private function getThreatResponseProtocolsUsed($filter)
	{
		$duration = $filter['duration'];

		$data = DB::table('trViolations')
			->join("trViolationResponses", "trViolationResponses.violationId", "=", "trViolations.id")
			->where('userKey', $filter['userKey'])
			->selectRaw("responseTaken, COUNT(*) AS total")
			->groupBy('responseTaken');

		if (!empty($duration) && $duration > 0)
		{
			$data->where("trViolations.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->get();
		$graphData = ['data' => [], 'label' => []];
		$labels = [
			ResponseController::RP_BLOCKED => 'Blocked',
			ResponseController::RP_CAPTCHA => 'Captcha',
			ResponseController::RP_ALLOWED => 'Allowed',
		];
		foreach($data as $d)
		{
			$graphData['data'][] = $d->total;
			$graphData['label'][] = $labels[$d->responseTaken];
		}

		return $graphData;
	}

	private function getThreatsAverted($filter)
	{
		$data = DB::table('trViolations')
			->join("trViolationResponses", "trViolationResponses.violationId", "=", "trViolations.id")
			->where('userKey', $filter['userKey'])
			->selectRaw("violation, COUNT(*) AS total")
			->groupBy('violation');

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("trViolations.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->get();
		$graphData = ['data' => [], 'label' => []];
		foreach($data as $d)
		{
			$graphData['data'][] = $d->total;
			$graphData['label'][] = self::labels[$d->violation];
		}

		return $graphData;
	}

	private function getTrafficGraph1($days)
	{
		$data = [];
		$data['datasets'][] = [
			'data' => [39, 48, 74, 70, 87, 34, 18],
			'label' => 'Humans'
		];
		$data['datasets'][] = [
			'data' => [46, 56, 65, 23, 34, 32, 13],
			'label' => 'Good Bots'
		];
		$data['label'] = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];

		foreach($data['datasets'] as $index=>$ds) {
			$data['datasets'][$index] = DummyDataController::ApplyDuration($data['datasets'][$index]);
		}

		return $data;
	}


	private function getTrafficGraph($filter)
	{
		$limit = Request::get('limit', 10);
		$page = Request::get('page', 10);
		$duration = $filter['duration'];

		$data = DB::table("trViolationLog")
			->join("trViolationSession", function($join) use($filter, $duration) {
				$join->on("trViolationSession.id", "=", "trViolationLog.sessionId")
					->where("trViolationSession.userKey", $filter['userKey']);
				if ($duration > 0) $join->where("trViolationLog.createdOn", ">", gmdate("Y-m-d H:i:s", strtotime("$duration DAYS AGO")));
			})
			->leftJoin("trViolations", function($join) use($filter) {
				$join->on("trViolations.ip", "=", "trViolationSession.ip")
					->on("trViolations.createdOn", "=", "trViolationLog.createdOn")
					->on("trViolations.userKey", "=", "trViolationLog.userKey");
			});

		if ($duration > 0) {
			$data->selectRaw("trViolations.violation, DATE(trViolationLog.createdOn) AS marker, COUNT(*) AS noRequests");
		} else {
			$data->selectRaw("trViolations.violation, YEAR(trViolationLog.createdOn) AS marker, COUNT(*) AS noRequests");
		}
		$data = $data->groupBy("trViolations.violation", "marker")
			->orderBy("trViolations.violation", "marker")
			->get();

		$graph = [
			'datasets' => [],
			'label' => []
		];

		$defaultData = [];

		$site = UserWebsite::where("userKey", $filter['userKey'])->first();

		if ($duration > 0) {
			for($a = 0; $a < $duration; $a ++) {
				$d = date("Y-m-d", strtotime(($duration - $a) . " days ago"));
				$graph['label'][] = $d;
				$defaultData[$d] = 0;
			}
		} else {
			$start = date("Y", strtotime($site->createdOn));
			for($a = $start; $a <= date("Y"); $a ++) {
				$graph['label'][] = $a;
				$defaultData[$a] = 0;
			}
		}

		$graphData = [
			'human' => $defaultData,
			'desiredAutomaticTraffic' => $defaultData,
			'unwantedAutomaticTraffic' => $defaultData
		];

		$labels = [
			'human' => 'Human',
			'desiredAutomaticTraffic' => 'Desirable Automatic Traffic',
			'unwantedAutomaticTraffic' => 'Unwanted Automatic Traffic'
		];

		$previousName = ''; $index = '';
		foreach($data as $record)
		{

			switch($record->violation == null)
			{
				//undesirable auto traffic
				case ViolationController::V_AGGREGATOR_UA:
				case ViolationController::V_UNCLASSIFIED_UA:	
					$index = 'desiredAutomaticTraffic';
					break;
				//undesirable
				case ViolationController::V_KNOWN_VIOLATOR:
				case ViolationController::V_NO_JS:
				case ViolationController::V_JS_CHECK_FAILED:
				case ViolationController::V_KNOWN_VIOLATOR_UA:
				case ViolationController::V_SUSPICIOUS_UA:
				case ViolationController::V_BROWSER_INTEGRITY:
				case ViolationController::V_KNOWN_DC:
				case ViolationController::V_PAGES_PER_MINUTE_EXCEED:
				case ViolationController::V_PAGES_PER_SESSION_EXCEED:
				case ViolationController::V_BLOCKED_COUNTRY:
				case ViolationController::V_KNOWN_VIOLATOR_AUTO_TOOL:
				case ViolationController::V_SESSION_LENGTH_EXCEED:
				case ViolationController::V_BAD_UA:
				case ViolationController::V_IS_BOT:
					$index = 'unwantedAutomaticTraffic';
					break;
				default:
					//human
					$index = 'human';
			}
			if (isset($graphData[$index][$record->marker])) {
				$graphData[$index][$record->marker] += $record->noRequests;
			} else {
				$graphData[$index][$record->marker] = $record->noRequests;
			}

		}

		foreach($graphData as $index=>$gData)
		{
			$graph['datasets'][] = [];
			$graph['datasets'][count($graph['datasets']) - 1]['data'] = array_values($gData);
			$graph['datasets'][count($graph['datasets']) - 1]['label'] = $labels[$index];
			
		}

		return $graph;
	}

	
}

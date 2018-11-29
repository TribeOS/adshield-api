<?php

namespace App\Http\Controllers\Adshield;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Config;

use App\Model\SystemLog;

class LogController extends BaseController
{

	const ACT_VIEW_REPORT = 'VIEW_REPORT';
	const ACT_VIEW_GRAPH_IP = 'VIEW_GRAPH_FOR_IP';
	const ACT_LOG_IN = 'LOG_IN';
	const ACT_LOG_OUT = 'LOG_OUT';
	const ACT_SAVE_SETTINGS = 'SAVE_SETTINGS';


	public function handle(Request $request)
	{

		$userId = 0;
        try {
            $token = $request->bearerToken();
            $user = LoginController::getUserIdFromToken($token, true); //get USER instead of just the id
        } catch (Exception $e) {
        	die($e->getMessage());
        }

		if ($request->isMethod('get'))
		{
			return $this->getList($user);
		}
	}


	/**
	 * Gets list of logs from the database,
	 * this data is paginated, and should be interpreted accordingly in the frontend
	 * @return [type] [description]
	 */
	private function getList($user)
	{

		$page = Input::get('page', 0);
		$limit = Input::get('limit', 10);
		$filter = Input::get('filter', []);

		$data = DB::table("systemLog")
			->join("users", "users.id", "=", "systemLog.userId")
			->selectRaw("systemLog.*, username")
			->where("systemLog.accountId", "=", $user->accountId)
			->orderBy('systemLog.createdOn', 'desc');

		if (!empty($filter['duration']) && $filter['duration'] > 0)
		{
			$duration = $filter['duration'];
			$data->where("systemLog.createdOn", ">=", gmdate("Y-m-d 0:0:0", strtotime("$duration DAYS AGO")));
		}

		$data = $data->paginate($limit);

		return response()->json(['id' => 0, 'listData' => $data]);

	}


	/**
	 * creates a system log record
	 * this would be called from the other class/objects as the event occurs
	 * @param [type] $action  [description]
	 * @param [type] $details [description]
	 * @param [type] $userId  [description]
	 * @param [type] $userKey [description]
	 */
	public static function Log($userId, $accountId, $action, $details)
	{
		$log = new SystemLog();
		$log->action = $action;
		$log->details = $details;
		$log->userId = $userId;
		$log->createdOn = gmdate("Y-m-d H:i:s");
		$log->accountId = $accountId;
		$log->save();
	}

	/**
	 * shorthand for Log() using the Config object to get the user details
	 */
	public static function QuickLog($action, $info=[])
	{

		$details = '';
		switch($action)
		{
			case self::ACT_VIEW_REPORT:
				$details = $info['title'] . ' for ' . $info['userKey'];
				if (isset($info['page'])) $details .= ', Page : ' . $info['page'];
				break;
			case self::ACT_VIEW_GRAPH_IP:
				$details = $info['title'] . ' Graph for ' . $info['userKey'] . ', IP : ' . $info['ip'];
				break;
			case self::ACT_SAVE_SETTINGS:
				$details = $info['title'] . ' Config';
				break;
		}

		try {
			$user = Config::get('user', []);
			self::Log($user->userId, $user->accountId, $action, $details);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

}

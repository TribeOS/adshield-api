<?php

namespace App\Http\Controllers\Adshield\Settings;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

use App\Http\Controllers\Adshield\LoginController;
use App\Model\Account;

use App\Http\Controllers\Adshield\LogController;

use App\Http\Controllers\Adshield\Misc\NotificationController;


/**
 * handles setting and fetching of account config
 */
class AccountManagementController extends BaseController
{

	public function handle(Request $request)
	{
		$userId = 0;
        try {
            $token = $request->bearerToken();
            $user = LoginController::getUserIdFromToken($token, true);
        } catch (Exception $e) {}

		if ($request->isMethod('get'))
		{
			return $this->getSettings($user->accountId);
		}
		else if ($request->isMethod('put'))
		{
			return $this->saveSettings($user);
		}
	}


	private function getSettings($accountId)
	{
		$account = Account::where('id', $accountId)->first();
		$data = [
			'account' => [
				'company' => '',
				'address' => ''
			],
			'emailNotifications' => [],
			'password' => []
		];

		if (!empty($account) && !empty($account->config)) $data = json_decode($account->config);;

		return response()->json(['id'=>1, 'pageData' => $data]);

	}


	private function saveSettings($user)
	{
		$settings = Input::get('accountManagement', []);
		$settings = $settings['pageData'];
		$account = Account::find($user->accountId);
		$account->config = json_encode($settings);
		$account->save();

		LogController::QuickLog(LogController::ACT_SAVE_SETTINGS, [
			'title' => 'Account Management'
		]);

		NotificationController::CreateAndSendSettings(
			$user->username,
			'Account Management',
			'An update was made in your Account Management page. Here is the data that was saved: <br />' . $account->config,
			$user->accountId
		);

		return response()->json(['id'=>1, 'pageData' => $settings]);
	}

}

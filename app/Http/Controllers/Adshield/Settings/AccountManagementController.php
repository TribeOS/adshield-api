<?php

namespace App\Http\Controllers\Adshield\Settings;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

use App\Http\Controllers\Adshield\LoginController;
use App\Model\Account;


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
			return $this->saveSettings($user->accountId);
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


	private function saveSettings($accountId)
	{
		$settings = Input::get('accountManagement', []);
		$settings = $settings['pageData'];
		$account = Account::find($accountId);
		$account->config = json_encode($settings);
		$account->save();
		return response()->json(['id'=>1, 'pageData' => $settings]);
	}

}

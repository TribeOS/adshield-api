<?php

namespace App\Http\Controllers\Adshield\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

use App\Http\Controllers\Adshield\LoginController;
use App\Model\User;
use App\Model\UserConfig;

use App\Http\Controllers\Adshield\LogController;


class ContentDistributionController extends Controller
{

	public function handle(Request $request)
	{
		$userId = 0;
        try {
            $token = $request->bearerToken();
            $userId = LoginController::getUserIdFromToken($token);
        } catch (Exception $e) {}

		if ($request->isMethod('get'))
		{
			$userKey = Input::get('userKey');
			return $this->getSettings($userKey);
		}
		else if ($request->isMethod('put'))
		{
			return $this->saveSettings();
		}
	}

	private function getSettings($userKey)
	{
		$settings = UserConfig::where('userKey', $userKey)->first();

		$data = [
			'manageCache' => [
				'enableContentCache' => 0,
				'enableUrlCache' => 0,
				'enableMobileCache' => 0,
			],
			'cacheExtension' => [
				'html' => false,
				'css' => false,
				'js' => false
			],
			'compressionReroute' => [
				'enableReroute' => 0,
				'enableBypassCookie' => 0
			],
			'customHeaders' => [
				'clientHeader' => ''
			]
		];

		if (!empty($settings) && !empty($settings->getConfigJson('contentDistribution'))) $data = $settings->getConfigJson('contentDistribution');

		return response()->json(['id'=>1, 'pageData' => $data]);

	}

	private function saveSettings()
	{
		$settings = Input::get('contentDistribution', []);
		$userKey = $settings['pageData']['userKey'];
		$config = UserConfig::where('userKey', $userKey)->first();

		if (empty($config)) {
			$config = new UserConfig();
			$config->userKey = $userKey;
		}
		$config->updatedOn = gmdate("Y-m-d H:i:s");
		$value = json_decode($config->config, 1);
		unset($settings['pageData']['userKey']);
		$value['contentDistribution'] = $settings['pageData'];
		$config->config = json_encode($value);
		$config->save();

		LogController::QuickLog(LogController::ACT_VIEW_GRAPH_IP, [
			'title' => 'Content Distribution'
		]);

		return response()->json(['id'=>1, 'pageData' => $settings]);
	}

}

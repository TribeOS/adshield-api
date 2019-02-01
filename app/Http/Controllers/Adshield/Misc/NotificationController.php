<?php


namespace App\Http\Controllers\Adshield\Misc;

use Illuminate\Routing\Controller as BaseController;

use DB;


/**
 * handles all notification from the backend to the users based on their
 * notification preferences
 * Notification are sent via email for threats, config changes, sign ups, etc...
 */
class NotificationController extends BaseController {

	const NC_ALL = 'all';
	const NC_SETTINGS = 'settings';
	const NC_VIOLATIONS = 'violations';


	/**
	 * creates a new notification and sends it
	 */
	public static function CreateAndSend($userKey, $type, $data)
	{
		$config = self::GetAccountConfig($userKey);
		if ($config == false) return;

		$config = $config['emailNotifications'];
		$sentTo = []; //list of emails already sent for this instance

		$notification = "";
		//create email object (pass $data)
		foreach($config as $c)
		{
			if ($type != $c['coverage']) continue;	//skip if coverage doesn't include this event
			if (in_array($c['email'], $sentTo)) continue;	//skip if already sent

			$sentTo[] = $c['email'];
			//$c['email'], $c['coverage']
			//self::Send($notification);
		}
	}


	/**
	 * sends the created notification
	 */
	private static function Send($notification)
	{
		//send it here
	}


	/**
	 * gets account's config for the given UserKey
	 */
	private function GetAccountConfig($userKey)
	{
		$account = DB::table("account")
			->join("userWebsites", function($join) use($userKey) {
				$join->on("userWebsites.accountId", "=", "account.id")
					->where("userWebsites.userKey", $userKey);
			})
			->select("config")->first();

		if (empty($account)) return false;
		return json_decode($account->config, 1);
	}

}
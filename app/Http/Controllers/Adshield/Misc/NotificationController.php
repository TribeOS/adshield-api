<?php


namespace App\Http\Controllers\Adshield\Misc;

use Illuminate\Routing\Controller as BaseController;
use App\Mail\Notification;
use Illuminate\Support\Facades\Mail;


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
		$info = [];	//info we want to send to the notification
		if (empty($config)) return false;

		if ($type == self::NC_SETTINGS) {
			//get settings information
			$info = $data['settings'];
		} else if ($type == self::NC_VIOLATIONS) {
			//get violation info
			$info = $data['violation'];
		}
		$notification = new Notification($type, $info);

		//create email object (pass $data)
		foreach($config as $c)
		{
			if ($type != $c['coverage'] && $c['coverage'] !== self::NC_ALL) continue;	//skip if coverage doesn't include this event
			if (in_array($c['email'], $sentTo)) continue;	//skip if already sent

			$sentTo[] = $c['email'];
			//$c['email'], $c['coverage']
			Mail::to($c['email'])->send($notification);
		}
	}


	/**
	 * gets account's config for the given UserKey
	 */
	private static function GetAccountConfig($userKey)
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
<?php


namespace App\Http\Controllers\Adshield\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Response;

use App\Http\Controllers\Adshield\LoginController;
use App\Model\User;
use App\Model\UserWebsite;

use App\Http\Controllers\Adshield\LogController;


class UserWebsitesController extends Controller
{

    const MAX_KEY_CHAR = 12; //max characteres for a userkey (this is used when generating a rnadom user key)

    public function handle(Request $request)
    {
        $userId = 0;
        try {
            $token = $request->bearerToken();
            $user = LoginController::getUserIdFromToken($token, true); //get USER instead of just the id
        } catch (Exception $e) {}

        if ($request->isMethod('get'))
        {
            return $this->getWebsites($user->accountId, $request);
        }
        else if ($request->isMethod('post'))
        {
            $website = Input::get("userWebsite");
            $userKey = !empty($website['userKey']) ? $website['userKey'] : '';
            return $this->create($user, $website['domain'], $userKey);
        }
        else if ($request->isMethod('delete'))
        {
            return $this->remove();
        }
    }


    /**
     * get all websites belonging to this account
     */
    public static function getUserWebsites($accountId)
    {
        $websites = UserWebsite::where("accountId", $accountId)->get();
        return $websites;
    }


    /**
     * get all websites belonging to this user (for public call)
     */
    private function getWebsites($accountId, $request)
    {
        if ($request->get('page'))
        {
            $limit = $request->get('limit', 10);
            $page = $request->get('page', 0);
            $data = UserWebsite::where("accountId", $accountId)->orderBy("domain");
            $data = $data->paginate($limit);
            $data->appends([
                'limit' => $limit
            ]);

            return response()->json(['id' => 0, 'listData' => $data]);
        }
        $websites = UserWebsite::where("accountId", $accountId)->get();
        return $websites;
    }


    private function remove()
    {
        //remove website?
    }

    private function create($user, $domain, $userKey="")
    {
        if (empty($userKey)) $userKey = $this->GenerateUserKey($domain);
        //check if user key is unique FIRST
        $record = UserWebsite::where("userKey", $userKey)->first();

        if (!empty($record)) {
            return response("The Key '$userKey' already exists.", 500)
                ->header('Content-Type', 'text/plain');
        }

        $record = UserWebsite::where("domain", $domain)->first();
        if (!empty($record)) {
            return response("The domain '$domain' is already registered.", 500)
                ->header('Contet-Type', 'text/plain');
        }

        try {
            $record = new UserWebsite();
            $record->accountId = $user->accountId;
            $record->userKey = $userKey;
            $record->domain = $domain;
            $record->createdOn = gmdate('Y-m-d H:i:s');
            $record->status = 1;
            $record->save();
        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }
        

        LogController::QuickLog(LogController::ACT_WEBSITE_ADD, [
            'userKey' => $userKey,
            'domain' => $record->domain
        ]);

        return response($record, 200);
    }


    /**
     * generate a UserKey from the given domain 
     * @param [type] $domain [description]
     */
    private function GenerateUserKey($domain)
    {
        $userKey = $domain;
        if (strpos($domain, '.') !== false)
        {
            $userKey = explode('.', $domain);
            $userKey = $userKey[0]; //get the first part only.
        }
        $userKey = strtolower($userKey);
        $record = UserWebsite::where("userKey", $userKey)->first();
        if (!empty($record))
        {
            //key already exists, create a new one
            $domain = str_random(self::MAX_KEY_CHAR);
            return $this->GenerateUserKey($domain);
        }
        return $userKey;
    }

}

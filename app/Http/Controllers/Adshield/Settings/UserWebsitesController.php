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
use App\Http\Controllers\Adshield\Misc\NotificationController;


class UserWebsitesController extends Controller
{

    const ST_ACTIVE = '1';
    const ST_DELETED = '0';

    const MAX_KEY_CHAR = 12; //max characteres for a userkey (this is used when generating a rnadom user key)

    var $user = null;

    public function handle(Request $request, $id=null)
    {
        $userId = 0;
        try {
            $token = $request->bearerToken();
            $user = LoginController::getUserIdFromToken($token, true); //get USER instead of just the id
            $this->user = $user;
        } catch (Exception $e) {}

        if ($request->isMethod('get'))
        {
            return $this->getWebsites($user->accountId, $request);
        }
        else if ($request->isMethod('post') || $request->isMethod('put'))
        {
            $website = Input::get("userWebsite");
            $userKey = !empty($website['userKey']) ? $website['userKey'] : '';
            $jsCode = $website["jsCode"];
            if (empty($id)) {
                return $this->create($user, $website['domain'], $userKey, $jsCode);
            } else {
                return $this->update($id, $website);
            }
        }
        else if ($request->isMethod('delete'))
        {
            if (empty($id)) {
                return response("Invalid request.", 500);
            }
            return $this->remove($id);
        }
    }


    /**
     * get all websites belonging to this account
     */
    public static function getUserWebsites($accountId)
    {
        $websites = UserWebsite::where("accountId", $accountId)
            ->where("status", self::ST_ACTIVE)
            ->get();
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
            $data = UserWebsite::where("accountId", $accountId)
                ->where("status", self::ST_ACTIVE)
                ->orderBy("domain");
            $data = $data->paginate($limit);
            $data->appends([
                'limit' => $limit
            ]);

            foreach($data as $d) {
                $coded = json_decode($d->jsCode);
                $d->jsCode = ($coded == null) ? [] : $coded;
            }

            return response()->json(['id' => 0, 'listData' => $data]);
        }
        $websites = UserWebsite::where("accountId", $accountId)
            ->where("status", self::ST_ACTIVE)
            ->get();
            
        return $websites;
    }


    /**
     * use this if we are actually deleting the website data along with other records attached to it from the database
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    private function remove($id)
    {
        //remove website?
        $record = UserWebsite::find($id);
        $domain = $record->domain;
        $userKey = $record->userKey;
        $result = $record->delete();

        return response([], 200);
    }

    private function create($user, $domain, $userKey="", $jsCode="")
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
            $record->status = self::ST_ACTIVE;
            $record->jsCode = json_encode($jsCode);
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
     * updates the userwebsite record
     * @param  [type] $id      [description]
     * @param  [type] $website [description]
     * @return [type]          [description]
     */
    private function update($id, $website)
    {
        $record = UserWebsite::find($id);
        if (empty($record))
        {
            return response([
                'errors' => [
                    'detail' => "Website doesn't exists.",
                ]
            ], 500);
        }

        $oldStatus = $record->status;

        $record->jsCode = json_encode($website['jsCode']);
        $record->domain = $website['domain'];
        $record->status = $website['status'];
        $record->save();

        //check if this is a changed of status from 1 to 0 (deleted/inactive)
        if ($oldStatus == 1 && $record->status == 0) {
            $description = "Your website, <strong>{$record->domain}</strong>, has been removed. UserKey : {$record->userKey}";
        } else {
            $description = "Your website, <strong>{$record->domain}</strong>, has been updated. UserKey : {$record->userKey}";
        }

        // NotificationController::CreateAndSendSettings(
        //     $this->user->username,
        //     'Api Settings',
        //     $description,
        //     $this->user->accountId
        // );

        return response(['userWebsite' => $record, 'id' => $id], 200);
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
        //we generate a random key if key already exists or resulting key is less than 6 chars
        if (!empty($record) || strlen($userKey) < 6)
        {
            //key already exists, create a new one
            $domain = str_random(self::MAX_KEY_CHAR);
            return $this->GenerateUserKey($domain);
        }
        return $userKey;
    }

}

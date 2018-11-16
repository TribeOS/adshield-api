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


class UserWebsitesController extends Controller
{

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
            return $this->create($user, $website['domain'], $website['userKey']);
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
            //return paginated result
            $data = UserWebsite::where("accountId", $accountId)
                ->orderBy('domain', 'asc');
            $data = $data->paginate(10);
            return response()->json(['id' => 0, 'listData' => $data])
                ->header('Content-Type', 'application/vnd.api+json');
        }

        $websites = UserWebsite::where("accountId", $accountId)->get();
        return $websites;
    }


    private function remove()
    {
        //remove website?
    }

    private function create($user, $domain, $userKey)
    {
        //check if user key is unique FIRST
        $record = UserWebsite::where("userKey", $userKey)->first();

        if (!empty($record)) {
            return response("The Key '$userKey' already exists.", 500)
                ->header('Content-Type', 'text/plain');
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
        
        return response($record, 200);
    }

}

<?php


namespace App\Http\Controllers\Adshield;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

use App\User;


class UserWebsitesController extends Controller
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
            return $this->getWebsites($userId);
        }
        else if ($request->isMethod('post'))
        {
            $website = Input::get("userWebsite");
            return $this->create($userId, $website['domain'], $website['userKey']);
        }
        else if ($request->isMethod('delete'))
        {
            return $this->remove();
        }
    }


    /**
     * get all websites belonging to this user
     */
    public static function getUserWebsites($userId)
    {
        $websites = DB::table("userWebsites")->where("userId", $userId)->get();
        return $websites;
    }


    /**
     * get all websites belonging to this user (for public call)
     */
    private function getWebsites($userId)
    {
        $websites = DB::table("userWebsites")->where("userId", $userId)->get();
        return $websites;
    }


    private function remove()
    {
        //remove website?
    }

    private function create($userId, $domain, $userKey)
    {
        //check if user key is unique FIRST

        DB::table("userWebsites")
            ->insert([
                'userId' => $userId,
                'domain' => $domain,
                'userKey' => $userKey,
                'createdOn' => gmdate("Y-m-d H:i:s")
            ]);
    }

}

<?php

/**
 * handles adshield dashboard logins
 */

namespace App\Http\Controllers\Adshield;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

use App\Http\Controllers\Adshield\Settings\UserWebsitesController;
use App\Model\User;
use App\Http\Controllers\Adshield\LogController;


class LoginController extends Controller
{

    /**
     * handles log in from ember js
     */
    public function login(Request $request)
    {

        $username = Input::get("username");
        $password = Input::get("password");

        $user = User::where('username', $username)->first();

        $response = [];
        if (empty($user) || !Hash::check($password, $user->password)) {
            $error = 'Invalid username/password.';
            return response($error, 401)
                ->header('Content-Type', 'application/vnd.api+json');
        }
        $token = md5(time().rand(1000, 9999));
        $response['access_token'] = $token;
        $response['username'] = $user->username;
        $response['id'] = $user->id;
        $response['accountId'] = $user->accountId;
        $response['channelId'] = sha1($user->accountId); //create identifier for the channel
        $this->saveToken($token, $user);

        $response['websites'] = UserWebsitesController::getUserWebsites($user->acountId);

        LogController::Log($user->id, $user->accountId, LogController::ACT_LOG_IN, '');

        return response()->json($response)
            ->header('Content-Type', 'application/vnd.api+json');
    }


    private function saveToken($token, $user)
    {
        DB::table("accessTokens")
            ->where("expiresOn", "<", gmdate("Y-m-d H:i:s"))
            ->delete();

        DB::table("accessTokens")
            ->insert([
                'accessToken' => $token,
                'accountId' => $user->accountId,
                'userId' => $user->id,
                'expiresOn' => gmdate("Y-m-d H:i:s", strtotime("+24 hours")),
                'createdOn' => gmdate("Y-m-d H:i:s")
            ]);
    }


    public function logout(Request $request)
    {

        $token = $request->bearerToken();
        if (empty($token)) return response("Invalid request", 401);

        //create a log of this event
        $user = LoginController::getUserIdFromToken($token, true);
        LogController::Log($user->userId, $user->accountId, LogController::ACT_LOG_OUT, '');

        DB::table("accessTokens")
            ->where("accessToken", $token)
            ->delete();

        return response()->json(['success' => true])
            ->header('Content-Type', 'application/vnd.api+json');
    }


    public static function isTokenVerified($username, $token)
    {
        $result = DB::table("accessTokens")
            ->join("users", "users.id", "=", "accessTokens.userId")
            ->where("users.username", $username)
            ->where("accessTokens.accessToken", $token)
            ->where("accessTokens.expiresOn", ">", date("Y-m-d H:i:s", strtotime("24 hours ago")))
            ->first();

        if (empty($result)) return false;
        return true;
    }

    /**
     * gets the user id from the token saved.
     * @param  [type]  $token [description]
     * @param  boolean $all   set to TRUE to get the entire saved record of the token (includes accountID and userID)
     * @return [type]         [description]
     */
    public static function getUserIdFromToken($token, $all=false)
    {
        $result = DB::table("accessTokens")->where("accessToken", $token)->first();
        if (!$all) return $result->userId;
        return $result;
    }

    /**
     * verifies the token passed with what we currently have on the database
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function verifyToken(Request $request)
    {
        $token = $request->bearerToken();
        $result = DB::table("accessTokens")
            ->join("users", "users.id", "=", "accessTokens.userId")
            ->where("accessTokens.accessToken", $token)
            ->where("accessTokens.expiresOn", ">", gmdate("Y-m-d H:i:s", strtotime("24 hours ago")))
            ->first();

        $valid = true;
        if (empty($result)) $valid = false;

        return response()->json(['id' => 0, 'valid' => $valid])
            ->header('Content-Type', 'application/vnd.api+json');
    }

}

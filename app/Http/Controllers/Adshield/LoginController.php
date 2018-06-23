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

use App\User;


class LoginController extends Controller
{
    

    /**
     * handles log in from ember js
     */
    public function login()
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
        $this->saveToken($token, $user->id);

        $response['websites'] = UserWebsitesController::getUserWebsites($user->id);


        return response()->json($response)
            ->header('Content-Type', 'application/vnd.api+json');
    }


    private function saveToken($token, $userId)
    {
        DB::table("accessTokens")
            ->where("userId", $userId)
            ->delete();

        DB::table("accessTokens")
            ->insert([
                'accessToken' => $token,
                'userId' => $userId,
                'expiresOn' => gmdate("Y-m-d H:i:s", strtotime("+24 hours")),
                'createdOn' => gmdate("Y-m-d H:i:s")
            ]);
    }


    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if (empty($token)) return response("Invalid request", 401);

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

    public static function getUserIdFromToken($token)
    {
        $result = DB::table("accessTokens")->where("accessToken", $token)->first();
        return $result->userId;
    }

    public function verifyToken(Request $request)
    {
        $token = $request->bearerToken();
        $result = DB::table("accessTokens")
            ->join("users", "users.id", "=", "accessTokens.userId")
            ->where("accessTokens.accessToken", $token)
            ->where("accessTokens.expiresOn", ">", date("Y-m-d H:i:s", strtotime("24 hours ago")))
            ->first();

        $valid = true;
        if (empty($result)) $valid = false;
        
        return response()->json(['id' => 0, 'valid' => $valid])
            ->header('Content-Type', 'application/vnd.api+json');        
    }

}

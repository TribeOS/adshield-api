<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Config;


class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $token = $request->bearerToken();

        $access = DB::table("accessTokens")
            ->join("user", "user.id", "=", "accessToken.userId")
            ->where("accessToken", $token)
            ->where("expiresOn", ">", gmdate("Y-m-d H:i:s"))
            ->selectRaw("accessTokens.*, user.timeZone")
            ->first();

        if (empty($access)) 
        {
            $error = [
                'errors' => [
                    'msg' => "Invalid access or session has already expired. Try loggin in again."
                ]
            ];
            return response(json_encode($error), 401)
                ->header('Content-Type', 'application/vnd.api+json')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Authorization');
        }


        Config::set('user', $access);

        return $next($request)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Authorization');
    }
}

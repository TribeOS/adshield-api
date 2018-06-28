<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

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
            ->where("accessToken", $token)
            ->where("expiresOn", ">", gmdate("Y-m-d H:i:s"))
            ->first();

        if (empty($access)) 
        {
            $response = "Invalid acces or session has already expired. Try loggin in again.";
            return response($response, 401);
        }

        // $key = $request->route('apikey');
        // if (empty($key)) return redirect()->route('ApiError');

        // $auth = DB::table("asConfig")->where("name", "main")->first();
        // if (empty($auth)) return redirect()->route('ApiError');

        // $auth = json_decode($auth->value);
        // if ($key !== $auth->frontAccessKey) return redirect()->route('ApiError');
        //we can validate "key"

        return $next($request)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    }
}

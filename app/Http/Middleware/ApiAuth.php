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
        //check if userkey is valid?
        // $userKey = $request->route('UserKey');
        // $record = DB::table("userWebsites")->where("userKey", $userKey)->first();
        // if (empty($record)) return redirect()->route('ApiError');

        $key = $request->input("key", "");
        //we can validate "key"

        return $next($request)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    }
}

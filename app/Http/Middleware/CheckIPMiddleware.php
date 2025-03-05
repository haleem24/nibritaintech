<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;
use App\Models\Ip_address_model;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Log;

class CheckIPMiddleware
{
    public function handle($request, Closure $next)
    {
        // Get the current route name
        $currentRoute = Route::currentRouteName();
        // Retrieve the user's ID from the session
        $userId = session('user_id');
        if($userId != null){
            $user = session('user');
        }
        // If the current route is the login route or sign-in route, bypass the middleware
        if ($currentRoute == 'login' || $currentRoute == 'signin' || $currentRoute == 'error') {
            return $next($request);
        }

        // Redirect to the sign-in page if user ID is null
        if ($userId == null) {
            return redirect('signin');
        }

        $ip = $request->ip();
        $ip_address = Ip_address_model::where('ip',$ip)->where('status',1)->first();
        if(!$user->hasPermission('add_ip')){
            // dd($ip_address);
            if($ip_address == null || $ip_address->updated_at->diffInDays(now()) > 5){
                // dd($ip);
                $ip_address->status = 2;
                $ip_address->save();
                Log::info('New IP detected  for user '.$user->first_name.' with IP '.$ip);
                abort(403, 'Quote of the day: '.Inspiring::just_quote());
            }
            if($ip_address != null && $ip_address->updated_at->diffInDays(now()) > 2){
                $ip_address->status = 1;
                $ip_address->updated_at = now();
                $ip_address->save();
            }
        }else{
            if($ip_address != null && $ip_address->updated_at->diffInDays(now()) > 2){
                $ip_address->status = 1;
                $ip_address->updated_at = now();
                $ip_address->save();
            }
        }
        // If the user has the required permission, proceed to the next middleware
        return $next($request);
    }
}

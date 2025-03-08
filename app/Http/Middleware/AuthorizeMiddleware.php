<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;
use App\Models\Admin_model;
use App\Models\Grade_model;
use App\Models\Ip_address_model;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Log;

class AuthorizeMiddleware
{
    public function handle($request, Closure $next, ...$permissions)
    {
        // Get the current route name
        $currentRoute = Route::currentRouteName();
        // Retrieve the user's ID from the session
        $userId = session('user_id');
        if($userId != null){

            $user = Admin_model::find($userId);
            $all_grades = Grade_model::all();
            session()->put('user',$user);
            session()->put('all_grades',$all_grades);
        }
        // If the current route is the login route or sign-in route, bypass the middleware
        if ($currentRoute == 'login' || $currentRoute == 'signin' || $currentRoute == 'admin.2fa' || $currentRoute == 'admin.2fa2' || $currentRoute == 'index' || $currentRoute == 'profile' || $currentRoute == 'error') {
            // Redirect to the sign-in page if user ID is null
            if ($userId == null && $currentRoute == 'index') {
                return redirect('signin');
            }
            return $next($request);
        }

        // Redirect to the sign-in page if user ID is null
        if ($userId == null) {
            return redirect('signin');
        }

        // Retrieve the page from the session
        // $page = session('page');
        // dd($page)
        // Retrieve the user object by ID
        session()->put('user',$user);
        // Check if the user has the required permission for the current page
        if (!$user->hasPermission($currentRoute)) {
            // Log the unauthorized access attempt
            Log::info('Unauthorized access attempt by user '.$user->first_name.' to '.$currentRoute);
            abort(403, 'Quote of the day: '.Inspiring::just_quote());
        }
        // Remove the 'page' session variable
        session()->forget('page');

        // If the user has the required permission, proceed to the next middleware
        return $next($request);
    }
}

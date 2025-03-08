<?php
// In app/Http/Middleware/InternalOnly.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Env;

class InternalOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Define allowed IPs for internal access
        $allowedIps = [
            '127.0.0.1', // localhost
            '::1',       // localhost IPv6
            // '192.168.1.1', // Replace with your server's internal IP
            Env::get('SERVER_IP'), // Get the server IP from .env file
        ];

        $urlParts = explode('/', Env::get('APP_URL'));
        $url = end($urlParts);
        // Check if the request originated from the allowed domain
        // if ($request->getHost() !== $url) {
        //     dd($request->getHost(), Env::get('APP_URL'), request(), $url);
        //     abort(401, 'Unauthorized accessu');
        // }

        // if (!in_array($request->ip(), $allowedIps)) {
        //     abort(401, 'Unauthorized access'.$request->ip(). $request->server('SERVER_ADDR'));
        // }
        return $next($request);
    }
}

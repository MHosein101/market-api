<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Check the auth header in public requests
 * if it is available, continue into ValidateToken middleware
 * else just fill user field with null
 * 
 * @author Hosein marzban
 */
class CheckAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasHeader('Authorization'))
            return (new ValidateToken)->handle($request, $next);

        $user = null;
        $request->merge(compact('user'));
        return $next($request);
    }
}

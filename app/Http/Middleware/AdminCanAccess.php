<?php

namespace App\Http\Middleware;

use App\Models\UserAccountType;
use Closure;
use Illuminate\Http\Request;

/**
 * If user account_type is admin, request continue
 * 
 * @author Hosein marzban
 */
class AdminCanAccess
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
        if( $request->user->account_type != UserAccountType::Admin )
        {
            return 
                response()
                ->json(
                [ 
                    'status'  => 403 ,
                    'message' => 'You are not admin.' 
                ], 403);
        }

        return $next($request);
    }
}

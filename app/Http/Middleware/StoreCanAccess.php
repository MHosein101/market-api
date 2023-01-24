<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Models\UserAccountType;

/**
 * If user account_type is store owner, request continue
 * 
 * @author Hosein marzban
 */
class StoreCanAccess
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
        if( $request->user->account_type != UserAccountType::Store ) 
        {
            return 
                response()
                ->json([ 
                    'status'  => 403 ,
                    'message' => 'You are not a store manager.' 
                ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Http\Request;
use App\Http\Helpers\DataHelper;

/**
 * Validate request header's token
 * 
 * @author Hosein marzban
 */
class ValidateToken
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
        $apiToken = $request->header('Authorization');
        $apiToken = str_replace('Bearer ', '', $apiToken);

        $tokenRecord = UserToken::where('token', $apiToken)->get()->first();
        
        if($tokenRecord == null)
            return response()
            ->json([ 
                'status' => 403 ,
                'message' => 'Token is invalid.' 
            ], 403);

        if($tokenRecord->expire < time()) {
            UserToken::where('token', $apiToken)->delete();

            return response()
            ->json([ 
                'status' => 403 ,
                'message' => 'Token has expired.' 
            ], 403);
        }

        $user = User::where('id', $tokenRecord->user_id)
        ->get(['id', 'account_type', 'store_id'])
        ->first();
        
        $request->merge(compact('apiToken'));
        $request->merge(compact('user'));

        return $next($request);
    }
}

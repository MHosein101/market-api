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
     * @param \Illuminate\Http\Request  $request
     * @param \Closure|\Illuminate\Http\Request $next
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Request
     */
    public function handle(Request $request, Closure $next)
    {
        $apiToken = $request->header('Authorization');

        $apiToken = str_replace('Bearer ', '', $apiToken);

        $tokenRecord = UserToken::where('token', $apiToken)->first();
        
        if($tokenRecord == null)
        {
            return 
                response()
                ->json(
                [ 
                    'status'  => 403 ,
                    'message' => 'Token is invalid.' 
                ], 403);
        }

        if( $tokenRecord->expire < time() ) 
        {
            UserToken::where('token', $apiToken)->delete();

            return 
                response()
                ->json(
                [ 
                    'status'  => 403 ,
                    'message' => 'Token has expired.' 
                ], 403);
        }
        
        $u = User::find($tokenRecord->user_id);

        $user = (object) 
        [
            'id'           => $u->id ,
            'account_type' => $u->account_type ,
            'store_id'     => $u->store_id ,
        ];
        
        $request->merge(compact('apiToken'));
        $request->merge(compact('user'));

        if(!$request->isMethod('get')) // for debug
        {
            DataHelper::logRequest($request);
        }
        
        return $next($request);
    }
}

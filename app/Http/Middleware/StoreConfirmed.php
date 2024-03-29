<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreConfirmed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Closure|\Illuminate\Http\Request $next
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Request|\Closure
     */
    public function handle(Request $request, Closure $next)
    {
        $pending = Store::find($request->user->store_id)->is_pending;

        if($pending) 
        {
            return 
                response()
                ->json(
                [
                    'status'  => 403 ,
                    'message' => 'Store need to be confirmed by admin.' 
                ], 403);
        }

        return $next($request);
    }
}

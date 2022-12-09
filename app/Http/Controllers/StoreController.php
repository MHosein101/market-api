<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

/**
 * Store panel store info management
 * 
 * @author Hosein marzban
 */ 
class StoreController extends Controller
{

    /**
     * Return current user's store information
     *
     * @param  Request $request
     * 
     * @return Response
     */ 
    public function info(Request $request)
    {
        $store = Store::withTrashed()->find($request->user->store_id);
        
        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'store' => $store
        ], 200);
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\AdminNotificationSubject;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Helpers\DataHelper;

class StoreController extends Controller
{
    /**
     * Return current user's store information
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function info(Request $request)
    {
        $store = Store::withTrashed()->find($request->user->store_id);
        
        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'OK' ,
                'store'   => $store
            ], 200);
    }

    /**
     * Change store options
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function changeSetting(Request $request)
    {
        $v = DataHelper::validate( response() , $request->post() , 
        [
            'min_shopping_count' => [ 'حداقل تعداد خرید', 'required|numeric' ] ,
        ]);
        
        if( $v['code'] == 400 )
        {
            return $v['response'];
        }
        
        $store = Store::withTrashed()->find($request->user->store_id);

        // notify admin about change
        AdminNotification::customCreate(
            AdminNotificationSubject::StoreMinimumShoppingCountChanged,
            $store->id, 
            $store->minimum_shopping_count,
            (int) $request->input('min_shopping_count')
        );

        $store->minimum_shopping_count = $request->input('min_shopping_count');
        $store->save();

        return 
            response()
            ->json(
            [
                'status'  => 200 ,
                'message' => 'OK' ,
                'store'   => $store
            ], 200);
    }

}

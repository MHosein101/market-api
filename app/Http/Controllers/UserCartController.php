<?php

namespace App\Http\Controllers;

use App\Models\StoreProduct;
use App\Models\UserCart;
use Illuminate\Http\Request;

/**
 * User shopping cart management
 * 
 * @author Hosein marzban
 */ 
class UserCartController extends Controller
{

    /**
     * Return user's cart items
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getAll(Request $request)
    {
        $cart = UserCart::where('user_id', $request->user->id)->get();

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'cart' => $cart
        ], 200);
    }

    /**
     * Add product to the cart
     *
     * @param Request $request
     * @param int $productId
     * 
     * @return Response
     */ 
    public function addProduct(Request $request, $storeProductId)
    {
        $sp = StoreProduct::find($storeProductId);

        $newItem = UserCart::create([
            'count' => 1 ,
            'is_payment_cash' => false ,
            'product_id' => $storeProductId ,
            'store_id' => $sp->store_id ,
            'base_product_id' => $sp->product_id ,
            'user_id' => $request->user->id ,
        ]);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'product' => $newItem
        ], 200);
    }

    /**
     * Add product to the cart
     *
     * @param Request $request
     * @param int $productId
     * 
     * @return Response
     */ 
    public function updateCart(Request $request, $itemId, $type)
    {
        $update = UserCart::where('id', $itemId);

        if($type == 'up')
            $update = $update->increment('count', 1);

        if($type == 'down')
            $update = $update->decrement('count', 1);

        if($type == 'payment') {
            $ipc = UserCart::where('user_id', $request->user->id)
            ->first()->is_payment_cash;

            UserCart::where('user_id', $request->user->id)
            ->update([ 'is_payment_cash' => !$ipc ]);
        }


        $cart = UserCart::where('user_id', $request->user->id)->get();
        
        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'cart' => $cart
        ], 200);
    }
    
    /**
     * Delete cart item
     *
     * @param Request $request
     * @param int $itemId
     * 
     * @return Response
     */ 
    public function deleteItem(Request $request, $itemId)
    {
        UserCart::where('user_id', $request->user->id)->where('id', $itemId)->delete();

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
        ], 200);
    }
    
    /**
     * Delete all items from cart
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function clearCart(Request $request)
    {
        UserCart::where('user_id', $request->user->id)->delete();

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
        ], 200);
    }

}

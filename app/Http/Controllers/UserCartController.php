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
    public function addProduct(Request $request, $productId)
    {
        $storeId = StoreProduct::find($productId)->store_id;

        $newItem = UserCart::create([
            'qty' => 1 ,
            'is_payment_cash' => false ,
            'product_id' => $productId ,
            'store_id' => $storeId ,
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
     * Update cart item details
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function updateCart(Request $request)
    {
        $itemsCount = $request->input('items_count', 0);

        for($i = 0; $i < $itemsCount; $i++) {
            
            $id = $request->input("cart_items_{$i}_id");
            $qty = $request->input("cart_items_{$i}_qty");
            $ipc = $request->input("cart_items_{$i}_is_payment_cash");

            UserCart::where('id', $id)
            ->update([
                'qty' => $qty ,
                'is_payment_cash' => ($ipc == 'true') ,
            ]);
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

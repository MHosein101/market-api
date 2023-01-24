<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\UserCart;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Http\Helpers\CartHelper;

/**
 * User shopping cart management
 * 
 * @author Hosein marzban
 */ 
class UserCartController extends Controller
{

    /**
     * Return user's cart summary group by stores
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getStoresSummary(Request $request)
    {
        return response()->json(CartHelper::storesSummary(), 200);
    }

    /**
     * Return cart items data in specific store
     *
     * @param Request $request
     * @param int $storeId
     * 
     * @return Response
     */ 
    public function getStoreItems(Request $request, $storeId)
    {
        return response()->json(CartHelper::storeItems($storeId), 200);
    }

    /**
     * Return user's cart items data summary
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getCartSummary(Request $request)
    {
        return response()->json(CartHelper::cartSummary(), 200);
    }

    /**
     * Add store product to the cart
     *
     * @param Request $request
     * @param int $productId
     * 
     * @return Response
     */ 
    public function addProduct(Request $request, $storeProductId)
    {
        $sp = StoreProduct::find($storeProductId);

        UserCart::create([
            'count' => 1 ,
            'is_payment_cash' => false ,
            'current_price' => $sp->store_price ,
            'product_id' => $storeProductId ,
            'store_id' => $sp->store_id ,
            'base_product_id' => $sp->product_id ,
            'user_id' => $request->user->id ,
        ]);

        return response()->json(CartHelper::cartSummary(), 200);
    }

    /**
     * Update cart item count
     *
     * @param Request $request
     * @param int $storeId
     * @param int $productId
     * @param string $type
     * @param string|null $isFactor
     * 
     * @return Response
     */ 
    public function updateItemCount(Request $request, $storeId, $productId, $type, $isFactor = null)
    {
        $item = UserCart::currentUser()
        ->where('store_id', $storeId)
        ->where('product_id', $productId)->first();
        
        $limit = StoreProduct::find($item->product_id)->warehouse_count;

        if($item != null) {
            if($type == 'up' && $item->count + 1 <= $limit)
                $item->count += 1;

            if($type == 'down')
                $item->count -= 1;

            $item->save();

            if($item->count == 0)
                $item->delete();
        }

        if($isFactor == 'factor')
            return response()->json(CartHelper::storeItems($storeId), 200);
        else
            return response()->json(CartHelper::cartSummary(), 200);
    }

    /**
     * Delete cart items of a store
     *
     * @param Request $request
     * @param int $storeId
     * 
     * @return Response
     */ 
    public function deleteStoreItems(Request $request, $storeId)
    {
        UserCart::currentUser()->where('store_id', $storeId)->delete();

        return response()->json(CartHelper::storesSummary(), 200);
    }

}

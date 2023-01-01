<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\UserCart;
use App\Models\StoreProduct;
use Illuminate\Http\Request;

/**
 * User shopping cart management
 * 
 * @author Hosein marzban
 */ 
class UserCartController extends Controller
{
    /**
     * Return cart items group by stores
     * 
     * @param int $storeId
     * 
     * @return array
     */ 
    public function getCartStoresData()
    {
        $uid = request()->user->id;
        $user = User::find($uid);
        $storeIDs = UserCart::where('user_id', $uid)->distinct()->pluck('store_id');
        $stores = [];

        foreach($storeIDs as $sid) {
            $s = Store::find($sid);
            $cartItems = UserCart::where('user_id', $uid)->where('store_id', $sid)->get();
            $products = [];

            foreach($cartItems as $item) {
                $p = $item->product;
                unset($p['id']);
                unset($p['count']);
                $products[] = $p;
            }

            $stores[] = [
                'id' => $s->id ,
                'title' => $s->name ,
                'slug' => $s->slug ,
                'logo_image' => $s->logo_image ,
                'products' => $products
            ];
        }

        return [
            'status' => 200 ,
            'message' => 'OK' ,
            'user' => $user ,
            'stores' => $stores ,
        ];
    }

    /**
     * Return cart store items data
     * 
     * @param int $storeId
     * 
     * @return array
     */ 
    public function getCartItemsData($storeId)
    {
        $uid = request()->user->id;
        $user = User::find($uid);
        $store = Store::find($storeId);
        $cart = UserCart::where('user_id', $uid)->where('store_id', $storeId)->get();
        $cartItemsCount = UserCart::where('user_id', request()->user->id)->sum('count');

        $costs = [
            'total_price' => 0 ,
            'is_discount' => 0 ,
            'discount_price' => 0 ,
            'discount_percent' => 0 ,
            'final_price' => 0 ,
            'payment_state' => true
        ];

        foreach($cart as $item) {
            $costs['total_price'] += $item->price['original_total'];
            $costs['final_price'] += $item->price['final_total'];

            if($item->state['is_show'])
                $costs['state'] = false;
        }

        $costs['discount_price'] = $costs['total_price'] - $costs['final_price'];
        $costs['is_discount'] = $costs['discount_price'] > 0;
        $costs['discount_percent'] = $costs['discount_price'] / ( $costs['total_price'] / 100 );

        $costs['discount_percent'] = round($costs['discount_percent'], 1);

        return [
            'status' => 200 ,
            'message' => 'OK' ,
            'user' => $user ,
            'store' => [
                'id' => $store->id ,
                'title' => $store->name ,
                'slug' => $store->slug ,
                'logo_image' => $store->logo_image ,
            ] ,
            'cart' => [
                'count' => (int)$cartItemsCount ,
                'cost' => $costs ,
                'items' => $cart ,
            ]
        ];
    }

    /**
     * Return user's cart items info for short
     * 
     * @return array
     */ 
    public function getCartSummary()
    {
        $cartItems = UserCart::where('user_id', request()->user->id)->get();
        $cartItemsCount = UserCart::where('user_id', request()->user->id)->sum('count');
        $data = [];

        foreach($cartItems as $item) {
            $data[] = [
                'count' => $item->count ,
                'product_id' => $item->product_id ,
                'store_id' => $item->store_id ,
            ];
        }

        return [
            'status' => 200 ,
            'message' => 'OK' ,
            'cart_count' => (int)$cartItemsCount ,
            'cart' => $data
        ];
    }

    /**
     * Return user's cart items info for short
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getCartItemsSummary(Request $request)
    {
        return response()->json($this->getCartSummary(), 200);
    }


    /**
     * Return user's cart items group by stores
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getCartStores(Request $request)
    {
        return response()->json($this->getCartStoresData(), 200);
    }

    /**
     * Return cart items in specific store
     *
     * @param Request $request
     * @param int $storeId
     * 
     * @return Response
     */ 
    public function getCartItems(Request $request, $storeId)
    {
        return response()->json($this->getCartItemsData($storeId), 200);
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

        return response()->json($this->getCartSummary(), 200);
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
        $update = UserCart::where('user_id', $request->user->id)
        ->where('store_id', $storeId)
        ->where('product_id', $productId);

        if($type == 'up')
            $update = $update->increment('count', 1);

        if($type == 'down')
            $update = $update->decrement('count', 1);

            
        $item = UserCart::where('user_id', $request->user->id)
        ->where('store_id', $storeId)
        ->where('product_id', $productId)->first();

        if($item->count == 0)
            $item->delete();

        if($isFactor == 'factor')
            return response()->json($this->getCartItemsData($storeId), 200);
        else
            return response()->json($this->getCartSummary(), 200);
    }

    /**
     * Delete cart item 
     *
     * @param Request $request
     * @param int $storeId
     * @param int $productId
     * 
     * @return Response
     */ 
    public function deleteItem(Request $request, $storeId, $productId)
    {
        UserCart::where('user_id', $request->user->id)
        ->where('store_id', $storeId)
        ->where('product_id', $productId)
        ->delete();

        return response()->json($this->getCartItemsData($storeId), 200);
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
        UserCart::where('user_id', $request->user->id)->where('store_id', $storeId)->delete();

        return response()->json($this->getCartStoresData(), 200);
    }

}

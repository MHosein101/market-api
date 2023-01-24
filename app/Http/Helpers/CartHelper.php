<?php

namespace App\Http\Helpers;

use App\Models\User;
use App\Models\Store;
use App\Models\UserCart;
use App\Models\StoreProduct;

/**
 * Helper methods for cart data 
 * 
 * @author Hosein marzban
 */ 
class CartHelper
{
    
    /**
     * Return user's cart summary data, group by stores
     * 
     * @param int $storeId
     * 
     * @return array
     */ 
    public static function storesSummary()
    {
        $stores = [];

        $user = User::find(request()->user->id);

        $storeIDs = UserCart::currentUser()
        ->distinct()
        ->pluck('store_id');

        foreach($storeIDs as $sid) 
        {
            $products = [];

            $store = Store::find($sid);

            $cartItems = UserCart::currentUser()
            ->where('store_id', $sid)
            ->get();

            foreach($cartItems as $item) 
            {
                $product = $item->product;

                unset($product['id']);
                unset($product['count']);

                $products[] = $product;
            }

            $stores[] = 
            [
                'id'         => $store->id ,
                'title'      => $store->name ,
                'slug'       => $store->slug ,
                'logo_image' => $store->logo_image ,
                'products'   => $products
            ];
        }

        $cart = CartHelper::cartSummary();

        return 
        [
            'user'       => $user ,
            'stores'     => $stores ,
            'cart_count' => $cart['cart_count'] ,
            'cart'       => $cart['cart'] ,
        ];

    }

    /**
     * Return cart items data in specific store
     * 
     * @param int $storeId
     * 
     * @return array
     */ 
    public static function storeItems($storeId)
    {
        $uid = request()->user->id;

        $user = User::find($uid);

        $store = Store::find($storeId);

        $cart = UserCart::currentUser()
        ->where('store_id', $storeId)
        ->get();

        $costs = 
        [
            'total_price'      => 0 ,
            'is_discount'      => 0 ,
            'discount_price'   => 0 ,
            'discount_percent' => 0 ,
            'final_price'      => 0 ,
            'payment_state'    => true
        ];

        foreach($cart as $item) 
        {
            $costs['total_price'] += $item->price['original_total'];
            $costs['final_price'] += $item->price['final_total'];

            if( $item->state['is_show'] ) 
            {
                $costs['payment_state'] = false;

                if( !$item->state['is_available'] ) 
                {
                    UserCart::currentUser()
                    ->where('id', $item->id)
                    ->delete();
                }

                if( $item->state['is_price_changed'] ) 
                {
                    UserCart::currentUser()
                    ->where('id', $item->id)
                    ->update(
                        [ 
                            'current_price' => $item->state['new_price'] 
                        ]
                    );
                }

            }

        }

        $costs['discount_price'] = $costs['total_price'] - $costs['final_price'];

        $costs['is_discount'] = $costs['discount_price'] > 0;

        // to prevent [Divide by zero] error
        if( $costs['discount_price'] == 0 || $costs['total_price'] == 0 )
        {
            $costs['discount_percent'] = 0;
        }
        else
        {
            $costs['discount_percent'] = $costs['discount_price'] / ( $costs['total_price'] / 100 );
        }

        $costs['discount_percent'] = round($costs['discount_percent'], 1);

        $cartsumm = CartHelper::cartSummary();

        return 
        [
            'status'  => 200 ,
            'message' => 'OK' ,
            'user'    => $user ,
            'store'   => 
            [
                'id'         => $store->id ,
                'title'      => $store->name ,
                'slug'       => $store->slug ,
                'logo_image' => $store->logo_image ,
            ] ,
            'cart' => 
            [
                'count' => $cartsumm['cart_count'] ,
                'cost'  => $costs ,
                'items' => $cart ,
            ] ,
            'cart_count' => $cartsumm['cart_count'] ,
            'cart_items' => $cartsumm['cart']
        ];

    }

    /**
     * Return user's cart items data summary
     * 
     * @param int|null $userId
     * 
     * @return array
     */ 
    public static function cartSummary($userId = null)
    {
        // an exception for, after a successful login, in AuthController
        // because user id not present in the request
        $UserCart = $userId == null 
        ? UserCart::currentUser() 
        : UserCart::where('user_id', $userId);

        $cartItems = $UserCart->get();

        $cartItemsCount = $UserCart->sum('count');

        $data = [];

        foreach($cartItems as $item) 
        {
            $limit = StoreProduct::find($item->product_id)->warehouse_count;

            $data[] = 
            [
                'count'      => $item->count ,
                'product_id' => $item->product_id ,
                'store_id'   => $item->store_id ,
                'limit'      => $limit
            ];
        }

        return 
        [
            'status'     => 200 ,
            'message'    => 'OK' ,
            'cart_count' => (int)$cartItemsCount ,
            'cart'       => $data
        ];

    }
    
}
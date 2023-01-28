<?php

namespace App\Http\Helpers;

use App\Models\User;
use App\Models\Store;
use App\Models\UserCart;
use App\Models\StoreProduct;
use App\Models\StoreProductDiscount;

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

                $updateData = [];
                
                $updateChanges = false;

                if( $item->state['is_price_changed'] ) 
                {
                    $updateData['current_price'] = $item->state['new_price'];

                    $updateChanges = true;
                }

                if( $item->state['is_discount_changed'] ) 
                {
                    if($item->state['pass_discount_changed'])
                    {
                        $costs['payment_state'] = true;
                    }
                    
                    $updateData['current_discount'] = $item->state['new_discount'];

                    $updateChanges = true;
                }

                if($updateChanges)
                {
                    UserCart::currentUser()
                    ->where('id', $item->id)
                    ->update($updateData);
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

    /**
     * Calculate diffrence value of two prices and return type of diffrence
     * 
     * @param int $old
     * @param int $new
     * 
     * @return array
     */ 
    public static function calcDiff($old, $new)
    {
        $diff = $old - $new;

        $type = 'افزایش';

        if( $diff < 0 ) 
        {
            $type = 'کاهش';
            $diff = -$diff;
        }

        $diff = number_format($diff);

        $diff = str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'] , 
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'] , 
            (string)$diff );

        return [
            'type' => $type ,
            'diff' => $diff
        ];
    }

    /**
     * Check if current discount of cart product changed or not
     * 
     * @param int $storeProductId
     * @param string $currentDiscount
     * 
     * @return boolean
     */ 
    public static function checkDiscount($storeProductId, $currentDiscount)
    {
        $discounts = StoreProductDiscount::where('product_id', $storeProductId)->get();

        foreach($discounts as $d)
        {
            $dStr = "{$d->discount_type}-{$d->discount_value}-{$d->final_price}";

            if($dStr == $currentDiscount)
            {
                return false;
            }
        }

        return true;
    }
    
}
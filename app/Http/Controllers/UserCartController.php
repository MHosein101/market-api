<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\UserCart;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Http\Helpers\CartHelper;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceState;

class UserCartController extends Controller
{

    /**
     * Return user's cart data summary
     * group by stores
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getStoresSummary(Request $request)
    {
        return 
            response()
            ->json(CartHelper::storesSummary(), 200);
    }

    /**
     * Return cart items data in specific store
     *
     * @param \Illuminate\Http\Request
     * @param int $storeId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getStoreItems(Request $request, $storeId)
    {
        return 
            response()
            ->json(CartHelper::storeItems($storeId), 200);
    }

    /**
     * Return user's cart items data summary
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getCartSummary(Request $request)
    {
        return 
            response()
            ->json(CartHelper::cartSummary(), 200);
    }

    /**
     * Add store product to the cart
     *
     * @param \Illuminate\Http\Request
     * @param int $storeProductId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function addProduct(Request $request, $storeProductId)
    {
        $sp = StoreProduct::find($storeProductId);

        UserCart::create(
        [
            'count'           => 1 ,
            'is_payment_cash' => false ,
            'current_price'   => $sp->store_price ,
            'product_id'      => $storeProductId ,
            'store_id'        => $sp->store_id ,
            'base_product_id' => $sp->product_id ,
            'user_id'         => $request->user->id ,
        ]);

        return 
            response()
            ->json(CartHelper::cartSummary(), 200);
    }

    /**
     * Update cart item count
     *
     * @param \Illuminate\Http\Request
     * @param int $storeId
     * @param int $productId
     * @param string $type
     * @param string|null $isFactor
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function updateItemCount(Request $request, $storeId, $productId, $type, $isFactor = null)
    {
        $item = UserCart::currentUser()
        ->where('store_id', $storeId)
        ->where('product_id', $productId)
        ->first();
        
        if( $item != null ) 
        {
            $limit = StoreProduct::find($item->product_id)->warehouse_count;

            if( $type == 'up' && $item->count + 1 <= $limit )
            {
                $item->count += 1;
            }

            if( $type == 'down' )
            {
                $item->count -= 1;
            }

            $item->save();

            if( $item->count == 0 )
            {
                $item->delete();
            }
        }

        if($isFactor == 'factor')
        {
            return 
                response()
                ->json(CartHelper::storeItems($storeId), 200);
        }
        else
        {
            return 
                response()
                ->json(CartHelper::cartSummary(), 200);
        }
    }

    /**
     * Delete cart items of a store
     *
     * @param \Illuminate\Http\Request
     * @param int $storeId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function deleteStoreItems(Request $request, $storeId)
    {
        UserCart::currentUser()
        ->where('store_id', $storeId)
        ->delete();

        return 
            response()
            ->json(CartHelper::storesSummary(), 200);
    }

    /**
     * Create new invoice from cart items 
     *
     * @param \Illuminate\Http\Request
     * @param int $storeId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function createInvoice(Request $request, $storeId)
    {
        $preInvoice = CartHelper::storeItems($storeId);

        if( ! $preInvoice['cart']['cost']['payment_state'] ) // invoice have error
        {
            $preInvoice['factor_state'] = false; // change to invoice_state

            return response()->json($preInvoice, 200);
        }

        if( count($preInvoice['cart']['items']) > 0 )
        {
            $countProducts = UserCart::currentUser()->where('store_id', $storeId)->sum('count');
            $trackingNumber = 0;
            $billNumber = 0;

            do 
            {
                $trackingNumber = random_int(100000000, 999999999);
            } 
            while ( Invoice::where('tracking_number', $trackingNumber)->first() ); // make random unique tracking number

            do 
            {
                $billNumber = random_int(100000000, 999999999);
            } 
            while ( Invoice::where('bill_number', $billNumber)->first() ); // make random unique bill number

            $v = Invoice::create(
            [
                'state'           => InvoiceState::Pending ,
                'items_count'     => $countProducts ,
                'total_price'     => $preInvoice['cart']['cost']['total_price'] ,
                'total_discount'  => $preInvoice['cart']['cost']['discount_price'] ,
                'tracking_number' => $trackingNumber ,
                'bill_number'     => $billNumber ,
                'billed_date'     => time() ,
                'store_id'        => $storeId ,
                'user_id'         => $request->user->id
            ]);

            foreach($preInvoice['cart']['items'] as $item)
            {
                InvoiceItem::create(
                [
                    'state'      => InvoiceState::Pending ,
                    'count'      => $item->count ,
                    'price'      => $item->current_price ,
                    'discount'   => $item->price['discount_price'] ,
                    'invoice_id' => $v->id ,
                    'store_product_id' => $item->product_id ,
                    'base_product_id'  => $item->base_product_id ,
                ]);

                StoreProduct::where('id', $item->product_id)->decrement('warehouse_count', $item->count);
            }

            UserCart::currentUser()->where('store_id', $storeId)->delete();
        }

        return 
            response()
            ->json(
            [
                'status'  => 201 ,
                'message' => 'Invoice created.' ,
                'factor_state' => true // change to invoice_state
            ], 201);
    }

}

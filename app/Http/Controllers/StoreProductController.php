<?php

namespace App\Http\Controllers;

use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\SearchHelper;
use App\Models\Product;
use App\Models\StoreProductDiscount;

/**
 * Store panel products management
 * 
 * @author Hosein marzban
 */ 
class StoreProductController extends Controller
{
    /**
     * Return all store's products with filter OR one of product by id
     * 
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getList(Request $request)
    {
        if( $request->query('id') != null ) {

            $product = StoreProduct::withTrashed()->find( $request->query('id') );
            $status = ( $product != null ) ? 200 : 404;

            return response()
            ->json([ 
                'status' => $status ,
                'message' => ($status == 200) ? 'OK' : 'No product found.' ,
                'product' => $product
            ], $status);
        }

        $products = Product::selectRaw('products.id, products.title, products.barcode, products.brand_id');

        $storeProducts = StoreProduct::selectRaw('store_products.*')->where('store_id', $request->user->store_id);
        
        $storeProducts = $storeProducts->leftJoinSub($products, 'products', function ($join) {
            $join->on('store_products.product_id', 'products.id');
        });

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            clone $storeProducts , 
            null , 
            [
                'title' => null ,
                'barcode' => null ,
                'brand_id' => null ,
                'category_id' => null
            ] , 
            'filterProducts'
        );

        extract($result);

        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No product found.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'products' => $data
        ], 200);
    }

    /**
     * Create new product or Update existing product by id
     * 
     * @see DataHelper::validate(Response, array) : array
     * 
     * @param Request $request
     * @param int|null $productId
     * 
     * @return Response
     */ 
    public function createOrUpdateProduct(Request $request, $productId = null)
    {
        $isCreate = ($productId == null) ? true : false;

        $v = DataHelper::validate( response() , $request->post() , 
        [
            'production_date' => [ 'تاریخ تولید', 'nullable|between:8,10' ] ,
            'expire_date' => [ 'تاریخ انقضا', 'nullable|between:8,10' ] ,
            
            'production_price' => [ 'قیمت تولید', 'nullable|numeric' ] ,
            'consumer_price' => [ 'قیمت مصرف کننده', 'required|filled|numeric' ] ,
            'store_price' => [ 'قیمت فروش', 'required|filled|numeric' ] ,
            
            'store_price_1' => [ 'قیمت فروش 1', 'nullable|numeric' ] ,
            'store_price_2' => [ 'قیمت فروش 2', 'nullable|numeric' ] ,
            
            'per_unit' => [ 'تعداد در واحد', 'required|numeric' ] ,
            'warehouse_count' => [ 'موجودی انبار', 'nullable|numeric' ] ,
            
            'delivery_description' => [ 'توضیحات ارسال کالا', 'nullable|max:500' ] ,
            'store_note' => [ 'توضیحات فروشنده', 'nullable|max:500' ] ,
            
            'cash_payment_discount' => [ 'درصد تخفیف نقدی', 'nullable|numeric|between:0,100' ] ,

            'commission' => [ 'پورسانت بازاریابی محصول', 'nullable|numeric' ] ,
            
            'product_id' => [ 'محصول الگو', 'required|numeric' ] ,
        ]);
        if( $v['code'] == 400 ) return $v['response'];

        $data = [
            'production_date' => DataHelper::post('production_date', '') ,
            'expire_date' => DataHelper::post('expire_date', '') ,
            
            'production_price' => (int)DataHelper::post('production_price', 0) ,
            'consumer_price' => (int)$request->post('consumer_price') ,
            'store_price' => (int)$request->post('store_price') ,
            
            'store_price_1' => (int)DataHelper::post('store_price_1', 0) ,
            'store_price_2' => (int)DataHelper::post('store_price_2', 0) ,
            
            'per_unit' => (int)DataHelper::post('per_unit', 1) ,
            'warehouse_count' => (int)DataHelper::post('warehouse_count', 0) ,
            
            'delivery_description' => DataHelper::post('delivery_description', '') ,
            'store_note' => DataHelper::post('store_note', '') ,

            'cash_payment_discount' => (int)DataHelper::post('cash_payment_discount', 0) ,
            'commission' => (int)DataHelper::post('commission', 0) ,
            
            'admin_confirmed' => -1 ,
            
            'product_id' => (int)$request->post('product_id') ,
        ];

        $product = null;

        if($isCreate) {
            $data['price_update_time'] = time();
            $data['store_id'] = $request->user->store_id;

            $product = StoreProduct::create($data);

            $productId = $product->id;
        }
        else {
            $product = StoreProduct::withTrashed()->find($productId);

            if( $product->production_price != $data['production_price']
                || $product->consumer_price != $data['consumer_price']
                || $product->store_price != $data['store_price']
                || $product->store_price_1 != $data['store_price_1']
                || $product->store_price_2 != $data['store_price_2'] ) {
                    
                    $data['price_update_time'] = time();
             }

            StoreProduct::withTrashed()
            ->where('id', $productId)
            ->update($data);

            $product = StoreProduct::withTrashed()->find($productId);
        }
        
        $discountsCount = (int)$request->post("discounts_count", 0);
        
        if($discountsCount > 0)
            StoreProductDiscount::where('product_id', $productId)->delete();

        for($i = 0; $i < $discountsCount; $i++) {

            $dvalue = str_replace(',', '', $request->post("product_discounts_{$i}_discount_value") );
            $fprice = str_replace(',', '', $request->post("product_discounts_{$i}_final_price") );

            StoreProductDiscount::create([
                'discount_type' => $request->post("product_discounts_{$i}_discount_type") ,
                'discount_value' => (int)$dvalue ,
                'final_price' => (int)$fprice ,
                'product_id' => $productId
            ]);
        }

        $status = $isCreate ? 201 : 200;
        return response()
        ->json([ 
            'status' => $status ,
            'message' =>  $isCreate ? 'Product created.' : 'Product updated.' ,
            'product' => $product
        ], $status);
    }
    
    /**
     * Soft delete or Restore store's product
     * 
     * @param Request $request
     * @param int $productId
     * 
     * @return Response
     */ 
    public function changeProductState(Request $request, $productId)
    {
        $check = StoreProduct::withTrashed()->find($productId);
        $msg = '';

        if($check->deleted_at == null) {
            StoreProduct::where('id', $productId)->delete();
            $msg = 'Product soft deleted.';
        }
        else {
            StoreProduct::withTrashed()->where('id', $productId)->restore();
            $msg = 'Product restored.';
        }

        $product = StoreProduct::withTrashed()->find($productId);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => $msg ,
            'product' => $product
        ], 200);
    }


}

<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Models\PublicProduct;
use App\Models\ProductCategory;
use App\Http\Helpers\PublicSearchHelper;
use App\Http\Helpers\SearchHelper;
use App\Models\SearchProduct;

/**
 * Public view of single product's information
 * 
 * @author Hosein marzban
 */ 
class PublicProductController extends Controller
{
    /**
     * Return detail for single product page
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function detail(Request $request)
    {
        $product = $request->product;

        $priceRange = StoreProduct::selectRaw('MIN(store_price) as range_min, MAX(store_price) as range_max')
        ->where('product_id', $product->id)
        ->where('warehouse_count', '>', 0)
        ->first();

        $brand = Brand::find($product->brand_id);
        
        $categoryId = ProductCategory::where('product_id', $product->id)->first()->category_id;
        $categoriesBreadCrump = PublicSearchHelper::categoryBreadCrump( Category::find($categoryId) );

        $breadCrumpPath = $categoriesBreadCrump;
        $breadCrumpPath[] = [ 
            'type' => 'brand' , 
            'title' => "{$brand->name} ({$brand->english_name})" , 
            'brand' => $brand->name , 
            'slug' => $brand->slug ,
        ];

        $sales = PublicSearchHelper::productSales($product->id);
        
        return response()
        ->json([
            'status' => 200 ,
            'message' => 'OK' ,
            'data' => [
                'prices_range' => [
                    'min' => $priceRange->range_min ?? 0 , 
                    'max' => $priceRange->range_max ?? 0
                ] ,
                'path' => $breadCrumpPath ,
                'product' => $product ,
                'brand' => $brand ,
                'categories' => $categoriesBreadCrump ,
                'stores' => $sales ,
            ]
        ], 200);
    }

    
    /**
     * Return product sales with filters
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function sales(Request $request)
    {
        $product = $request->product;
        $filters = SearchHelper::configQueryParams($request->query(), [
            'provinces' => null ,
            'cities' => null ,
        ]);

        foreach(['provinces', 'cities'] as $k) {
            if($filters[$k] != null)
                $filters[$k] = explode('|', $filters[$k]);
        }

        /* if( $filters['provinces'] == null && $filters['cities'] == null ) 
            return response()->json([
                'status' => 400 ,
                'message' => 'Define at least one of these parameters : provinces , cities'
            ], 400); */

        $filters['ignores'] = null;
        $filteredSales = PublicSearchHelper::productSales($product->id, $filters);
        
        $ignoreIDs = [];
        foreach($filteredSales as $f)
            $ignoreIDs[] = $f->store_id;

        $otherSales = PublicSearchHelper::productSales($product->id, [
            'ignores' => $ignoreIDs ,
            'provinces' => null , 'cities' => null ,
        ]);

        return response()
        ->json([
            'status' => 200 ,
            'message' => 'Ok' ,
            'counts' => [
                'filtered' => count($filteredSales) , 
                'others' => count($otherSales)
            ] ,
            'data' => [
                'filtered' => $filteredSales ,
                'others' => $otherSales
            ]
        ], 200);

    }

    /**
     * Return similar products
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function similars(Request $request)
    {
        $product = $request->product;

        $productStores = StoreProduct::
        selectRaw('product_id, MIN(store_price) as product_price, SUM(warehouse_count) as product_available_count')
        ->groupBy('product_id');
        
        $products = SearchProduct::leftJoinSub($productStores, 'product_stores', function ($join) {
            $join->on('products.id', 'product_stores.product_id');
        })
        ->where('products.id', '!=', $product->id);
        
        $c = ProductCategory::where('product_id', $product->id)->first()->category_id;
        $c = Category::find($c)->slug;

        $result = SearchHelper::dataWithFilters(
            [] , 
            clone $products , 
            null , 
            [ 
                'category' => $c ,

                'q' => null ,
                'brand' => null ,
                'fromPrice' => null , 'toPrice' => null , 'perPage' => null ,
                'price_from' => null , 'price_to' => null ,
                'available' => null ,
                'order' => null , 'sort' => 'time_desc' ,
                'state' => 'active'
            ] , 
            'filterSearchProducts'
        );
        extract($result);

        return response()
        ->json([
            'status' => 200 ,
            'message' => 'OK' ,
            'count' => $count['total'] ,
            'data' => $data
        ], 200);
    }


}

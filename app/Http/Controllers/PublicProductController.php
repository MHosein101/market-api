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

/**
 * Public view of product's information
 * 
 * @author Hosein marzban
 */ 
class PublicProductController extends Controller
{
    /**
     * Debug helper for developer
     * 
     * @param Request $request
     * @param string $productSlug
     * 
     * @return Response
     */ 
    public function detail(Request $request, $productSlug)
    {
        $product = PublicProduct::where('slug', $productSlug)->first();

        if($product == null)
            return response()
            ->json([ 
                'status' => 401 ,
                'message' => 'Product not found.'
            ], 401);

        $priceRange = StoreProduct::selectRaw('MIN(store_price) as range_min, MAX(store_price) as range_max')
        ->where('product_id', $product->id)
        ->where('warehouse_count', '>', 0)
        ->first();

        $brand = Brand::find($product->brand_id);
        
        $categoryId = ProductCategory::where('product_id', $product->id)->first()->category_id;
        $categoriesBreadCrump = PublicSearchHelper::categoryBreadCrump( Category::find($categoryId) );

        $breadCrumpPath = $categoriesBreadCrump;
        $brandName = "{$brand->name} ({$brand->english_name})";

        $breadCrumpPath[] = [ 
            'type' => 'brand' , 
            'title' => $brandName , 
            'brand' => $brand->name , 
            'category' => ''
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
                'sales' => $sales ,
            ]
        ], 200);
    }
}

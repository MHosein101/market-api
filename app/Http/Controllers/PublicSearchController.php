<?php

namespace App\Http\Controllers;

use App\Http\Helpers\PublicSearchHelper;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Models\SearchProduct;
use App\Models\MenuCategory;
use App\Http\Helpers\SearchHelper;

/**
 * Search related methods for public access
 * 
 * @author Hosein marzban
 */ 
class PublicSearchController extends Controller
{

    /**
     * All users can search in products and filter
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function search(Request $request)
    {
        $productStores = StoreProduct::
        selectRaw('product_id, MIN(store_price) as product_price, SUM(warehouse_count) as product_available_count')
        ->groupBy('product_id');
        
        $products = SearchProduct::leftJoinSub($productStores, 'product_stores', function ($join) {
            $join->on('products.id', 'product_stores.product_id');
        });

        $priceRange = clone $products;
        $priceRange = $priceRange->selectRaw('deleted_at, MIN(product_price) as range_min, MAX(product_price) as range_max');
        
        if( $request->query('available') == '1' || $request->query('available') == 'true' )
            $priceRange = $priceRange->where('product_available_count', '>', 0);
        
        $priceRange = $priceRange->groupBy('deleted_at')->first();

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            clone $products , 
            null , 
            [ 
                'q' => null ,
                'brand' => null ,
                'category' => null ,
                
                'fromPrice' => null ,
                'toPrice' => null ,
                'perPage' => null ,

                'price_from' => null ,
                'price_to' => null ,

                'available' => null ,

                'order' => null ,
                'sort' => 'time_desc' ,

                'state' => 'active'
            ] , 
            'filterSearchProducts'
        );
        extract($result);

        $qcategory = $request->query('category');
        $qquery = $request->query('q');

        $relatedCategories = PublicSearchHelper::relatedCategories($qcategory, clone $products, $qquery);
        $relatedBrands = PublicSearchHelper::relatedBrands(clone $products, $qquery);
        $categoriesTypeTitle = PublicSearchHelper::categoryTypeTitle($qcategory);

        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No product found.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'data' => [
                'products_count' => $count['total'] ,
                'price_range' => [
                    'min' => $priceRange->range_min ?? 0 , 
                    'max' => $priceRange->range_max ?? 0
                ] ,
                'products' => $data ,
                'brands' => $relatedBrands ,
                'categories' => [
                    'title' => $categoriesTypeTitle ,
                    'data' => $relatedCategories['data'] ?? $relatedCategories ,
                    'list' => $relatedCategories['list'] ?? []
                ] ,
            ]
        ], 200);
    }

    /**
     * Return all brands
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function brands(Request $request)
    {
        $brands = Brand::get();

        $status = ( count($brands) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No brands found.' ,
            'data' => $brands
        ], 200);
    }

    /**
     * Return all categories and their children
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function categories(Request $request)
    {
        $categories = MenuCategory::where('parent_id', null)->get();

        $status = ( count($categories) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No category found.' ,
            'data' => $categories
        ], 200);
    }

    /**
     * Return all categories and their children
     *
     * @param Request $request
     * @param string $categorySlug
     * 
     * @return Response
     */ 
    public function categoryBreadCrump(Request $request, $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->first();

        if($category == null)
            return response()
            ->json([ 
                'status' => 401 ,
                'message' => 'Category not found.'
            ], 401);

        $path = PublicSearchHelper::categoryBreadCrump($category);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'data' => $path
        ], 200);
    }
    /**
     * Return category and it's children as multistep until first parent
     *
     * @param Request $request
     * @param string $categorySlug
     * 
     * @return Response
     */ 
    public function categoryChildrenTree(Request $request, $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->first();
        $subs = [];

        if($category == null)
            return response()
            ->json([ 
                'status' => 401 ,
                'message' => 'Category not found.'
            ], 401);

        $category = PublicSearchHelper::categoryUntilTopParent($category);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'data' => $category
        ], 200);
    }

}

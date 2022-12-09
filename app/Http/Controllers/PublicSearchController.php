<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Models\SearchProduct;
use App\Models\SearchCategory;
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
        $products = SearchProduct::selectRaw('*');

        $productStores = StoreProduct::selectRaw('product_id, MIN(store_price) as product_price, COUNT(warehouse_count) as product_stores_count')
        ->where('warehouse_count', '>', 0)
        ->groupBy('product_id');
        
        $products = $products->leftJoinSub($productStores, 'product_stores', function ($join) {
            $join->on('products.id', 'product_stores.product_id');
        });

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            $products , 
            null , 
            [ 
                'q' => null ,
                'brand' => null ,
                'category' => null ,
                
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

        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No product found.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'data' => [
                'products_count' => -1 ,
                'price_range' => [
                    'min' =>  -1 , 
                    'max' =>  -1 
                ] ,
                'brands' => [] ,
                'categories' => [] ,
                'products' => $data
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
        $categories = SearchCategory::where('parent_id', null)->get();

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
        $category = Category::where('slug', $categorySlug)->get()->first();

        if($category == null)
            return response()
            ->json([ 
                'status' => 401 ,
                'message' => 'Category not found.'
            ], 401);

        $path = [];

        $path[] = [ 
            'type' => 'category' , 
            'title' => $category->name
        ];

        while($category->parent_id != null) {

            $category = Category::find($category->parent_id);

            $path[] = [ 
                'type' => 'category' , 
                'title' => $category->name 
            ];
        }

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'data' => array_reverse($path)
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
        $category = Category::where('slug', $categorySlug)->get()->first();
        $subs = [];

        if($category == null)
            return response()
            ->json([ 
                'status' => 401 ,
                'message' => 'Category not found.'
            ], 401);

            
        $subs = Category::where('parent_id', $category->id)->get();

        if( count($subs) == 0 ) {
            $category = Category::find($category->parent_id);
            $subs = Category::where('parent_id', $category->id)->get();
        }
            
        $category['sub_categories'] = $subs;

        while( $category->parent_id != null ) {
            $parent = Category::find($category->parent_id);
            $parent['sub_categories'] = $category;

            $category = $parent;
        }

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'data' => $category
        ], 200);
    }

}

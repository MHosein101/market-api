<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductTag;
use App\Models\MenuCategory;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Models\SearchProduct;
use App\Models\ProductCategory;
use App\Models\UserSearchQuery;
use App\Http\Helpers\SearchHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\PublicSearchHelper;
use App\Http\Helpers\PublicHomePageHelper;

class PublicSearchController extends Controller
{

    /**
     * All users can search in products and filter
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function search(Request $request)
    {
        $productStores = StoreProduct
        ::selectRaw('product_id, MIN(store_price) as product_price, SUM(warehouse_count) as product_available_count')
        ->groupBy('product_id');
        
        $products = SearchProduct::leftJoinSub($productStores, 'product_stores', function ($join) 
        {
            $join->on('products.id', 'product_stores.product_id');
        });
        
        $productTags = ProductTag
        ::selectRaw("product_id, GROUP_CONCAT(name SEPARATOR  '|') as tags")
        ->groupBy('product_id');
        
        $products = $products->leftJoinSub($productTags, 'product_tags', function ($join) 
        {
            $join->on('products.id', 'product_tags.product_id');
        });

        // to get min and max price range of search results
        $priceRange = clone $products;
        $priceRange = $priceRange->selectRaw('deleted_at, MIN(product_price) as range_min, MAX(product_price) as range_max');
        
        if( $request->query('available') == '1' || $request->query('available') == 'true' )
        {
            $priceRange = $priceRange->where('product_available_count', '>', 0);
        }
        
        $priceRange = $priceRange
        ->groupBy('deleted_at')
        ->first();

        unset($request['state']); // ignore state

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            clone $products , 
            null , 
            [ 
                'q'        => null ,
                'brand'    => null ,
                'category' => null ,
                
                'fromPrice' => null ,
                'toPrice'   => null ,
                'perPage'   => null ,

                'price_from' => null ,
                'price_to'   => null ,

                'available' => null ,

                'order' => null ,
                'sort'  => 'time_desc' ,

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

        if($qquery)
        {
            if($request->user) // add to user search history
            {
                UserSearchQuery::create(
                [   
                    'text'    => $qquery ,
                    'user_id' => $request->user->id
                ]);
            }

            // increase tags rating

            $qwords = explode(' ', $qquery);

            $rating = new ProductTag;

            foreach($qwords as $w)
            {
                $rating = $rating->where('name', 'like', "%$w%");
            }

            $rating->increment('rate');
        }

        $status = count($data) > 0 ? 200 : 204;

        return 
            response()
            ->json(
            [ 
                'status'  => $status ,
                'message' => $status == 200 ? 'OK' : 'No product found.' ,
                'count'   => $count ,
                'pagination' => $pagination ,
                'data' => 
                [
                    'products_count' => $count['total'] ,
                    'price_range' => 
                    [
                        'min' => $priceRange->range_min ?? 0 , 
                        'max' => $priceRange->range_max ?? 0
                    ] ,
                    'products' => $data ,
                    'brands'   => $relatedBrands ,
                    'categories' => 
                    [
                        'title' => $categoriesTypeTitle ,
                        'data'  => $relatedCategories['data'] ?? $relatedCategories ,
                        'list'  => $relatedCategories['list'] ?? []
                    ] ,
                ] ,
                'search_bar' => PublicSearchHelper::searchBarData($request->user)
            ], 200);
    }


    /**
     * Return list of suggested queries based on input
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function suggestQuery(Request $request)
    {
        if(!$request->query('q'))
        {
            return 
                response()
                ->json(
                [
                    'status'  => 400 ,
                    'message' => 'Query string parameter [q] is required'
                ], 400); 
        }

        /* it will not work !

        $categoriesIDs = ProductCategory
        ::selectRaw('product_id, category_id')
        ->whereIn('id', function($query)
        {
            $query
            ->select('MIN(id)')
            ->from('product_categories')
            ->groupBy('product_id');
        }); */

        $categoriesIDs = 'SELECT product_id, category_id FROM `product_categories` WHERE id IN ( SELECT MIN(id) FROM `product_categories` GROUP BY product_id ) AND deleted_at is NULL';
        
        $categoriesDetails = Category
        ::selectRaw('id as category_id, name as category_name, slug as category_slug');
        
        $search = ProductTag
        ::selectRaw('name as query, category_name, category_slug')

        ->leftJoinSub($categoriesIDs, 'categories_ids', function ($join) 
        {
            $join->on('product_tags.product_id', 'categories_ids.product_id');
        })

        ->leftJoinSub($categoriesDetails, 'categories_details', function ($join) 
        {
            $join->on('categories_ids.category_id', 'categories_details.category_id');
        });

        $tags = [];

        $words = explode(' ', $request->query('q'));
        
        foreach($words as $w)
        {
            $search = $search->where('name', 'like', "%$w%");
        }

        $tags = $search
        ->distinct()
        // ->orderBy('rate', 'desc') // could not use it because of production server mysql setting
        ->get()
        ->take(15);

        return 
            response()
            ->json(
            [ 
                'status'      => 200 ,
                'message'     => 'OK' ,
                'suggestions' => $tags
            ], 200);
    }

    /**
     * Return all brands
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function brands(Request $request)
    {
        $brands = Brand::get();

        $status = count($brands) > 0 ? 200 : 204;

        return 
            response()
            ->json(
            [ 
                'status'  => $status ,
                'message' => $status == 200 ? 'OK' : 'No brands found.' ,
                'data'    => $brands
            ], 200);
    }

    /**
     * Return all categories and their children
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function categories(Request $request)
    {
        $categories = MenuCategory::where('parent_id', null)->get();

        $status = count($categories) > 0 ? 200 : 204;
        
        return
            response()
            ->json(
            [ 
                'status'  => $status ,
                'message' => $status == 200 ? 'OK' : 'No category found.' ,
                'data'    => $categories
            ], 200);
    }

    /**
     * Return all categories and their children
     *
     * @param \Illuminate\Http\Request
     * @param string $categorySlug
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function categoryBreadCrump(Request $request, $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->first();

        if($category == null)
        {
            return 
                response()
                ->json(
                [ 
                    'status'  => 401 ,
                    'message' => 'Category not found.'
                ], 401);
        }

        $path = PublicSearchHelper::categoryBreadCrump($category);

        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'OK' ,
                'data'    => $path
            ], 200);
    }
    /**
     * Return category and it's children as multistep until first parent
     *
     * @param \Illuminate\Http\Request
     * @param string $categorySlug
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function categoryChildrenTree(Request $request, $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->first();

        if($category == null)
        {
            return 
                response()
                ->json(
                [ 
                    'status'  => 401 ,
                    'message' => 'Category not found.'
                ], 401);
        }

        $category = PublicSearchHelper::categoryUntilTopParent($category);

        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'OK' ,
                'data'    => $category
            ], 200);
    }

}

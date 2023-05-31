<?php

namespace App\Http\Helpers;

use App\Models\Brand;
use App\Models\Store;
use App\Models\Category;
use App\Models\ProductTag;
use App\Models\ProductCategory;
use App\Models\UserSearchQuery;
use App\Models\PublicStoreProduct;

/**
 * Helper methods for public search data
 */ 
class PublicSearchHelper
{
    /**
     * Return parent to target category as tree
     *
     * @param Category $category
     * 
     * @return array
     */ 
    public static function categoryUntilTopParent($category)
    {
        $c = 0;

        $catsId = [ $category->id ];

        $currentId = $category->id;

        while( $c != 4 ) 
        {
            if( $category->parent_id == null )
            {
                 break;
            }

            $category = Category::find($category->parent_id);

            $catsId[] = $category->id;

            $c++;
        }

        $catsId = array_reverse($catsId);

        $categories = 
        [ 
            'parent' => null , 
            'sub1'  => null , 
            'sub2'  => null , 
            'sub3'  => null, 
            'sub4'  => null 
        ];

        $i = 0;

        $lastCatId = null;

        $keys = array_keys($categories);
        
        $list = 
        [ 
            [ 
                'type'  => 'unavailable', 
                'title' => 'زیر دسته ای وجود ندارد' 
            ] 
        ];

        foreach($keys as $k) 
        {
            if( isset($catsId[$i]) ) 
            {
                $category = Category::find($catsId[$i]);

                $categories[$k] = 
                [ 
                    'type'       => 'category' , 
                    'title'      => $category->name , 
                    'slug'       => $category->slug ,
                    'is_current' => $currentId == $category->id ,
                    'is_list'    => false
                ];

                $lastCatId = $category->id;

                $i++;
            }
            else 
            {
                $k = $keys[$i-1];

                $subs = Category::where('parent_id', $lastCatId)->get();

                if( count($subs) == 0 && $i != 1 ) 
                {
                    $categories[$k] = null;

                    $k = $keys[$i-2];

                    $lastCatId = Category::find($lastCatId)->parent_id;

                    $subs = Category::where('parent_id', $lastCatId)->get();

                    $list = [];
                }

                $categories[$k]['is_list'] = true;

                if( count($subs) > 0 )
                {
                    $list = [];
                }

                foreach($subs as $s) 
                {
                    $list[] = 
                    [
                        'type'       => 'category' , 
                        'title'      => $s->name , 
                        'slug'       => $s->slug ,
                        'is_current' => $currentId == $s->id ,
                    ];
                }

                break;
            }

        }

        unset($categories['sub4']);

        return 
        [ 
            'data' => $categories , 
            'list' => $list 
        ];

        /* $subs = Category::where('parent_id', $category->id)->get();
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
        return $category; */
    }

    /**
     * Return value of categories title by search params
     *
     * @param string $queryCategorySlug
     * 
     * @return string
     */ 
    public static function categoryTypeTitle($queryCategorySlug)
    {
        if($queryCategorySlug == null)
        {
            return 'دسته های پیشنهادی';
        }
        
        $category = Category::where('slug', $queryCategorySlug)->first();

        if($category == null)
        {
            return 'دسته های پیشنهادی';
        }

        $subs = Category::where('parent_id', $category->id)->count();

        if( $subs > 0 || $category->parent_id == null )
        {
            return 'دسته های دقیق تر';
        }
        else 
        { 
            return 'دسته های مشابه'; 
        }

    }

    /**
     * Return related categories of user search
     *
     * @param string $queryCategoryName
     * @param object $qbuilder
     * @param string $q
     * 
     * @return array
     */ 
    public static function relatedCategories($queryCategoryName, $qbuilder, $q)
    {
        if( $queryCategoryName ) 
        {
            $category = Category::where('slug', $queryCategoryName)->first();

            if( $category != null ) 
            {
                return PublicSearchHelper::categoryUntilTopParent($category);
            }

        }

        else if ($q) 
        {
            $productsIds = $qbuilder->selectRaw('id')->where('products.title', 'LIKE', "%$q%");

            $categoryIds = ProductCategory::selectRaw('category_id')
            ->whereIn('product_id', $productsIds)
            ->distinct()
            ->take(50)
            ->inRandomOrder()
            ->get();

            $categories = [];

            foreach($categoryIds as $c) 
            {
                $cat =  Category::find($c->category_id);

                if($cat)
                {
                    $categories[] = $cat;
                }
            }
                
            return $categories;
        }

        return [];
    }

    /**
     * Return bread crump from category
     *
     * @param Category $category
     * 
     * @return array
     */ 
    public static function categoryBreadCrump($category)
    {
        $path = [];

        $path[] = 
        [ 
            'type'  => 'category' , 
            'title' => $category->name
        ];

        while( $category->parent_id != null ) 
        {
            $category = Category::find($category->parent_id);

            $path[] = 
            [ 
                'type'  => 'category' , 
                'title' => $category->name
            ];
        }

        return array_reverse($path);
    }

    /**
     * Return related brands of user search
     *
     * @param object $qbuilder
     * @param string $q
     * 
     * @return Category[]
     */ 
    public static function relatedBrands($qbuilder, $q)
    {
        if( $q == null )
        {
            return Brand::get();
        }
            
        $brandsIds = $qbuilder
        ->selectRaw('brand_id as id')
        ->where('products.title', 'LIKE', "%$q%")
        ->distinct()
        ->inRandomOrder()
        ->get();

        $brands = [];

        foreach($brandsIds as $b)
        {
            $brands[] = Brand::find($b->id);
        }

        return $brands;
    }

    /**
     * Return product store's sales with filter
     *
     * @param int $productId
     * @param array|null $filters
     * 
     * @return PublicStoreProduct[]
     */ 
    public static function productSales($productId, $filters = null)
    {
        $stores = Store::selectRaw('id, name as title, province, city');

        $offers = PublicStoreProduct::leftJoinSub($stores, 'stores', function ($join) 
        {
            $join->on('store_products.store_id', 'stores.id');
        });

        $offers = $offers
        ->selectRaw('store_products.id as product_id, store_id, title, store_products.store_price as price, province, city, warehouse_count > 0 as is_available, cash_payment_discount')
        ->where('product_id', $productId)
        ->orderBy('is_available', 'desc')
        ->orderBy('store_price', 'asc');

        if($filters != null) 
        {
            extract($filters); // $provinces, $cities, $ignores

            $offers->where(function($query) use ($provinces, $cities, $ignores) 
            {
                if( $provinces != null )
                {
                    $query->whereIn('stores.province', $provinces);
                }

                if( $cities != null )
                {
                    $query->orWhereIn('stores.city', $cities);
                }

                if( $ignores != null )
                {
                    $query->whereNotIn('stores.id', $ignores);
                }
            });
        }
        
        return $offers->get();

    }

    
    /**
     * Return search bar data
     * 
     * @param object|null $user
     * 
     * @return array
     */ 
    public static function searchBarData($user)
    {
        $history = []; // user search history
        $popular = []; // popular searched queries

        if($user)
        {
            $history = UserSearchQuery::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->pluck('text')
            ->unique()
            ->values()
            ->take(15);
        }

        $popular = ProductTag::orderBy('rate', 'desc')
        ->get()
        ->pluck('name')
        ->unique()
        ->values()
        ->take(5);

        return 
        [
            'user'    => $history ,
            'popular' => $popular
        ];
    }

}
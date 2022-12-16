<?php

namespace App\Http\Helpers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductCategory;
use App\Models\PublicStoreProduct;

/**
 * Helper methods for public search data
 * 
 * @author Hosein marzban
 */ 
class PublicSearchHelper
{
    /**
     * Return parent to target category as tree
     *
     * @param Category $category
     * 
     * @return Category
     */ 
    public static function categoryUntilTopParent($category)
    {
        $c = 0;
        $catsId = [ $category->id ];
        $currentId = $category->id;

        while($c != 4) {
            if($category->parent_id == null) break;

            $category = Category::find($category->parent_id);
            $catsId[] = $category->id;
            $c++;
        }

        $catsId = array_reverse($catsId);
        $categories = [ 'parent' => null , 'sub1' => null , 'sub2' => null , 'sub3' => null, 'sub4' => null ];

        $i = 0;
        $lastCatId = null;
        $keys = array_keys($categories);
        
        $list = [ 'type' => 'unavailable', 'title' => 'زیر دسته ای وجود ندارد' ];

        foreach($keys as $k) {

            if( isset($catsId[$i]) ) {
                $category = Category::find($catsId[$i]);
                $categories[$k] = [ 
                    'type' => 'category' , 
                    'title' => $category->name , 
                    'slug' => $category->slug ,
                    'is_current' => $currentId == $category->id ,
                    'is_list' => false
                ];
                $lastCatId = $category->id;
                $i++;
            }
            else {
                $k = $keys[$i-1];
                $subs = Category::where('parent_id', $lastCatId)->get();

                if(count($subs) == 0) {
                    $categories[$k] = null;
                    $k = $keys[$i-2];
                    $lastCatId = Category::find($lastCatId)->parent_id;
                    $subs = Category::where('parent_id', $lastCatId)->get();
                }

                $categories[$k]['is_list'] = true;
                $list = [];

                foreach($subs as $s) {
                    $list[] = [
                        'type' => 'category' , 
                        'title' => $s->name , 
                        'slug' => $s->slug ,
                        'is_current' => $currentId == $s->id ,
                    ];
                }
                break;
            }
        }

        unset($categories['sub4']);

        return [ 
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
            return 'دسته های پیشنهادی';
        
        $category = Category::where('slug', $queryCategorySlug)->first();

        if($category == null)
            return 'دسته های پیشنهادی';

        $subs = Category::where('parent_id', $category->id)->count();

        if($subs > 0)
            return 'دسته های دقیق تر';
        else
            return 'دسته های مشابه';
    }

    /**
     * Return related categories of user search
     *
     * @param string $queryCategoryName
     * @param QueryBuilder $qbuilder
     * @param string $q
     * 
     * @return Category[]
     */ 
    public static function relatedCategories($queryCategoryName, $qbuilder, $q)
    {
        if($queryCategoryName) {
            $category = Category::where('slug', $queryCategoryName)->first();
            if($category)
                return PublicSearchHelper::categoryUntilTopParent($category);
        }
        else if ($q) {
            $productsIds = $qbuilder->selectRaw('id')->where('products.title', 'LIKE', "%$q%");

            $categoryIds = ProductCategory::selectRaw('category_id')
            ->whereIn('product_id', $productsIds)
            ->distinct()
            ->take(50)->inRandomOrder()->get();

            $categories = [];
            foreach($categoryIds as $c) {
                $cat =  Category::find($c->category_id);
                if($cat)
                    $categories[] = $cat;
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

        return array_reverse($path);
    }

    /**
     * Return related brands of user search
     *
     * @param QueryBuilder $qbuilder
     * @param string $q
     * 
     * @return Category[]
     */ 
    public static function relatedBrands($qbuilder, $q)
    {
        if($q == null)
            return Brand::get();
            
        $brandsIds = $qbuilder->selectRaw('brand_id as id')
        ->where('products.title', 'LIKE', "%$q%")
        ->distinct()->inRandomOrder()->get();

        $brands = [];
        foreach($brandsIds as $b) 
            $brands[] = Brand::find($b->id);

        return $brands;
    }

    /**
     * Return product store's sales with filter
     *
     * @param int $productId
     * @param array|null $filters
     * 
     * @return array
     */ 
    public static function productSales($productId, $filters = null)
    {
        return PublicStoreProduct::where('product_id', $productId)
        ->orderBy('store_price', 'asc')
        ->get();
    }


}
<?php

namespace App\Http\Helpers;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SearchProduct;
use App\Models\UserAccountType;

class SearchHelper
{
    /**
     * Replace default values for fields that are not included in query string data
     *
     * @param array $query
     * @param array $defaults
     * 
     * @return array
     */ 
    public static function configQueryParams($query, $defaults) 
    {
        $params = $defaults;

        foreach($defaults as $key => $val)
            $params[$key] = isset($query[$key]) ? $query[$key] : $val;

        return $params;
    }

    /**
     * Return data of class with pagination
     * If $filterFunction is not null then call one of the filter functions inside SearchHelper class
     * 
     * @see SearchHelper::configQueryParams(array, array) : array
     *
     * @param array $queryString
     * @param Model|QueryBuilder $class
     * @param string|null $select
     * @param array $filterParams
     * @param string|null $filterFunction
     * 
     * @return Model[]
     */ 
    public static function dataWithFilters($queryString, $class, $select = '*', $filterParams = [], $filterFunction = null) 
    {
        $defaultParams = [
            'page' => 1 ,
            'limit' => 20 ,
            'state' => 'all' , // all active trashed
            'order' => 'desc' // asc desc null
        ];

        foreach($filterParams as $k => $v)
            $defaultParams[$k] = $v;

        $query = SearchHelper::configQueryParams($queryString, $defaultParams);

        $qbuilder = ($select == null) ? $class : $class::selectRaw($select);

        switch( $query['state'] ) {
            case 'all': 
                $qbuilder = $qbuilder->withTrashed(); break;
            case 'trashed': 
                $qbuilder = $qbuilder->onlyTrashed(); break;
        }

        if($filterFunction != null)
            $qbuilder = SearchHelper::$filterFunction(clone $qbuilder, $query);

        $count = clone $qbuilder;
        $count = count( $count->get() );

        $lastPage = ceil( $count / $query['limit'] );

        $take = $query["limit"];
        $skip = ( $query["page"] - 1 ) * $query["limit"];

        if($query['order'] != null)
            $qbuilder = $qbuilder->orderBy('created_at', $query['order']);

        $data = $qbuilder->skip($skip)->take($take)->get();

        return [
            'data' => $data ,
            'count' => [
                'current' => count($data) ,
                'total' => $count ,
                'limit' => (int)$query['limit']
            ] ,
            'pagination' => [
                'current' => (int)$query['page'] ,
                'last' => $lastPage
            ]
        ];
    }

    /**
     * Add users filters to query builder
     *
     * @param QueryBuilder $qbuilder
     * @param array $query
     * 
     * @return QueryBuilder
     */ 
    public static function filterUsers($qbuilder, $query) 
    {
        if( $query['number'] != null ) {

            $n = $query['number'];
            $qbuilder = $qbuilder
            ->where(function($qb) use ($query, $n) {
                return $qb
                    ->where('phone_number_primary','LIKE', "%$n%")
                    ->orWhere('phone_number_secondary','LIKE', "%$n%")
                    ->orWhere('house_number','LIKE', "%$n%");
            });
        }

        foreach(['full_name', 'national_code'] as $field) {
            if( $query[$field] != null )
                $qbuilder = $qbuilder->where($field,'LIKE', "%{$query[$field]}%");
        }

        return $qbuilder;
    }

    /**
     * Add categories filters to query builder
     *
     * @param QueryBuilder $qbuilder
     * @param array $query
     * 
     * @return QueryBuilder
     */ 
    public static function filterCategories($qbuilder, $query) 
    {
        if( $query['name'] != null )
            $qbuilder = $qbuilder->where('name', 'LIKE', "%{$query['name']}%");

        return $qbuilder;
    }

    /**
     * Add brands filters to query builder
     *
     * @param QueryBuilder $qbuilder
     * @param array $query
     * 
     * @return QueryBuilder
     */ 
    public static function filterBrands($qbuilder, $query) 
    {
        if( $query['name'] != null ) {
            $qbuilder = $qbuilder
            ->where(function($qb) use ($query) {
                return $qb
                    ->where('name','LIKE', "%{$query['name']}%")
                    ->orWhere('english_name','LIKE', "%{$query['name']}%");
            });
        }
            
        if( $query['company'] != null )
            $qbuilder = $qbuilder->where('company','LIKE', "%{$query['company']}%");

        return $qbuilder;
    }
    
    /**
     * Add products filters to query builder
     *
     * @param QueryBuilder $qbuilder
     * @param array $query
     * 
     * @return QueryBuilder
     */ 
    public static function filterProducts($qbuilder, $query) 
    {
        if($query['category_id'] != null) {
            
            $productCategories = ProductCategory::selectRaw('product_id, category_id')->where('category_id', $query['category_id']);

            $qbuilder = $qbuilder->leftJoinSub($productCategories, 'products_categories_ids', function ($join) {
                $join->on('products.id', 'products_categories_ids.product_id');
            });

            $qbuilder = $qbuilder->where('category_id', $query['category_id']);
        }

        foreach(['title', 'barcode'] as $field) {
            if( $query[$field] != null )
                $qbuilder = $qbuilder->where("products.$field",'LIKE', "%{$query[$field]}%");
        }
        
        foreach(['brand_id'] as $field) {
            if( $query[$field] != null )
                $qbuilder = $qbuilder->where("products.$field", $query[$field]);
        }

        return $qbuilder;
    }

    /**
     * Add stores filters to query builder
     *
     * @param QueryBuilder $qbuilder
     * @param array $query
     * 
     * @return QueryBuilder
     */ 
    public static function filterStores($qbuilder, $query) 
    {
        if($query['state'] == 'pending')
            $qbuilder = $qbuilder->withTrashed()->where('admin_confirmed', -1);

        if( $query['number'] != null ) {

            $n = $query['number'];
            $qbuilder = $qbuilder
            ->where(function($qb) use ($query, $n) {
                return $qb
                    ->where('owner_phone_number','LIKE', "%$n%")
                    ->orWhere('second_phone_number','LIKE', "%$n%")
                    ->orWhere('office_number','LIKE', "%$n%")
                    ->orWhere('warehouse_number','LIKE', "%$n%");
            });
        }

        foreach(['name', 'economic_code', 'province', 'city'] as $field) {
            if( $query[$field] != null )
                $qbuilder = $qbuilder->where($field,'LIKE', "%{$query[$field]}%");
        }

        if($query['national_code'] != null) {
            
            $usersNationalCodes = User::selectRaw('users.id as user_id, users.national_code as owner_national_code');

            $qbuilder = $qbuilder->leftJoinSub($usersNationalCodes, 'users_national_codes', function ($join) {
                $join->on('stores.user_id', 'users_national_codes.user_id');
            });

            $qbuilder = $qbuilder->where('owner_national_code', 'LIKE', "%{$query['national_code']}%");
        }


        return $qbuilder;
    }
    
    /**
     * Return items that user marked as favorite or in it's history
     *
     * @param int $userId
     * @param Modle $class
     * @param array $query
     * 
     * @return Model[]
     */ 
    public static function getUserMarkedItems($userId, $class, $query) 
    {
        $markedProducts = $class::selectRaw('user_id, product_id, created_at as marked_at');

        $products = SearchProduct::selectRaw('products.*');
        
        $products = $products->leftJoinSub($markedProducts, 'marked_items', function ($join) {
            $join->on('products.id', 'marked_items.product_id');
        });

        $products = $products->where('user_id', $userId)->orderBy('marked_at', 'desc');

        $result = SearchHelper::dataWithFilters(
            $query ,
            $products ,
            null ,
            [
                'state' => 'active' ,
                'order' => null
            ] , 
            null
        );

        return $result;
    }

    /**
     * Add front search filters to products query builder
     *
     * @see SearchHelper::filterProducts(QueryBuilder, array) : QueryBuilder
     * 
     * @param QueryBuilder $qbuilder
     * @param array $query
     * 
     * @return QueryBuilder
     */ 
    public static function filterSearchProducts($qbuilder, $query) 
    {
        $query['title'] = $query['q'];
        $query['barcode'] = null;
        $query['brand_id'] = $query['brand'];
        $query['category_id'] = $query['category'];

        $qbuilder = SearchHelper::filterProducts(clone $qbuilder, $query);

        switch($query['sort']) {
            case 'time_desc': $qbuilder = $qbuilder->orderBy('created_at', 'desc');
                break;
            case 'price_min': $qbuilder = $qbuilder->orderBy('product_price', 'asc');
                break;
            case 'price_max': $qbuilder = $qbuilder->orderBy('product_price', 'desc');
                break;
        }

        if( $query['price_from'] != null && $query['price_to'] != null ) {
            $qbuilder = $qbuilder->where('product_price', '>=', $query['price_from'])
                                 ->where('product_price', '<=', $query['price_to']);
        }

        if( $query['available'] == '1' )
            $qbuilder = $qbuilder->where('product_stores_count', '!=', null);

        return $qbuilder;
    }

}
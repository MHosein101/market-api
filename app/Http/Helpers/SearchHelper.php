<?php

namespace App\Http\Helpers;

use App\Models\User;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchProduct;
use App\Models\ProductCategory;
use App\Models\UserAccountType;
use App\Models\UserAnalytic;
use App\Models\UserMarkedProduct;
use App\Models\UserMarkedProductAnalytic;

/**
 * Helper methods for searching data
 * 
 * @author Hosein marzban
 */ 
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
        {
            $params[$key] = 
            isset($query[$key]) 
            ? $query[$key] 
            : $val;
        }

        return $params;
    }

    /**
     * Return data of class with pagination
     * If $filterFunction is not null then call one of the filter functions inside SearchHelper class
     * 
     * @see FilterHelper class methods
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
        $defaultParams = 
        [
            'page'  => 1 ,
            'limit' => 20 ,
            'state' => 'all' , // all active trashed
            'order' => 'desc' // asc desc null
        ];

        foreach($filterParams as $k => $v) 
        {
            $defaultParams[$k] = $v;
        }

        $query = SearchHelper::configQueryParams($queryString, $defaultParams);

        $qbuilder = $select == null
        ? $class 
        : $class::selectRaw($select);

        switch( $query['state'] ) 
        {
            case 'all': 

                $qbuilder = $qbuilder->withTrashed(); 
                break;

            case 'trashed': 

                $qbuilder = $qbuilder->onlyTrashed(); 
                break;
        }

        if( $filterFunction != null ) 
        {
            $qbuilder = FilterHelper::$filterFunction(clone $qbuilder, $query);
        }

        $count = clone $qbuilder;

        $count = count( $count->get() );

        $lastPage = ceil( $count / $query['limit'] );

        $take = $query["limit"];

        $skip = ( $query["page"] - 1 ) * $query["limit"];

        if($query['order'] != null)
        {
            $qbuilder = $qbuilder->orderBy('created_at', $query['order']);
        }

        $data = $qbuilder
        ->skip($skip)
        ->take($take)
        ->get();

        return 
        [
            'data'  => $data ,
            'count' => [
                'current' => count($data) ,
                'total'   => $count ,
                'limit'   => (int)$query['limit']
            ] ,
            'pagination' => [
                'current' => (int)$query['page'] ,
                'last'    => $lastPage
            ]
        ];
    }
    
    /**
     * Return items that user marked as favorite or in it's history
     * 
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
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

        if( $class == UserAnalytic::class ) 
        {
            $products = UserMarkedProductAnalytic::selectRaw('products.*');
        }
        else 
        {
            $products = UserMarkedProduct::selectRaw('products.*');
        }
        
        $products = $products->leftJoinSub($markedProducts, 'marked_items', function ($join) 
        {
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


}
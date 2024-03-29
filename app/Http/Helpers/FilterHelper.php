<?php

namespace App\Http\Helpers;

use App\Models\User;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InvoiceState;
use App\Models\Product;
use App\Models\SearchProduct;
use App\Models\ProductCategory;
use App\Models\UserAccountType;
use App\Models\UserAnalytic;
use App\Models\UserMarkedProduct;
use App\Models\UserMarkedProductAnalytic;

class FilterHelper
{

    /**
     * Add users filters to query builder
     *
     * @param object $qbuilder
     * @param array $query
     * 
     * @return object
     */ 
    public static function filterUsers($qbuilder, $query) 
    {
        if( $query['number'] != null ) 
        {
            $n = $query['number'];

            $qbuilder = $qbuilder
            ->where(function($qb) use ($query, $n) 
            {
                return $qb
                    ->where('phone_number_primary','LIKE', "%$n%")
                    ->orWhere('phone_number_secondary','LIKE', "%$n%")
                    ->orWhere('house_number','LIKE', "%$n%");
            });
        }

        foreach(['full_name', 'national_code'] as $field) 
        {
            if( $query[$field] != null )
            {
                $qbuilder = $qbuilder->where($field,'LIKE', "%{$query[$field]}%");
            }
        }

        return $qbuilder;
    }

    /**
     * Add categories filters to query builder
     *
     * @param object $qbuilder
     * @param array $query
     * 
     * @return object
     */ 
    public static function filterCategories($qbuilder, $query) 
    {
        if( $query['name'] != null ) 
        {
            $qbuilder = $qbuilder->where('name', 'LIKE', "%{$query['name']}%");
        }

        return $qbuilder;
    }

    /**
     * Add brands filters to query builder
     *
     * @param object $qbuilder
     * @param array $query
     * 
     * @return object
     */ 
    public static function filterBrands($qbuilder, $query) 
    {
        if( $query['name'] != null ) 
        {
            $qbuilder = $qbuilder
            ->where(function($qb) use ($query) 
            {
                return $qb
                    ->where('name','LIKE', "%{$query['name']}%")
                    ->orWhere('english_name','LIKE', "%{$query['name']}%");
            });
        }
            
        if( $query['company'] != null ) 
        {
            $qbuilder = $qbuilder->where('company','LIKE', "%{$query['company']}%");
        }

        return $qbuilder;
    }
    
    /**
     * Add products filters to query builder
     *
     * @param object $qbuilder
     * @param array $query
     * 
     * @return object
     */ 
    public static function filterProducts($qbuilder, $query) 
    {
        if( $query['category_id'] != null )
        {
            $productCategories = ProductCategory::selectRaw('product_id, category_id')->where('category_id', $query['category_id']);

            $qbuilder = $qbuilder->leftJoinSub($productCategories, 'products_categories_ids', function ($join) 
            {
                $join->on('products.id', 'products_categories_ids.product_id');
            });

            $qbuilder = $qbuilder->where('category_id', $query['category_id']);
        }

        foreach(['title', 'barcode'] as $field) 
        {
            if( $query[$field] != null )
            {
                $qbuilder = $qbuilder->where("products.$field",'LIKE', "%{$query[$field]}%");
            }
        }
        
        foreach(['brand_id'] as $field)
        {
            if( $query[$field] != null )
            {
                $qbuilder = $qbuilder->where("products.$field", $query[$field]);
            }
        }

        return $qbuilder;
    }

    /**
     * Add slides filters to query builder
     *
     * @param object $qbuilder
     * @param array $query
     * 
     * @return object
     */ 
    public static function filterSlides($qbuilder, $query) 
    {
        if( $query['title'] != null ) 
        {
            $qbuilder = $qbuilder->where('title','LIKE', "%{$query['title']}%");
        }

        if( $query['state'] != null ) 
        {
            $qbuilder = $qbuilder->where('state', (boolean)$query['state']);
        }

        if( $query['priority'] != null ) 
        {
            $qbuilder = $qbuilder->orderBy('priority', $query['priority']);
        }

        return $qbuilder;
    }

    /**
     * Add stores filters to query builder
     *
     * @param object $qbuilder
     * @param array $query
     * 
     * @return object
     */ 
    public static function filterStores($qbuilder, $query) 
    {
        if($query['state'] == 'pending')
        {
            $qbuilder = $qbuilder->withTrashed()->where('admin_confirmed', -1);
        }

        if( $query['number'] != null ) 
        {
            $n = $query['number'];

            $qbuilder = $qbuilder
            ->where(function($qb) use ($query, $n) 
            {
                return $qb
                    ->where('owner_phone_number','LIKE', "%$n%")
                    ->orWhere('second_phone_number','LIKE', "%$n%")
                    ->orWhere('office_number','LIKE', "%$n%")
                    ->orWhere('warehouse_number','LIKE', "%$n%");
            });
        }

        foreach(['name', 'economic_code', 'province', 'city'] as $field) 
        {
            if( $query[$field] != null ) 
            {
                $qbuilder = $qbuilder->where($field,'LIKE', "%{$query[$field]}%");
            }
        }

        if( $query['national_code'] != null ) 
        {
            $usersNationalCodes = User::selectRaw('users.id as user_id, users.national_code as owner_national_code');

            $qbuilder = $qbuilder->leftJoinSub($usersNationalCodes, 'users_national_codes', function ($join) 
            {
                $join->on('stores.user_id', 'users_national_codes.user_id');
            });

            $qbuilder = $qbuilder->where('owner_national_code', 'LIKE', "%{$query['national_code']}%");
        }

        return $qbuilder;
    }

    /**
     * Add front search filters to products query builder
     *
     * @see SearchHelper::filterProducts()
     * 
     * @param object $qbuilder
     * @param array $query
     * 
     * @return object
     */ 
    public static function filterSearchProducts($qbuilder, $query) 
    {
        $query['title'] = null;
        $query['barcode'] = null;
        
        $renameing = 
        [
            'price_from' => 'fromPrice' ,
            'price_to'   => 'toPrice' ,
            'limit'      => 'perPage' ,
        ];

        foreach($renameing as $new => $old) 
        {
            if($query[$old])
            {
                $query[$new] = $query[$old];
            }
        }

        $dataChecks = 
        [
            'brand'    => \App\Models\Brand::class ,
            'category' => \App\Models\Category::class ,
        ];

        foreach($dataChecks as $key => $class) 
        {
            $query[ $key . '_id' ] = null;
            
            if($query[$key] != null) 
            {
                $record = $class::where('slug', $query[$key])->first();

                $query[ $key . '_id' ] = $record ? $record->id : null;
            }
        }

        $qbuilder = FilterHelper::filterProducts(clone $qbuilder, $query);

        if($query['q'] != null)
        {
            $words = explode(' ', $query['q']);
            
            $qbuilder = $qbuilder
            ->where( function($query) use ($words)
            {
                $query
                ->where( function($query) use ($words)
                {
                    foreach($words as $w)
                    {
                        $query->where('title', 'like', "%$w%");
                    }
                })
                ->orWhere( function($query) use ($words)
                {
                    foreach($words as $w)
                    {
                        $query->where('tags', 'like', "%$w%");
                    }
                });
            });
        }

        switch($query['sort']) 
        {
            case 'dateRecent': 
            case 'time_desc': 
                
                $qbuilder = $qbuilder->orderBy('created_at', 'desc');
                break;

            case 'priceMin': 
            case 'price_min': 
                
                $qbuilder = $qbuilder->orderBy('product_price', 'asc');
                break;

            case 'priceMax': 
            case 'price_max': 
                
                $qbuilder = $qbuilder->orderBy('product_price', 'desc');
                break;
        }

        if( $query['price_from'] != null && $query['price_to'] != null ) 
        {
            $qbuilder = $qbuilder
            ->where('product_price', '>=', $query['price_from'])
            ->where('product_price', '<=', $query['price_to']);
        }

        if( $query['available'] == '1' || $query['available'] == 'true' )
        {
            $qbuilder = $qbuilder->where('product_available_count', '>', 0);
        }

        return $qbuilder;
    }

    /**
     * Add invoices filters to query builder
     *
     * @param object $qbuilder
     * @param array $query
     * 
     * @return object
     */ 
    public static function filterStoreInvoices($qbuilder, $query) 
    {
        if( $query['state'] != null )
        {
            switch($query['state'])
            {
                case InvoiceState::Pending:
                case InvoiceState::Accepted:
                case InvoiceState::Rejected:
                case InvoiceState::Sending:
                case InvoiceState::Finished:
                case InvoiceState::Canceled:
                case InvoiceState::Returned:

                    $qbuilder->where('state', $query['state']);
                    break;
            }
        }
        
        foreach(['title', 'tracking_number', 'bill_number'] as $field) 
        {
            if( $query[$field] != null ) 
            {
                $qbuilder = $qbuilder->where($field,'LIKE', "%{$query[$field]}%");
            }
        }
        
        if( $query['name'] != null ) 
        {
            $qbuilder = $qbuilder->where('full_name','LIKE', "%{$query['name']}%");
        }
        
        if( $query['number'] != null ) 
        {
            $n = $query['number'];

            $qbuilder = $qbuilder
            ->where(function($qb) use ($n) 
            {
                return $qb
                    ->where('phone_number_primary','LIKE', "%$n%")
                    ->orWhere('phone_number_secondary','LIKE', "%$n%");
            });
        }
        
        foreach(['brand_id', 'category_id'] as $field) 
        {
            if( $query[$field] != null ) 
            {
                $qbuilder = $qbuilder->where($field, $query[$field]);
            }
        }

        return $qbuilder;
    }

    /**
     * Add invoices filters to query builder
     *
     * @param object $qbuilder
     * @param array $query
     * 
     * @return object
     */ 
    public static function filterUserInvoices($qbuilder, $query) 
    {
        if( $query['state'] != null )
        {
            switch($query['state'])
            {
                case InvoiceState::Pending:
                case InvoiceState::Accepted:
                case InvoiceState::Rejected:
                case InvoiceState::Sending:
                case InvoiceState::Finished:
                case InvoiceState::Canceled:
                case InvoiceState::Returned:

                    $qbuilder->where('state', $query['state']);
                    break;
            }
        }
        
        foreach(['name', 'title', 'tracking_number', 'bill_number'] as $field) 
        {
            if( $query[$field] != null ) 
            {
                $qbuilder = $qbuilder->where($field,'LIKE', "%{$query[$field]}%");
            }
        }
        
        foreach(['brand_id', 'category_id'] as $field) 
        {
            if( $query[$field] != null ) 
            {
                $qbuilder = $qbuilder->where($field, $query[$field]);
            }
        }

        return $qbuilder;
    }

    /**
     * Add admin notification filters to query builder
     *
     * @param object $qbuilder
     * @param array $query
     * 
     * @return object
     */ 
    public static function filterAdminNotifications($qbuilder, $query) 
    {
        
        if( $query['store'] != null ) 
        {
            $qbuilder = $qbuilder->where('store','LIKE', "%{$query['store']}%");
        }

        return $qbuilder;
    }


}
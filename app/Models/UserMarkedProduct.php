<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Public view of user marked products list
 */
class UserMarkedProduct extends SearchProduct
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = 
    [ 
        'product_id', 
        'category_id',
        'barcode', 
        'description', 
        'brand_id', 
        'created_at', 'updated_at', 'deleted_at'
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = 
    [ 
        'price_start', 
        'shops_count', 
        'is_available', 
        'image_url' , 
        'shop_name' , 
        'is_like', 
        'is_analytic', 
        'is_cart'
    ];

    /**
     * Return count of stores that have this product
     * 
     * @return boolean
     */
    public function getIsAvailableAttribute() 
    {
        $c = StoreProduct::where('product_id', $this->id)->sum('warehouse_count');

        return $c > 0;
    }

    /**
     * Return count of stores that have this product
     * 
     * @return int
     */
    public function getPriceStartAttribute() 
    {
        $p = StoreProduct::selectRaw('MIN(store_price) as min_price')
        ->where('product_id', $this->id)
        ->first();

        return $p->min_price ?? 0;
    }

}

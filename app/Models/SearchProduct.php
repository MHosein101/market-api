<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SearchProduct extends Product
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
    protected $fillable = [ ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [ 
        'product_id', 'category_id', 'product_price', 'product_available_count', 
        'barcode', 'description', 'brand_id', 'created_at', 'updated_at', 'deleted_at'

    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = [ 
        'price_start', 'shops_count', 'is_available', 'image_url' , 'shop_name' , 'is_like', 'is_analytic', 'is_cart'
    ];

    /**
     * Return count of stores that have this product
     * 
     * @return boolean
     */
    public function getIsAvailableAttribute() {
        return ($this->product_available_count > 0);
    }

    /**
     * Return count of stores that have this product
     * 
     * @return int
     */
    public function getPriceStartAttribute() {
        return $this->product_price ?? 0;
    }

    /**
     * Return 0 if product_available_count is null
     * 
     * @return int
     */
    public function getShopsCountAttribute() {
        return StoreProduct::where('product_id', $this->id)->count();
    }

    /**
     * If product available in just one store
     * return the store name
     * 
     * @return string
     */
    public function getShopNameAttribute() {
        if($this->shops_count == 1) {
            $sp = StoreProduct::where('product_id', $this->id)->first();
            return Store::find($sp->store_id)->name;
        }
        return '(Multiple)';
    }

    /**
     * Return if product marked for users's favorites list
     * 
     * @return boolean
     */
    public function getIsLikeAttribute() {
        if(request()->user != null) {
            $record = UserFavorite::where('user_id', request()->user->id)
            ->where('product_id', $this->id)
            ->first();

            return $record != null;
        }
        return false;
    }

    /**
     * Return if product marked for users's analytics list
     * 
     * @return boolean
     */
    public function getIsAnalyticAttribute() {
        return false;
    }

    /**
     * Return if product is in cart
     * 
     * @return boolean
     */
    public function getIsCartAttribute() {
        // if(request()->user != null) {
        //     $record = UserCart::where('user_id', request()->user->id)
        //     ->where('product_id', $this->id)
        //     ->first();

        //     return $record != null;
        // }
        return false;
    }
}

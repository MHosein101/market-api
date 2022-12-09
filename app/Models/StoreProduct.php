<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model to work with store_products table
 * 
 * @author Laravel
 */
class StoreProduct extends Model
{
    /**
     * Adds a deleted_at column to model's table
     */
    use SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'production_date' ,
        'expire_date' ,
        
        'production_price' ,
        'consumer_price' ,
        'store_price' ,
        
        'store_price_1' ,
        'store_price_2' ,

        'price_update_time' ,
        
        'per_unit' ,
        'warehouse_count' ,
        
        'delivery_description' ,
        'store_note' ,
        
        'cash_payment_discount' ,
        
        'commission' ,
        
        'admin_confirmed' ,
        
        'product_id' ,
        'store_id' ,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [ 'product_id', 'store_id', 'created_at', 'updated_at', 'deleted_at' ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = [ 'is_show', 'discounts', 'base_product', 'store' ];

    /**
     * Casts field value to specific type
     *
     * @var array
     */
    protected $casts = [ ];

    /**
     * Return product's discounts
     * 
     * @return Store
     */
    public function getDiscountsAttribute() {
        return StoreProductDiscount::where('product_id', $this->id)->orderBy('created_at')->get();
    }

    /**
     * Return base product with product_id column
     * 
     * @return Product
     */
    public function getBaseProductAttribute() {
        $product = Product::withTrashed()->find($this->product_id);
        return $product;
    }

    /**
     * Return product's store info with store_id column
     * 
     * @return Store
     */
    public function getStoreAttribute() {
        $store = Store::find($this->store_id);
        return $store;
    }

    /**
     * Compute deleted_at column as boolean value
     * 
     * @return boolean
     */
    public function getIsShowAttribute() {
        return ($this->deleted_at == null);
    }
}

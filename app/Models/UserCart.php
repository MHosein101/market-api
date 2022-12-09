<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserCart extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_cart';

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
        'qty' ,
        'is_payment_cash' ,

        'product_id' ,
        'store_id' ,
        'user_id' ,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [ 'product_id', 'store_id',  'user_id', 'created_at', 'updated_at', 'deleted_at' ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = [ 'product', 'store_product' ];

    /**
     * Casts field value to specific type
     *
     * @var array
     */
    protected $casts = [
        'is_payment_cash' => 'boolean'
    ];

    /**
     * Return item's base product
     * 
     * @return Product
     */
    public function getProductAttribute() {
        $pid = StoreProduct::find($this->product_id)->product_id;
        return Product::find($pid);
    }
    
    /**
     * Return item's store product
     * 
     * @return StoreProduct
     */
    public function getStoreProductAttribute() {
        return StoreProduct::find($this->product_id);
    }
}

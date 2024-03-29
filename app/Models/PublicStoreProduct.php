<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Public view of store_products table data
 */
class PublicStoreProduct extends StoreProduct
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'store_products';

    /**
     * Adds a deleted_at column to model's table
     */
    use SoftDeletes;
    
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
        'id', 
        // 'is_available' ,
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
        // 'cash_payment_discount' , 
        'commission' ,
        'admin_confirmed' ,
        // 'product_id', 
        // 'store_id', 
        'created_at', 'updated_at', 'deleted_at' 
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = 
    [ 
        'discounts', 
        'cart' 
    ];

    /**
     * Casts field value to specific type
     *
     * @var array
     */
    protected $casts = 
    [
        'is_available' => 'boolean'
    ];
    
    /**
     * Return step discounts for products
     * 
     * @return StoreProductDiscount[]
     */
    public function getDiscountsAttribute() 
    {
        return StoreProductDiscount::where('product_id', $this->product_id)->get();
    }

    /**
     * Return count of user cart items
     * 
     * @return array
     */
    public function getCartAttribute() 
    {
        $isCart = false;

        $count = 0;

        if( request()->user != null ) 
        {
            $record = UserCart::currentUser()
            ->where('product_id', $this->product_id)
            ->first();

            $isCart = $record != null;

            $count = $isCart ? $record->count : 0;
        }
        
        return 
        [
            'is_cart' => $isCart ,
            'count'   => $count ,
        ];
    }

}

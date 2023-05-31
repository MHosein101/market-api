<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductPriceChart extends Model
{
    
    /**
     * Adds a deleted_at column to model's table
     */
    use SoftDeletes;
    
    /**
     * The attributes that aren't mass assignable. 
     * If leave empty, all attributes will be mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = 
    [ 
        'product_id',
        'created_at', 'updated_at', 'deleted_at' 
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = [];
    
    /**
     * Process detail and create new record
     * 
     * @param int $productId
     */
    public static function customCreate($productId) 
    {
        $lastTime = ProductPriceChart::orderBy('created_at', 'desc')->first()->time;

        $hold = 3600 * 24 * 3; // create new record every 3 days
        $hold = 1; 

        if( $lastTime + $hold >= time() )
        {
            $price = StoreProduct
            ::selectRaw('product_id, MIN(store_price) as min_price, AVG(store_price) as avg_price')
            ->where('product_id', $productId)
            ->where('warehouse_count', '>', 0)
            ->groupBy('product_id')
            ->first();
            
            ProductPriceChart::create(
            [
                'time'          => time() ,
                'min_price'     => $price->min_price ,
                'average_price' => $price->avg_price ,
                'product_id'    => $productId ,
            ]);
        }

    }

}

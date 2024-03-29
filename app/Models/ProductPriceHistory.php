<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductPriceHistory extends Model
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
    protected $hidden = [ 
        'store_id',
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
     * Process detail to find the type of change and create new record
     * 
     * @param StoreProduct $storeProduct
     * @param int $price
     * @param int $warehouse
     */
    public static function customCreate($storeProduct, $price, $warehouse) 
    {
        $type = null;

        $diff = $storeProduct->store_price - $price;

        if($diff == 0)
        {
            $type = ProductPriceHistoryType::Unchanged;
        }
        else if ($diff > 0)
        {
            $type = ProductPriceHistoryType::Decrease;
        }
        else if ($diff < 0)
        {
            $type = ProductPriceHistoryType::Increase;
        }

        if($warehouse == 0)
        {
            $type = ProductPriceHistoryType::Ranout;
        }
        else if( $storeProduct->warehouse_count == 0 && $warehouse > 0 )
        {
            $type = ProductPriceHistoryType::Available;
        }

        ProductPriceHistory::create(
        [
            'type'       => $type ,
            'time'       => time() ,
            'price'      => $price ,
            'store_id'   => $storeProduct->store_id ,
            'product_id' => $storeProduct->product_id
        ]);


        ProductPriceChart::customCreate($storeProduct->product_id);
    }

}

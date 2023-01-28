<?php

namespace App\Models;

use App\Http\Helpers\CartHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model to work user cart items
 * 
 * @author Hosein Marzban
 */
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
        'id', 
        'count', 
        'current_price', 
        // 'current_discount', 
        'is_payment_cash', 
        'product_id', 
        'store_id', 
        'base_product_id', 
        'user_id', 
        'created_at', 'updated_at', 'deleted_at' 
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = 
    [ 
        'item_id', 
        'price', 
        'product', 
        'discounts', 
        'state' 
    ];

    /**
     * Casts field value to specific type
     *
     * @var array
     */
    protected $casts = 
    [
        'is_payment_cash' => 'boolean'
    ];

    /**
     * Return new query builder for current user
     * 
     * @return QueryBuilder
     */
    public static function currentUser() 
    {
        return UserCart::where('user_id', request()->user->id);
    }


    /**
     * Return ids
     * 
     * @return array
     */
    public function getItemIdAttribute() 
    {
        return 
        [
            'product' => $this->product_id ,
            'store'  => $this->store_id
        ];
    }

    /**
     * Return item's base product info
     * 
     * @return Product
     */
    public function getProductAttribute() 
    {
        $p = Product::find($this->base_product_id);

        return 
        [
            'title'     => $p->title ,
            'slug'      => $p->slug ,
            'image_url' => $p->image_url ,
        ];
    }

    /**
     * Return discount rules
     * 
     * @return Product
     */
    public function getDiscountsAttribute() 
    {
        return StoreProductDiscount::where('product_id', $this->product_id)->get();
    }

    /**
     * Return item changes messages
     * 
     * @return array
     */
    public function getStateAttribute() 
    {
        $sp = StoreProduct::find($this->product_id);

        $show = false;

        $msg = [];

        if( $sp->warehouse_count == 0 ) 
        {
            $show = true;

            $msg[] = 'این کالا موجود نمی باشد.';
        }
        else if( $sp->warehouse_count < $this->count ) 
        {
            $show = true;

            $msg[] = 'تعداد این کالا بیش از حد موجودی است';
        }

        $isPriceChanged = $sp->store_price != $this->current_price;

        $isDiscountChanged = CartHelper::checkDiscount($this->product_id, $this->current_discount);

        $passDiscountChanged = false;

        if($isPriceChanged)
        {
            extract( CartHelper::calcDiff($sp->store_price, $this->current_price) );

            if($diff != 0)
            {
                $show = true;
                
                $msg[] = 'این کالا به میزان ' . $diff . ' تومان ' . $type . ' قیمت داشته است.';
            }
        }

        if($isDiscountChanged)
        {
            $show = true;

            if($this->current_discount == null) // ( $this->current_discount != null && $this->price['applied_discount'] != null )
            {
                if(count($msg) == 0)
                {
                    $passDiscountChanged = true;
                }
            }
            else 
            {
                if($this->price['applied_discount'] == null)
                {
                    $msg[] = 'تخفیف این کالا به اتمام رسیده است.';
                }
                else
                {
                    $msg[] = 'تخفیف این کالا تغییر پیدا کرده است.';
                }
            }

        }

        return 
        [
            'is_show'               => $show ,
            'message'               => $msg ,
            'limit'                 => $sp->warehouse_count ,
            'is_available'          => $sp->warehouse_count > 0 ,
            'is_price_changed'      => $isPriceChanged ,
            'is_discount_changed'   => $isDiscountChanged ,
            'pass_discount_changed' => $passDiscountChanged ,
            'new_price'             => $sp->store_price ,
            'new_discount'          => $this->price['applied_discount'] ,
        ];
    }

    /**
     * Calculate and Return item price and discounts
     * 
     * @return Product
     */
    public function getPriceAttribute() 
    {
        $sp = StoreProduct::find($this->product_id);

        $discountsOfPrice = 
        StoreProductDiscount::where('product_id', $this->product_id)
        ->where('discount_type', StoreProductDiscountType::Price)
        ->orderBy('discount_value', 'desc')
        ->get();

        $discountsOfCount = 
        StoreProductDiscount::where('product_id', $this->product_id)
        ->where('discount_type', StoreProductDiscountType::Count)
        ->orderBy('discount_value', 'desc')
        ->get();

        $originalPrice = $this->current_price;

        $originalTotalPrice = $originalPrice * $this->count;

        $isDiscount = false;

        $finalPrice = $originalPrice;

        $discountPrice = 0;

        $discountApplied = null;

        if( $this->is_payment_cash ) 
        {
            $discountPrice = ( $originalTotalPrice / 100 ) * $sp->cash_payment_discount;

            $isDiscount = true;
        }

        foreach($discountsOfCount as $d) 
        {
            if( $d->discount_value <= $this->count ) 
            {
                $discountPrice += ($d->discount_value * $originalPrice) - $d->final_price;

                $isDiscount = true;

                $discountApplied = "{$d->discount_type}-{$d->discount_value}-{$d->final_price}";

                break;
            }
        }

        foreach($discountsOfPrice as $d) 
        {
            if( !$isDiscount && $d->discount_value <= $originalTotalPrice ) 
            {
                $discountPrice += $d->discount_value - $d->final_price;

                $isDiscount = true;

                $discountApplied = "{$d->discount_type}-{$d->discount_value}-{$d->final_price}";

                break;
            }
        }

        $cashPayDiscount = StoreProduct::find($this->product_id)->cash_payment_discount;

        $finalPriceTotal = $originalTotalPrice - $discountPrice;

        $finalPrice = round($finalPriceTotal / $this->count);

        $discountPrice = $originalPrice - $finalPrice;

        $discountPercent = $discountPrice / ( $originalPrice / 100 );
        
        return 
        [
            'count'    => $this->count ,
            'original' => $originalPrice ,
            'original_total'        => $originalTotalPrice ,
            'cash_payment_discount' => $cashPayDiscount ,
            'is_discount'      => $isDiscount ,
            'applied_discount' => $discountApplied ,
            'discount_price'   => $discountPrice ,
            'discount_percent' => round($discountPercent, 1) ,
            'final'            => $finalPrice ,
            'final_total'      => $finalPriceTotal ,
        ];
    }

}

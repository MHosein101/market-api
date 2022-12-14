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
        'count' ,
        'is_payment_cash' ,

        'current_price' ,

        'product_id' ,
        'store_id' ,
        'base_product_id' ,
        'user_id' ,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [ 'id', 'count', 'current_price', 'is_payment_cash', 'product_id', 'store_id', 'base_product_id', 'user_id', 'created_at', 'updated_at', 'deleted_at' ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = [ 'item_id', 'price', 'product', 'discounts', 'state' ];

    /**
     * Casts field value to specific type
     *
     * @var array
     */
    protected $casts = [
        'is_payment_cash' => 'boolean'
    ];

    /**
     * Return ids
     * 
     * @return array
     */
    public function getItemIdAttribute() {
        return [
            'product' => $this->product_id ,
            'store' => $this->store_id
        ];
    }

    /**
     * Return item's base product info
     * 
     * @return Product
     */
    public function getProductAttribute() {
        $p = Product::find($this->base_product_id);
        return [
            'title' => $p->title ,
            'slug' => $p->slug ,
            'image_url' => $p->image_url
        ];
    }

    /**
     * Return discount rules
     * 
     * @return Product
     */
    public function getDiscountsAttribute() {
        return StoreProductDiscount::where('product_id', $this->product_id)->get();
    }

    /**
     * Return ids
     * 
     * @return array
     */
    public function getStateAttribute() {
        $sp = StoreProduct::find($this->product_id);
        $show = false;
        $msg = '';

        if($sp->warehouse_count == 0) {
            $show = true;
            $msg = '?????? ???????? ?????????? ?????? ????????.';
        }

        if($sp->store_price != $this->current_price) {
            $diff = $sp->store_price - $this->current_price;
            $type = '????????????';

            if($diff < 0) {
                $type = '????????';
                $diff = -$diff;
            }

            $diff = number_format($diff);

            $diff = str_replace(
                ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'] , 
                ['??', '??', '??', '??', '??', '??', '??', '??', '??', '??'] , 
                (string)$diff );

            $show = true;
            $msg = '?????? ???????? ???? ?????????? ' . $diff . ' ?????????? ' . $type . ' ???????? ?????????? ??????.';
        }

        return [
            'is_show' => $show ,
            'is_available' => $sp->warehouse_count > 0 ,
            'is_price_changed' => $sp->store_price != $this->current_price ,
            'message' => $msg ,
        ];
    }

    /**
     * Calculate and Return item price and discounts
     * 
     * @return Product
     */
    public function getPriceAttribute() {
        $sp = StoreProduct::find($this->product_id);

        $discountsOfPrice = StoreProductDiscount::where('product_id', $this->product_id)
        ->where('discount_type', StoreProductDiscountType::Price)
        ->orderBy('discount_value', 'desc')
        ->get();

        $discountsOfCount = StoreProductDiscount::where('product_id', $this->product_id)
        ->where('discount_type', StoreProductDiscountType::Count)
        ->orderBy('discount_value', 'desc')
        ->get();

        $originalPrice = $sp->store_price;
        $originalTotalPrice = $sp->store_price * $this->count;
        $isDiscount = false;
        $finalPrice = $originalPrice;
        $discountPrice = 0;

        if($this->is_payment_cash) {
            $discountPrice = ( $originalTotalPrice / 100 ) * $sp->cash_payment_discount;
            $isDiscount = true;
        }

        foreach($discountsOfCount as $d) {
            if($d->discount_value <= $this->count) {
                $discountPrice += ($d->discount_value * $originalPrice) - $d->final_price;
                $isDiscount = true;
                break;
            }
        }

        foreach($discountsOfPrice as $d) {
            if(!$isDiscount && $d->discount_value <= $originalTotalPrice) {
                $discountPrice += $d->discount_value - $d->final_price;
                $isDiscount = true;
                break;
            }
        }

        $cashPayDiscount = StoreProduct::find($this->product_id)->cash_payment_discount;

        $finalPriceTotal = $originalTotalPrice - $discountPrice;
        $finalPrice = round($finalPriceTotal / $this->count);

        $discountPrice = $originalPrice - $finalPrice;
        $discountPercent = $discountPrice / ( $originalPrice / 100 );
        
        return [
            'count' => $this->count ,
            'original' => $originalPrice ,
            'original_total' => $originalTotalPrice ,
            'cash_payment_discount' => $cashPayDiscount ,
            'is_discount' => $isDiscount ,
            'discount_price' => $discountPrice ,
            'discount_percent' => round($discountPercent, 1) ,
            'final' => $finalPrice ,
            'final_total' => $finalPriceTotal ,
        ];
    }

}

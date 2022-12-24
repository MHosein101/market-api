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
    protected $hidden = [ 'count', 'is_payment_cash', 'product_id', 'store_id', 'base_product_id', 'user_id', 'created_at', 'updated_at', 'deleted_at' ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = [ 'item', 'price', 'product', 'store' ];

    /**
     * Casts field value to specific type
     *
     * @var array
     */
    protected $casts = [
        'is_payment_cash' => 'boolean'
    ];

    /**
     * Return item's base product info
     * 
     * @return Product
     */
    public function getItemAttribute() {
        return [
            'id' => $this->id ,
            'is_payment_cash' => $this->is_payment_cash ,
            'count' => $this->count ,
        ];
    }

    /**
     * Calculate and Return item price and discounts
     * 
     * @return Product
     */
    public function getPriceAttribute() {
        
        $sp = StoreProduct::find($this->product_id);
        $discounts = StoreProductDiscount::where('product_id', $this->product_id)
        ->orderBy('created_at', 'desc')
        ->get();

        $originalPrice = $sp->store_price;
        $originalTotalPrice = $sp->store_price * $this->count;
        $isDiscount = false;
        $finalPrice = $originalPrice;
        $discountPrice = 0;

        if($this->is_payment_cash) {
            $discountPrice = ( $originalPrice / 100 ) * $sp->cash_payment_discount;
            $isDiscount = true;
        }

        foreach($discounts as $d) {
            if( $d->discount_type == StoreProductDiscountType::Count 
             && $d->discount_value <= $this->count ) {
                $discountPrice += $originalTotalPrice - $d->final_price;
                $isDiscount = true;
                break;
            }
            if( $d->discount_type == StoreProductDiscountType::Price 
             && $d->discount_value <= $originalTotalPrice ) {
                $discountPrice += $d->discount_value - $d->final_price;
                $isDiscount = true;
                break;
            }
        }

        $discountPercent = $discountPrice / ( $originalTotalPrice / 100 );
        $finalPrice = $originalTotalPrice - $discountPrice;

        return [
            'single' => $originalPrice ,
            'total' => $originalTotalPrice ,
            'is_discount' => $isDiscount ,
            'discount_price' => $discountPrice ,
            'discount_percent' => $discountPercent ,
            'final' => $finalPrice ,
        ];

        /* $sp = StoreProduct::find($this->product_id);
        $singlePrice = $sp->store_price;
        $totalPrice = $singlePrice * $this->count;
        $discountsByQty = StoreProductDiscount::where('product_id', $this->product_id)
        ->where('discount_type', StoreProductDiscountType::Count)
        ->orderBy('discount_value', 'desc')
        ->get();
        $discountsByPrice = StoreProductDiscount::where('product_id', $this->product_id)
        ->where('discount_type', StoreProductDiscountType::Price)
        ->orderBy('discount_value', 'desc')
        ->get();
        $isDiscount = false;
        $priceWithDiscount = $totalPrice;
        if($this->is_payment_cash) {
            $priceWithDiscount = ( $totalPrice / 100 ) * ( 100 - $sp->cash_payment_discount );
            $isDiscount = true; }
        foreach($discountsByQty as $d) {
            if(!$isDiscount && $d->discount_value < $this->count) {
                $diffPrice = ( $this->count - $d->discount_value ) * $singlePrice;
                $priceWithDiscount = $diffPrice + $d->final_price;
                $isDiscount = true;
                break; } }
        foreach($discountsByPrice as $d) {
            if(!$isDiscount && $d->discount_value < $totalPrice) {
                $priceWithDiscount = $totalPrice - ( $d->discount_value - $d->final_price );
                $isDiscount = true;
                break; } }
        $discountPrice = $totalPrice - $priceWithDiscount;
        $discountPercent = $discountPrice / ( $totalPrice / 100 );
        return [
            'is_payment_cash' => $this->is_payment_cash ,
            'count' => $this->count ,
            'single_price' => $singlePrice ,
            'total_price' => $totalPrice ,
            'is_discount' => $isDiscount ,
            'discount_price' => $discountPrice ,
            'discount_percent' => $discountPercent ,
            'final_price' => $priceWithDiscount ,
        ]; */
    }

    /**
     * Return item's base product info
     * 
     * @return Product
     */
    public function getProductAttribute() {
        $pid = StoreProduct::find($this->product_id)->product_id;
        $p = Product::find($pid);
        return [
            'title' => $p->title ,
            'slug' => $p->slug ,
            'image_url' => $p->image_url
        ];
    }
    
    /**
     * Return store info
     * 
     * @return StoreProduct
     */
    public function getStoreAttribute() {
        $s = Store::find($this->store_id);
        $sp = StoreProduct::find($this->product_id);
        return [
            'title' => $s->name ,
            'slug' => $s->slug ,
            'logo_image' => $s->logo_image ,
            
            'store_note' => $sp->store_note ,
            'delivery_description' => $sp->delivery_description ,
        ];
    }
}

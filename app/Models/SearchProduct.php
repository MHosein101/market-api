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
        'product_id', 'product_price', 'product_stores_count', 
        'barcode', 'description', 'brand_id', 'created_at', 'updated_at', 'deleted_at'

    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = [ 
        'price', 'stores_count', 'is_available', 'image_url' 
    ];

    /**
     * Return count of stores that have this product
     * 
     * @return boolean
     */
    public function getIsAvailableAttribute() {
        return ($this->stores_count > 0);
    }

    /**
     * Return 0 if product_stores_count is null
     * 
     * @return int
     */
    public function getStoresCountAttribute() {
        return $this->product_stores_count ?? 0;
    }

    /**
     * Return count of stores that have this product
     * 
     * @return int
     */
    public function getPriceAttribute() {
        return $this->product_price ?? 0;
    }
}

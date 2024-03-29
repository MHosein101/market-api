<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
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
        'id',
        'state',
        'store_comment',
        'user_comment',
        'tax',
        'invoice_id',
        'store_product_id',
        'base_product_id',
        'created_at', 'updated_at', 'deleted_at' 
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = 
    [
        'product'
    ];
    
    /**
     * Return item's base product info
     * 
     * @return Product
     */
    public function getProductAttribute() 
    {
        return Product::find($this->base_product_id);
    }
}

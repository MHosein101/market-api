<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model to work with product_categories table
 * 
 * @author Laravel
 */
class ProductCategory extends Model
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
        'product_id', 'category_id'
    ];

    /**
     * Do not include record timestamps
     *
     * @var boolean
     */
    public $timestamps = false;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model to work with brands table
 * 
 * @author Laravel
 */
class Brand extends Model
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
        'name', 'english_name', 'slug', 'company', 'logo_url'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [ 'created_at', 'updated_at', 'deleted_at' ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = [ 'is_brand_image', 'is_show' ];

    /**
     * Compute boolean value from logo_url column value
     * 
     * @return boolean
     */
    public function getIsBrandImageAttribute() {
        return ! (strpos($this->logo_url, 'default') !== false);
    }

    /**
     * Compute deleted_at column as boolean value
     * 
     * @return boolean
     */
    public function getIsShowAttribute() {
        return ($this->deleted_at == null);
    }
}

<?php

namespace App\Models;

use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
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
        'brand_id', 
        'created_at', 'updated_at', 'deleted_at' 
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = 
    [ 
        'tags',
        'brand', 
        'categories', 
        'images', 
        'is_show', 
        'is_created', 
        'is_image_url', 
        'image_url' 
    ];

    /**
     * Return product tags
     * 
     * @return array
     */
    public function getTagsAttribute() 
    {
        return ProductTag::where('product_id', $this->id)->pluck('name');
    }

    /**
     * Return product's brands data
     * 
     * @return array
     */
    public function getBrandAttribute() 
    {
        $b = Brand::find($this->brand_id);

        if( $b == null )
        {
            return [];
        }

        return 
        [
            'id'   => $b->id ,
            'name' => $b->name ,
        ];
    }

    /**
     * Return all product's categories in order
     * 
     * @return array
     */
    public function getCategoriesAttribute() 
    {
        $catIDs = ProductCategory::where('product_id', $this->id)->get();

        $names = [];

        foreach($catIDs as $c) 
        {
            $category = Category::withTrashed()->find($c->category_id);

            $names[] = 
            [
                'id'  => $category->id ,
                'name' => $category->name
            ];
        }

        $names = array_reverse($names);
        
        return $names;
    }

    /**
     * Return all product images
     * 
     * @return array
     */
    public function getImagesAttribute() 
    {
        $images = ProductImage::where('product_id', $this->id)->orderBy('created_at')->get();

        return $images ?? [];
    }

    /**
     * Compute boolean value from deleted_at column value
     * 
     * @return boolean
     */
    public function getIsShowAttribute() 
    {
        return $this->deleted_at == null;
    }

    /**
     * Return main image's url of product
     * If product have no image then return default image
     * 
     * @return string
     */
    public function getImageUrlAttribute() 
    {
        $image = ProductImage::where('product_id', $this->id)
        ->where('is_main', true)
        ->first();

        return $image == null 
        ? request()->getSchemeAndHttpHost() . '/default.jpg' 
        : $image->url;
    }

    /**
     * Compute boolean if product have no image
     * 
     * @return boolean
     */
    public function getIsImageUrlAttribute() 
    {
        $images = ProductImage::where('product_id', $this->id)->get();

        return count($images) > 0;
    }

    /**
     * Compute if product created in store products
     * 
     * @return null|boolean
     */
    public function getIsCreatedAttribute() 
    {
        if( request()->user->account_type == UserAccountType::Store ) 
        {
            $check = StoreProduct::withTrashed()
            ->where('store_id', request()->user->store_id)
            ->where('product_id', $this->id)
            ->first();
            
            return $check != null;
        }
        
        return null;
    }
    
}

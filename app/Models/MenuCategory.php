<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Multi level menu categories model
 */
class MenuCategory extends Category
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';
    
    /**
     * Adds a deleted_at column to model's table
     */
    use SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = 
    [ 
        'name', 
        'parent_id', 
        'created_at', 
        'updated_at', 
        'deleted_at' 
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = 
    [ 
        'title', 
        'type', 
        'status', 
        'is_sub_category', 
        'sub_categories' 
    ];

    /**
     * Return name column value
     * 
     * @return string
     */
    public function getTitleAttribute() 
    {
        return $this->name;
    }

    /**
     * Return fixed string for front-end multilevel menu
     * 
     * @return string
     */
    public function getTypeAttribute() 
    {
        return 'category';
    }

    /**
     * Return false for front-end multilevel menu
     * 
     * @return boolean
     */
    public function getStatusAttribute() 
    {
        return false;
    }

    /**
     * Compute parent_id column as boolean value
     * 
     * @return boolean
     */
    public function getIsSubCategoryAttribute() 
    {
        return $this->parent_id != null;
    }

    /**
     * Return children of category
     * 
     * @return array
     */
    public function getSubCategoriesAttribute() 
    {
        return MenuCategory::where('parent_id', $this->id)->get();
    }

}

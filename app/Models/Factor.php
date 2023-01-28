<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Factor extends Model
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
        'state',
        'store_note',
        'user_note',
        'user_id',
        'store_id',
        'ordered',
        'created_at', 'updated_at', 'deleted_at' 
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = 
    [
        'items'
    ];

    /**
     * Return items related in this factor
     * 
     * @return FactorItem[]
     */
    public function getItemsAttribute() 
    {
        return FactorItem::where('factor_id', $this->id)->get();
    }

}

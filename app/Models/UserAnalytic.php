<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAnalytic extends Model
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
}

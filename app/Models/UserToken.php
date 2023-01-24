<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Users access tokens
 * 
 * @author Hosein Marzban
 */
class UserToken extends Model
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
    protected $fillable = 
    [
        'token', 
        'expire', 
        'user_id'
    ];
    
}

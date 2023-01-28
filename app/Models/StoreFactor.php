<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model to work with users table
 * 
 * @author Hosein Marzban
 */
class StoreFactor extends User
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that aren't mass assignable. 
     * If leave empty, all attributes will be mass assignable.
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
        'store_id',
        'verification_code', 
        'password', 
        'created_at', 'updated_at', 'deleted_at' 
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = 
    [ 
        'factors',
        'address', 
        'is_password', 
        'is_profile_image', 
        'is_active', 
        'is_pending' 
    ];

    /**
     * Return user factors
     * 
     * @return Factor[]
     */
    public function getFactorsAttribute() 
    {
        return Factor::where('user_id', $this->id)->get();
    }

}

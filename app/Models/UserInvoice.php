<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInvoice extends Store
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stores';
    
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
        'id',
        'bank_name', 
        'bank_code', 
        'bank_card_number', 
        'bank_sheba_number', 
        'minimum_shopping_count', 
        'minimum_shopping_unit', 
        'banner_image', 
        'license_image', 
        'admin_confirmed', 
        'created_at', 'updated_at', 'deleted_at'
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = [];

}

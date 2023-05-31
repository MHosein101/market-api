<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
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
        'admin_confirmed', 
        'created_at', 'updated_at', 'deleted_at' 
    ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = 
    [ 
        'owner_national_code', 
        'is_pending', 
        'is_license_image', 
        'is_logo_image', 
        'is_store_banner_image', 
        'is_show' 
    ];

    /**
     * Return owner national code from users table
     * 
     * @return string
     */
    public function getOwnerNationalCodeAttribute() 
    {
        return User::withTrashed()
        ->where('store_id', $this->id)
        ->first()
        ->national_code;
    }

    /**
     * Compute boolean value from admin_confirmed column value
     * 
     * @return boolean
     */
    public function getIsPendingAttribute() 
    {
        return $this->admin_confirmed == -1;
    }

    /**
     * Override logo_image value if it's empty
     * 
     * @return string
     */
    public function getLogoImageAttribute($value) 
    {
        return $value != '' 
        ? $value 
        : request()->getSchemeAndHttpHost() . '/default.jpg';
    }

    /**
     * Override license_image value if it's empty
     * 
     * @return string
     */
    public function getLicenseImageAttribute($value) 
    {
        return $value != '' 
        ? $value 
        : request()->getSchemeAndHttpHost() . '/default.jpg';
    }

    /**
     * Override banner_image value if it's empty
     * 
     * @return string
     */
    public function getBannerImageAttribute($value) 
    {
        return $value != '' 
        ? $value 
        : request()->getSchemeAndHttpHost() . '/default.jpg';
    }

    /**
     * Compute boolean value from logo_image column value
     * 
     * @return boolean
     */
    public function getIsLogoImageAttribute() 
    {
        return !Str::contains($this->logo_image, 'default.jpg');
    }

    /**
     * Compute boolean value from license_image column value
     * 
     * @return boolean
     */
    public function getIsLicenseImageAttribute() 
    {
        return !Str::contains($this->license_image, 'default.jpg');
    }

    /**
     * Compute boolean value from banner_image column value
     * 
     * @return boolean
     */
    public function getIsStoreBannerImageAttribute() 
    {
        return !Str::contains($this->banner_image, 'default.jpg');
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
     * Return store's owner is_password field
     * 
     * @return boolean
     */
    public function getIsPasswordAttribute() 
    {
        return User::find(request()->user->id)->is_password;
    }

}

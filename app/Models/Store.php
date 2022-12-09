<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model to work with stores table
 * 
 * @author Laravel
 */
class Store extends Model
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
        'name' ,
        'slug' ,
        'economic_code' ,

        'owner_full_name' ,
        'owner_phone_number' ,
        'second_phone_number' ,

        'province' ,
        'city' ,

        'office_address' ,
        'office_number' ,

        'warehouse_address' ,
        'warehouse_number' ,

        'minimum_shopping_count' ,
        'minimum_shopping_unit' ,

        'bank_name' ,
        'bank_code' ,
        'bank_card_number' ,
        'bank_sheba_number' ,

        'admin_confirmed' ,
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
    protected $appends = [ 'owner_national_code', 'is_pending', 'is_license_image', 'is_logo_image', 'is_store_banner_image', 'is_show' ];

    /**
     * Return owner national code from users table
     * 
     * @return string
     */
    public function getOwnerNationalCodeAttribute() {
        return User::where('store_id', $this->id)->get()->first()->national_code;
    }

    /**
     * Compute boolean value from admin_confirmed column value
     * 
     * @return boolean
     */
    public function getIsPendingAttribute() {
        return ($this->admin_confirmed == -1);
    }

    /**
     * Compute boolean value from logo_image column value
     * 
     * @return boolean
     */
    public function getIsLogoImageAttribute() {
        return ! (strpos($this->logo_image, 'default') !== false);
    }

    /**
     * Compute boolean value from license_image column value
     * 
     * @return boolean
     */
    public function getIsLicenseImageAttribute() {
        return ! (strpos($this->license_image, 'default') !== false);
    }

    /**
     * Compute boolean value from banner_image column value
     * 
     * @return boolean
     */
    public function getIsStoreBannerImageAttribute() {
        return ! (strpos($this->banner_image, 'default') !== false);
    }
    
    /**
     * Compute boolean value from deleted_at column value
     * 
     * @return boolean
     */
    public function getIsShowAttribute() {
        return ($this->deleted_at == null);
    }

    /**
     * Return store's owner is_password field
     * 
     * @return boolean
     */
    public function getIsPasswordAttribute() {
        return User::find(request()->user->id)->is_password;
    }
    

}

<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Model to work with users table
 * 
 * @author Laravel
 */
class User extends Authenticatable
{
    /**
     * Adds a deleted_at column to model's table
     */
    use SoftDeletes; // HasApiTokens, HasFactory, Notifiable, 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_type', 
        
        'profile_image',
        'full_name',
        'national_code',

        'phone_number_primary',
        'phone_number_secondary',
        'house_number',

        'store_id' ,

        'password',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [ 'verification_code', 'password', 'created_at', 'updated_at', 'deleted_at' ];

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = [ 'address', 'is_password', 'is_profile_image', 'is_active', 'is_pending' ];

    /**
     * Override profile_image value if it's empty
     * 
     * @return string
     */
    public function getProfileImageAttribute($value) {
        return $value != '' ? $value : request()->getSchemeAndHttpHost() . '/default.jpg';
    }

    /**
     * Compute boolean value from profile_image column value
     * 
     * @return boolean
     */
    public function getIsProfileImageAttribute() {
        return !Str::contains($this->profile_image, 'default.jpg');
    }

    /**
     * Compute boolean value from deleted_at column value
     * 
     * @return boolean
     */
    public function getIsActiveAttribute() {
        return ($this->deleted_at == null);
    }

    /**
     * User store is_pending value
     * 
     * @return boolean
     */
    public function getIsPendingAttribute() {
        if($this->store_id != null)
            return Store::withTrashed()->find($this->store_id)->is_pending;
        return false;
    }

    /**
     * Compute boolean value from password column value
     * 
     * @return boolean
     */
    public function getIsPasswordAttribute() {
        return ($this->password != null);
    }

    /**
     * Return user address
     * 
     * @return boolean
     */
    public function getAddressAttribute() {
        $adds = UserAddress::where('user_id', $this->id)->first();
        return ( $adds == null ) ? [] : $adds ;
    }
    
    /**
     * Generate a random verification code and save it for user
     * 
     * @return string $code
     */ 
    public function generateVerificationCode()
    {
        $nums = '0123456789';
        $code = '';
        foreach([0,1,2,3] as $i)
            $code .= ( $nums[ random_int( 0, strlen($nums)-1 ) ] );

        $this->verification_code = $code;
        $this->save();

        return $code;
    }

    /**
     * Generate an api token and save it for user
     * 
     * @return string
     */ 
    public function generateApiToken()
    {
        $token = Str::random(60);
        
        $this->verification_code = null;
        $this->save();

        UserToken::create([
            'token' => $token ,
            'expire' => time() + (3600 * 24 * 14) ,
            'user_id' => $this->id ,
        ]);

        return $token;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
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
        'user_id',
        'store_id',
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
     * Override state value to persian
     * 
     * @return string
     */
    public function getStateAttribute($value) 
    {
        $persian =
        [
            'pending'  => 'در حال بررسی' ,
            'accepted' => 'تایید شده' ,
            'rejected' => 'رد شده' ,
            'canceled' => 'کنسل شده (توسط کاربر)' ,
            'sending'  => 'در حال ارسال' ,
            'finished' => 'ارسال شده' ,
        ];

        return $persian[$value];
    }

    /**
     * Return items related to this invoice
     * 
     * @return InvoiceItem[]
     */
    public function getItemsAttribute() 
    {
        return InvoiceItem::where('invoice_id', $this->id)->get();
    }
}

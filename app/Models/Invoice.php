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
        'state_persian',
        'items'
    ];

    /**
     * Translate state value to persian
     * 
     * @return string
     */
    public function getStatePersianAttribute() 
    {
        $persian =
        [
            InvoiceState::Pending  => 'در حال بررسی' ,
            InvoiceState::Accepted => 'تایید شده' ,
            InvoiceState::Rejected => 'رد شده' ,
            InvoiceState::Canceled => 'کنسل شده (توسط کاربر)' ,
            InvoiceState::Sending  => 'در حال ارسال' ,
            InvoiceState::Finished => 'ارسال شده' ,
        ];

        return $persian[$this->state];
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

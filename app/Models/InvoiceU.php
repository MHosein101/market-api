<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceU extends Invoice
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoices';

    /**
     * New attributes that should be appended to model
     *
     * @var array
     */
    protected $appends = 
    [
        'message',
        'state_persian',
        'is_allowed_comment',
        'items'
    ];

    /**
     * rename user comment field
     * 
     * @return string
     */
    public function getMessageAttribute() 
    {
        return $this->user_comment;
    }

    /**
     * If user allow to comment to state
     * 
     * @return boolean
     */
    public function getIsAllowedCommentAttribute() 
    {
        return $this->state == InvoiceState::Pending;
    }
}

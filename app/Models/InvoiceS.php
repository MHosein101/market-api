<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceS extends Invoice
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
     * rename store comment field
     * 
     * @return string
     */
    public function getMessageAttribute() 
    {
        return $this->store_comment;
    }

    /**
     * If store allow to comment to state
     * 
     * @return boolean
     */
    public function getIsAllowedCommentAttribute() 
    {
        return 
            in_array(
            $this->state, 
            [ 
                InvoiceState::Pending, 
                InvoiceState::Accepted, 
                InvoiceState::Sending 
            ]);
    }

}

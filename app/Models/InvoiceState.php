<?php

namespace App\Models;


/**
 * Constant values invoices states
 * 
 * @author Hosein marzban
 */
class InvoiceState 
{

    const Pending = 'pending';

    const Rejected = 'rejected';

    const Accepted = 'accepted';

    const Canceled = 'canceled';

    const Sending = 'sending';

    const Finished = 'finished';

    const Returned = 'returned';

}
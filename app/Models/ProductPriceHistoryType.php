<?php

namespace App\Models;


/**
 * Constant values for store's product price history type
 * 
 * @author Hosein marzban
 */
class ProductPriceHistoryType 
{

    const Unchanged = 'unchanged';

    const Increase  = 'increase';

    const Decrease  = 'decrease';

    const Available = 'available';

    const Ranout    = 'ranout';

}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Public view of user marked products list as price analytic
 * 
 * @author Hosein Marzban
 */
class UserMarkedProductAnalytic extends SearchProduct
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * product prices chart data
     * 
     * @return ProductPriceChart
     */
    public function getChartAttribute() 
    {
        return ProductPriceChart::where('product_id', $this->id)
        ->orderby('created_at')
        ->get();
    }

    /**
     * product price changes data
     * 
     * @return ProductPriceHistory
     */
    public function getHistoryCountAttribute() 
    {
        return ProductPriceHistory::where('product_id', $this->id)
        ->orderby('created_at')
        ->get();
    }
}

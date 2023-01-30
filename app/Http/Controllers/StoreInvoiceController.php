<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\InvoiceState;
use App\Models\StoreInvoice;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Http\Helpers\SearchHelper;

class StoreInvoiceController extends Controller
{
    /**
     * Return all store's invoices with filter
     * 
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getList(Request $request)
    {
        $usersInvoices = Invoice
        ::selectRaw('id as invoice_id, store_id, user_id, state, COUNT(id) as invoices_count')
        ->groupBy('user_id', 'store_id', 'id', 'state');

        $invoiceItems = InvoiceItem
        ::selectRaw('invoice_id, base_product_id as product_id');

        $products = Product
        ::selectRaw('id as product_id, title, brand_id');
        
        $users = StoreInvoice::selectRaw('users.*')->distinct()

        ->leftJoinSub($usersInvoices, 'u_invoices', function ($join) 
        {
            $join->on('users.id', 'u_invoices.user_id');
        })

        ->where('u_invoices.invoices_count', '>', 0)

        ->where('u_invoices.store_id', $request->user->store_id)

        ->leftJoinSub($invoiceItems, 'v_items', function ($join) 
        {
            $join->on('u_invoices.invoice_id', 'v_items.invoice_id');
        })

        ->leftJoinSub($products, 'i_products', function ($join) 
        {
            $join->on('v_items.product_id', 'i_products.product_id');
        });

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            clone $users , 
            null , 
            [
                'state'        => null ,
                'title'        => null ,
                'brand_id'     => null ,
                'category_id'  => null ,
                'name'         => null ,
                'number'       => null ,
            ] , 
            'filterStoreInvoices'
        );

        extract($result);

        $status = count($data) > 0 ? 200 : 204;

        return
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No invoices found.' ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'users'      => $data
            ]
            , 200);
    }

    /**
     * Change invoice's state
     * 
     * @param Request $request
     * @param int $invoiceId
     * 
     * @return Response
     */ 
    public function changeState(Request $request, $invoiceId)
    {
        $state = $request->input('state');

        $comment = $request->input('comment') ?? '';
        
        $invoice = Invoice::find($invoiceId);
        
        $lastState = $invoice->state;

        $allowChange = false;

        $restock = false;

        $isComment = false;

        $newState = null;

        switch($state)
        {
            case 'accept':

                if( $lastState == InvoiceState::Pending )
                {
                    $newState = InvoiceState::Accepted;
                    $isComment = true;
                    $allowChange = true;
                }
                break;

            case 'reject':

                if( $lastState == InvoiceState::Pending )
                {
                    $newState = InvoiceState::Rejected;
                    $isComment = true;
                    $allowChange = true;
                    $restock = true;
                }
                break;
                    
            case 'sending':

                if( $lastState == InvoiceState::Accepted )
                {
                    $newState = InvoiceState::Sending;
                    $allowChange = true;
                }
                break;
                
            case 'finished':

                if( $lastState == InvoiceState::Sending )
                {
                    $newState = InvoiceState::Finished;
                    $allowChange = true;
                }
                break;
        }

        if($allowChange)
        {
            $updateData = 
            [ 
                'state'         => $newState ,
                'store_comment' => $isComment ? $comment : ''
            ];

            Invoice::where('id', $invoiceId)->update($updateData);
        }

        if($restock)
        {
            foreach($invoice->items as $item)
            {
                StoreProduct::where('id', $item->store_product_id)
                ->increment('warehouse_count', $item->count);
            }
        }

        return
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'OK' ,
                'state_changed' => $allowChange
            ] 
            , 200);
    }
}

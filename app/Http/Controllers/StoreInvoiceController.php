<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\InvoiceState;
use App\Models\StoreInvoice;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Http\Helpers\SearchHelper;
use App\Models\InvoiceS;

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
        ::selectRaw('id as invoice_id, store_id, user_id, state, COUNT(id) as invoices_count, created_at')
        ->groupBy('user_id', 'store_id', 'id', 'state', 'created_at');

        $invoiceItems = InvoiceItem
        ::selectRaw('invoice_id, base_product_id as product_id');

        $products = Product
        ::selectRaw('id as product_id, title, brand_id');
        
        $categories = ProductCategory
        ::selectRaw('product_id, category_id');
        
        $qbuilder = StoreInvoice::distinct()

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
        })
        
        ->leftJoinSub($categories, 'p_categories', function ($join) 
        {
            $join->on('i_products.product_id', 'p_categories.product_id');
        });

        $invoicesqbuilder = clone $qbuilder;
        $invoicesqbuilder = $invoicesqbuilder->selectRaw('u_invoices.user_id, u_invoices.invoice_id, u_invoices.created_at');

        $invoicesFilters = $request->query();

        unset($invoicesFilters['page']);
        unset($invoicesFilters['limit']);

        $invoicesList = SearchHelper::dataWithFilters(
            $invoicesFilters , 
            clone $invoicesqbuilder , 
            null , 
            [
                'state'        => null ,
                'title'        => null ,
                'brand_id'     => null ,
                'category_id'  => null ,
                'name'         => null ,
                'number'       => null ,
            ] , 
            'filterStoreInvoices' ,
            true
        );

        $usersqbuilder = clone $qbuilder;
        $usersqbuilder = $usersqbuilder->selectRaw('u_invoices.user_id');
        
        $usersFilters = $request->query();

        unset($usersFilters['order']);

        $usersList = SearchHelper::dataWithFilters(
            $usersFilters , 
            clone $usersqbuilder , 
            null , 
            [
                'state'        => null ,
                'title'        => null ,
                'brand_id'     => null ,
                'category_id'  => null ,
                'name'         => null ,
                'number'       => null ,

                'order'        => null
            ] , 
            'filterStoreInvoices' ,
        );

        $usersIds = [];

        foreach($invoicesList as $d)
        {
            $usersIds[] = $d->user_id;
        }

        $usersIds = array_values( array_unique($usersIds) );

        $usersInvoicesIds = [];
        
        foreach($usersIds as $uid)
        {
            $usersInvoicesIds[$uid] = [];

            foreach($invoicesList as $d)
            {
                if( $uid == $d->user_id )
                {
                    $usersInvoicesIds[$uid][] = $d->invoice_id;
                }
            }
        }

        $data = [];

        $usersIds = [];

        foreach($usersList['data'] as $i)
        {
            $usersIds[] = $i->user_id;
        }

        foreach($usersInvoicesIds as $uid => $iids)
        {
            if( in_array($uid, $usersIds) )
            {
                $user = StoreInvoice::find($uid);

                $invoices = [];
                
                foreach($iids as $i)
                {
                    $invoices[] = InvoiceS::find($i);
                }

                $user->invoices = $invoices;
                
                $data[] = $user;
            }
        }

        $status = count($data) > 0 ? 200 : 204;

        return
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No invoices found.' ,
                'count'      => $usersList['count'] ,
                'pagination' => $usersList['pagination'] ,
                'users'      => $data ,
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

        switch($state)
        {
            case 'accept':

                if( $invoice->state == InvoiceState::Pending )
                {
                    $invoice->state = InvoiceState::Accepted;
                    $invoice->store_comment = $comment;
                    $invoice->save();
                }
                break;

            case 'reject':

                if( $invoice->state == InvoiceState::Pending )
                {
                    $invoice->state = InvoiceState::Rejected;
                    $invoice->store_comment = $comment;
                    $invoice->save();

                    foreach($invoice->items as $item)
                    {
                        StoreProduct::where('id', $item->store_product_id)
                        ->increment('warehouse_count', $item->count);
                    }
                }
                break;
                    
            case 'sending':

                if( $invoice->state == InvoiceState::Accepted )
                {
                    $invoice->state = InvoiceState::Sending;
                    $invoice->store_comment = $comment;
                    $invoice->save();
                }
                break;
                
            case 'finished':

                if( $invoice->state == InvoiceState::Sending )
                {
                    $invoice->state = InvoiceState::Finished;
                    $invoice->store_comment = $comment;
                    $invoice->save();
                }
                break;
        }

        return $this->getList($request);
    }

    
}

<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\UserInvoice;
use App\Models\InvoiceState;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Http\Helpers\SearchHelper;
use App\Models\InvoiceU;

class UserInvoiceController extends Controller
{
    /**
     * Return all user's invoices with filter
     * 
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getList(Request $request)
    {
        $storesInvoices = Invoice
        ::selectRaw('id as invoice_id, store_id, user_id, state, COUNT(id) as invoices_count, created_at')
        ->groupBy('user_id', 'store_id', 'id', 'state', 'created_at');

        $invoiceItems = InvoiceItem
        ::selectRaw('invoice_id, base_product_id as product_id');

        $products = Product
        ::selectRaw('id as product_id, title, brand_id');
        
        $categories = ProductCategory
        ::selectRaw('product_id, category_id');
        
        $qbuilder = UserInvoice::distinct()

        ->leftJoinSub($storesInvoices, 's_invoices', function ($join) 
        {
            $join->on('stores.id', 's_invoices.store_id');
        })

        ->where('s_invoices.invoices_count', '>', 0)

        ->where('s_invoices.user_id', $request->user->id)

        ->leftJoinSub($invoiceItems, 'v_items', function ($join) 
        {
            $join->on('s_invoices.invoice_id', 'v_items.invoice_id');
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
        $invoicesqbuilder = $invoicesqbuilder->selectRaw('s_invoices.store_id, s_invoices.invoice_id, s_invoices.created_at');

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
            ] , 
            'filterUserInvoices' ,
            true
        );

        $storesqbuilder = clone $qbuilder;
        $storesqbuilder = $storesqbuilder->selectRaw('s_invoices.store_id');
        
        $storesFilters = $request->query();

        unset($storesFilters['order']);

        $storesList = SearchHelper::dataWithFilters(
            $storesFilters , 
            clone $storesqbuilder , 
            null , 
            [
                'state'        => null ,
                'title'        => null ,
                'brand_id'     => null ,
                'category_id'  => null ,
                'name'         => null ,

                'order'        => null
            ] , 
            'filterUserInvoices' ,
        );

        $storesIds = [];

        foreach($invoicesList as $d)
        {
            $storesIds[] = $d->store_id;
        }

        $storesIds = array_values( array_unique($storesIds) );

        $storesInvoicesIds = [];
        
        foreach($storesIds as $sid)
        {
            $storesInvoicesIds[$sid] = [];

            foreach($invoicesList as $d)
            {
                if( $sid == $d->store_id )
                {
                    $storesInvoicesIds[$sid][] = $d->invoice_id;
                }
            }
        }

        $data = [];

        $storesIds = [];

        foreach($storesList['data'] as $i)
        {
            $storesIds[] = $i->store_id;
        }

        foreach($storesInvoicesIds as $sid => $iids)
        {
            if( in_array($sid, $storesIds) )
            {
                $user = UserInvoice::find($sid);

                $invoices = [];
                
                foreach($iids as $i)
                {
                    $invoices[] = InvoiceU::find($i);
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
                'count'      => $storesList['count'] ,
                'pagination' => $storesList['pagination'] ,
                'stores'     => $data
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
        $invoice = Invoice::find($invoiceId);

        if( $request->input('state') == 'cancel' && $invoice->state == InvoiceState::Pending )
        {
            $invoice->state = InvoiceState::Canceled;
            $invoice->user_comment = $request->input('comment') ?? '';
            $invoice->save();

            foreach($invoice->items as $item)
            {
                StoreProduct::where('id', $item->store_product_id)
                ->increment('warehouse_count', $item->count);
            }
        }

        return $this->getList($request);
    }

}

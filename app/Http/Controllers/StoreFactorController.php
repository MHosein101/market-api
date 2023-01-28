<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Factor;
use App\Models\Product;
use App\Models\FactorItem;
use App\Models\FactorState;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\FactorHelper;
use App\Http\Helpers\SearchHelper;
use App\Models\StoreFactor;

class StoreFactorController extends Controller
{
    
    /**
     * Return all store's factors with filter
     * 
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getList(Request $request)
    {
        $userFactors = Factor
        ::selectRaw('id as factor_id, store_id, user_id, COUNT(id) as factors_count')
        ->groupBy('user_id', 'store_id', 'id');

        $factorItems = FactorItem
        ::selectRaw('factor_id, base_product_id as product_id, state');

        $products = Product
        ::selectRaw('id as product_id, title, brand_id');
        
        $factors = StoreFactor::selectRaw('users.*')->distinct()

        ->leftJoinSub($userFactors, 'u_factors', function ($join) 
        {
            $join->on('users.id', 'u_factors.user_id');
        })

        ->where('u_factors.factors_count', '>', 0)

        ->where('u_factors.store_id', $request->user->store_id)

        ->leftJoinSub($factorItems, 'f_items', function ($join) 
        {
            $join->on('u_factors.factor_id', 'f_items.factor_id');
        })

        ->leftJoinSub($products, 'i_products', function ($join) 
        {
            $join->on('f_items.product_id', 'i_products.product_id');
        });

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            clone $factors , 
            null , 
            [
                'state'        => null ,
                'title'        => null ,
                'brand_id'     => null ,
                'category_id'  => null ,
                'name'         => null ,
                'number'       => null ,
            ] , 
            'filterStoreFactors'
        );

        extract($result);

        $status = count($data) > 0 ? 200 : 204;

        return
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No factor found.' ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'factors'    => $data
            ]
            , 200);
    }

    /**
     * Change factor's item state
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function changeFactorItemState(Request $request)
    {
        $itemsCount = (int)$request->input('items_count', 0);

        $state = $request->input('state');

        $comment = $request->input('comment') ?? '';
        
        for($i = 0; $i < $itemsCount; $i++) 
        {
            $id = (int)$request->input("items_{$i}");

            $factorItem = FactorItem::find($id);

            $allowChange = false;

            $restock = false;
    
            $isComment = false;
    
            $newState = null;
    
            switch($state)
            {
                case 'accept':
    
                    if( $factorItem->state == FactorState::Pending )
                    {
                        $newState = FactorState::Accepted;
                        $isComment = true;
                        $allowChange = true;
                    }
                    break;
    
                case 'reject':
    
                    if( $factorItem->state == FactorState::Pending )
                    {
                        $newState = FactorState::Rejected;
                        $isComment = true;
                        $allowChange = true;
                        $restock = true;
                    }
                    break;
                        
                case 'sending':
    
                    if( $factorItem->state == FactorState::Accepted )
                    {
                        $newState = FactorState::Sending;
                        $allowChange = true;
                    }
                    break;
                    
                case 'finished':
    
                    if( $factorItem->state == FactorState::Sending )
                    {
                        $newState = FactorState::Finished;
                        $allowChange = true;
                    }
                    break;
            }
    
            if($allowChange)
            {
                $updateData = 
                [ 
                    'state'      => $newState ,
                    'store_note' => $isComment ? $comment : ''
                ];
    
                FactorItem::where('id', $id)->update($updateData);
            }

            if($restock)
            {
                StoreProduct::where('id', $factorItem->store_product_id)
                ->increment('warehouse_count', $factorItem->count);
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

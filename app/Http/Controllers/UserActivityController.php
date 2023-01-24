<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UserHistory;
use App\Models\UserAnalytic;
use App\Models\UserFavorite;
use Illuminate\Http\Request;
use App\Http\Helpers\SearchHelper;

/**
 *  Manage normal user's activity
 *  Histories, Favorites, Analytics list
 * 
 * @author Hosein marzban
 */ 
class UserActivityController extends Controller
{
    /**
     * Return products that user marked as favorite
     * 
     * @see SearchHelper::getUserMarkedItems(int, Model, array) : Model[]
     *
     * @param  Request $request
     * 
     * @return Response
     */ 
    public function getFavorites(Request $request)
    {
        $result = SearchHelper::getUserMarkedItems($request->user->id, UserFavorite::class, $request->query());
        extract($result);

        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No favorite products found.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'products' => $data
        ], 200);
    }

    /**
     * If $productId is in user's favorites, delete it
     * If $productId is not in user's favorites, add it to list
     * 
     * @see SearchHelper::getUserMarkedItems(int, Model, array) : Model[]
     *
     * @param  Request $request
     * @param  int $productId
     * 
     * @return Response
     */ 
    public function modifyFavorites(Request $request, $productId)
    {
        $record = UserFavorite::where('user_id', $request->user->id)->where('product_id', $productId)->first();
        $status = 200;
        $msg = '';

        if($record == null) {
            UserFavorite::create([
                'user_id' => $request->user->id ,
                'product_id' => $productId
            ]);

            $status = 201;
            $msg = 'Product added to favorites';
        }
        else {
            $record->delete();
            $msg = 'Product removed from favorites';
        }

        $result = SearchHelper::getUserMarkedItems($request->user->id, UserFavorite::class, $request->query());
        extract($result);

        $realStatus = $status;

        if(count($data) == 0) {
            $status = 204;
            $msg = 'No favorite products found.';
        }

        return response()
        ->json([ 
            'status' => $status ,
            'message' => $msg ,
            'count' => $count ,
            'pagination' => $pagination ,
            'products' => $data
        ], $realStatus);
    }
    
    /**
     * Return products that user marked as analytics
     * 
     * @see SearchHelper::getUserMarkedItems(int, Model, array) : Model[]
     *
     * @param  Request $request
     * 
     * @return Response
     */ 
    public function getAnalytics(Request $request)
    {
        $result = SearchHelper::getUserMarkedItems($request->user->id, UserAnalytic::class, $request->query());
        extract($result);

        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No analytic products found.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'products' => $data
        ], 200);
    }

    /**
     * If $productId is in user's analytics, delete it
     * If $productId is not in user's analytics, add it to list
     * 
     * @see SearchHelper::getUserMarkedItems(int, Model, array) : Model[]
     *
     * @param  Request $request
     * @param  int $productId
     * 
     * @return Response
     */ 
    public function modifyAnalytics(Request $request, $productId)
    {
        $record = UserAnalytic::where('user_id', $request->user->id)->where('product_id', $productId)->first();
        $status = 200;
        $msg = '';

        if($record == null) {
            UserAnalytic::create([
                'user_id' => $request->user->id ,
                'product_id' => $productId
            ]);

            $status = 201;
            $msg = 'Product added to analytics';
        }
        else {
            $record->delete();
            $msg = 'Product removed from analytics';
        }

        $result = SearchHelper::getUserMarkedItems($request->user->id, UserAnalytic::class, $request->query());
        extract($result);

        $realStatus = $status;

        if(count($data) == 0) {
            $status = 204;
            $msg = 'No analytic products found.';
        }

        return response()
        ->json([ 
            'status' => $status ,
            'message' => $msg ,
            'count' => $count ,
            'pagination' => $pagination ,
            'products' => $data
        ], $realStatus);
    }

    /**
     * Get user's visited products
     * 
     * @see SearchHelper::getUserMarkedItems(int, Model, array) : Model[]
     *
     * @param  Request $request
     * 
     * @return Response
     */ 
    public function getHistory(Request $request)
    {
        $result = SearchHelper::getUserMarkedItems($request->user->id, UserHistory::class, $request->query());
        extract($result);

        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No products are in history' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'products' => $data
        ], 200);
    }

    /**
     * If $productId is null, delete all product ids in user's history
     * If $productId is number then add this id to user's history
     * 
     * @see SearchHelper::getUserMarkedItems(int, Model, array) : Model[]
     *
     * @param  Request $request
     * @param  string|null $productSlug
     * 
     * @return Response
     */ 
    public function modifyHistory(Request $request, $productSlug = null)
    {
        $status = 200;
        $msg = 'Already exists';

        if($productSlug == null) {
            UserHistory::where('user_id', $request->user->id)->delete();

            $msg = 'History cleared';
        }
        else {
            $product = Product::where('slug', $productSlug)->first();

            if($product != null) {

                $record = UserHistory::where('user_id', $request->user->id)->where('product_id', $product->id)->first();

                if($record == null) {
                    UserHistory::create([
                        'user_id' => $request->user->id ,
                        'product_id' => $product->id
                    ]);

                    $status = 201;
                    $msg = 'Product added to history';
                }
            }
        }

        $result = SearchHelper::getUserMarkedItems($request->user->id, UserHistory::class, $request->query());
        extract($result);

        $realStatus = $status;

        if(count($data) == 0)
            $status = 204;

        return response()
        ->json([ 
            'status' => $status ,
            'message' => $msg ,
            'count' => $count ,
            'pagination' => $pagination ,
            'products' => $data
        ], $realStatus);
    }
    
}

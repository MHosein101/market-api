<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UserHistory;
use App\Models\UserAnalytic;
use App\Models\UserFavorite;
use Illuminate\Http\Request;
use App\Http\Helpers\SearchHelper;

class UserActivityController extends Controller
{
    /**
     * Return products that user marked as favorite
     * 
     * @see SearchHelper::getUserMarkedItems()
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getFavorites(Request $request)
    {
        $result = SearchHelper::getUserMarkedItems($request->user->id, UserFavorite::class, $request->query());

        extract($result);

        $status = count($data) > 0 ? 200 : 204;

        return 
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No favorite products found.' ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'products'   => $data
            ], 200);
    }

    /**
     * If $productId is in user's favorites, delete it
     * else add it to list
     *
     * @param \Illuminate\Http\Request
     * @param  int $productId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function modifyFavorites(Request $request, $productId)
    {
        $record = UserFavorite::where('user_id', $request->user->id)->where('product_id', $productId)->first();

        if($record == null) 
        {
            UserFavorite::create(
            [
                'user_id'    => $request->user->id ,
                'product_id' => $productId
            ]);
        }
        else 
        {
            $record->delete();
        }

        return $this->getFavorites($request);
    }
    
    /**
     * Return products that user marked as analytics
     * 
     * @see SearchHelper::getUserMarkedItems()
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getAnalytics(Request $request)
    {
        $result = SearchHelper::getUserMarkedItems($request->user->id, UserAnalytic::class, $request->query());

        extract($result);

        $status = count($data) > 0 ? 200 : 204;

        return 
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No analytic products found.' ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'products'   => $data
            ], 200);
    }

    /**
     * If $productId is in user's analytics, delete it
     * else add it to list
     *
     * @param \Illuminate\Http\Request
     * @param  int $productId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function modifyAnalytics(Request $request, $productId)
    {
        $record = UserAnalytic::where('user_id', $request->user->id)->where('product_id', $productId)->first();

        if($record == null) 
        {
            UserAnalytic::create(
            [
                'user_id'    => $request->user->id ,
                'product_id' => $productId
            ]);
        }
        else 
        {
            $record->delete();
        }

        return $this->getAnalytics($request);
    }

    /**
     * Return user's visited products history
     * 
     * @see SearchHelper::getUserMarkedItems()
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getHistory(Request $request)
    {
        $result = SearchHelper::getUserMarkedItems($request->user->id, UserHistory::class, $request->query());

        extract($result);

        $status = count($data) > 0 ? 200 : 204;

        return 
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No products are in history' ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'products'   => $data
            ], 200);
    }

    /**
     * If $productId is in user's history, delete it
     * else add it to list
     *
     * @param \Illuminate\Http\Request
     * @param  string|null $productSlug
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function modifyHistory(Request $request, $productSlug = null)
    {
        if($productSlug == null) // clear history
        {
            UserHistory::where('user_id', $request->user->id)->delete();
        }
        else // add to history
        {
            $product = Product::where('slug', $productSlug)->first();

            if($product != null) // if slug is valid
            {
                $record = UserHistory::where('user_id', $request->user->id)->where('product_id', $product->id)->first();

                if($record == null) // avoid duplicate
                {
                    UserHistory::create(
                    [
                        'user_id'    => $request->user->id ,
                        'product_id' => $product->id
                    ]);
                }
            }
        }

        return $this->getHistory($request);
    }
    
}

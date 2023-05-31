<?php

namespace App\Http\Controllers;

use App\Http\Helpers\PublicSearchHelper;
use App\Models\User;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use App\Models\UserToken;
use App\Models\UserHistory;
use App\Models\StoreProduct;
use App\Models\UserAnalytic;
use App\Models\UserFavorite;
use Illuminate\Http\Request;
use App\Models\UserAccountType;
use App\Models\UserSearchQuery;
use App\Http\Helpers\CartHelper;
use App\Http\Helpers\DataHelper;
use App\Models\AdminNotification;
use App\Http\Helpers\SearchHelper;
use Illuminate\Support\Facades\Hash;
use App\Http\Helpers\PublicHomePageHelper;

class UserController extends Controller
{
    
    /**
     * Return admin's data count
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function adminDataCount(Request $request)
    {
        $brands = Brand::withTrashed()->selectRaw('id')->count();
        $categories = Category::withTrashed()->selectRaw('id')->count();
        $products = Product::withTrashed()->selectRaw('id')->count();
        $stores = Store::withTrashed()->selectRaw('id')->count();
        $users = User::withTrashed()->selectRaw('id')->where('account_type', UserAccountType::Normal)->count();
        $notifications = AdminNotification::where('is_new', true)->count();

        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'OK' ,
                'count'   => 
                [
                    'brands'     => $brands ,
                    'categories' => $categories ,
                    'products'   => $products ,
                    'stores'     => $stores ,
                    'users'      => $users ,
                    'notifications' => $notifications ,
                ]
            ], 200);
    }
    
    /**
     * Return store's data count
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function storeDataCount(Request $request)
    {
        $products = StoreProduct::withTrashed()->selectRaw('id')->where('store_id', $request->user->store_id)->count();

        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'OK' ,
                'count'   => 
                [
                    'products' => $products
                ]
            ], 200);
    }

    /**
     * Return current user information
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function info(Request $request)
    {
        $user = User::find($request->user->id);

        $cart = CartHelper::cartSummary();

        return 
            response()
            ->json(
            [ 
                'status'     => 200 ,
                'message'    => 'OK' ,
                'user'       => $user ,
                'cart_count' => $cart['cart_count'] ,
                'cart'       => $cart['cart'] ,
            ], 200);
    }
    

    /**
     * Clear user searches history
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function clearSearches(Request $request)
    {
        UserSearchQuery::where('user_id', $request->user->id)->delete();

        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'OK' ,
                'search_bar' => PublicSearchHelper::searchBarData($request->user)
            ], 200);
    }
    
    /**
     * Change user password
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function changePassword(Request $request)
    {
        $v = DataHelper::validate( response() , $request->post() , 
        [
            'old_password' => [ 'رمز عبور قدیمی', 'required|min:6' ] ,
            'new_password' => [ 'رمز عبور جدید', 'required|min:6' ] ,
        ]);
        if( $v['code'] == 400 )
        {
            return $v['response'];
        }

        $user = User::find($request->user->id);

        $checkPassword = true;

        $checks = 
        [
            'old_password' => [ 'رمز عبور فعلی صحیح نیست' ] ,
            'new_password' => [ 'مقدار رمز عبور جدید نمیتواند رمز فعلی باشد' ] ,
        ];

        foreach($checks as $key => $error) 
        {
            if($user->is_password)
            {
                $checkPassword = Hash::check($request->input($key), $user->password);
            }
            else
            {
                $checkPassword = $request->input($key) == $user->phone_number_primary; // default password
            }

            $checkPassword = $key == 'new_password' ? $checkPassword : !$checkPassword;

            if($checkPassword) 
            {
                return 
                    response()
                    ->json(
                    [ 
                        'status' => 400 ,
                        'errors' => $error ,
                    ], 400);
            }
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'رمز عبور با موفقیت تغییر یافت'
            ], 200);
    }

    /**
     * Delete user's api token that sent this request with (logout)
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function logout(Request $request)
    {
        UserToken::where('token', $request->apiToken)->delete();
        
        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'Logged out successfully.' ,
            ], 200);
    }
    

}

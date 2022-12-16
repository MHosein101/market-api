<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use App\Models\UserToken;
use App\Models\UserHistory;
use App\Models\StoreProduct;
use App\Models\UserFavorite;
use Illuminate\Http\Request;
use App\Models\UserAccountType;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\SearchHelper;
use App\Models\UserAnalytic;
use Illuminate\Support\Facades\Hash;

/**
 *  Normal users panel content management
 * 
 * @author Hosein marzban
 */ 
class UserController extends Controller
{
    
    /**
     * Return admin's data count
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function adminDataCount(Request $request)
    {
        $brands = Brand::withTrashed()->selectRaw('id')->get();
        $categories = Category::withTrashed()->selectRaw('id')->get();
        $products = Product::withTrashed()->selectRaw('id')->get();
        $stores = Store::withTrashed()->selectRaw('id')->get();
        $users = User::withTrashed()->selectRaw('id')->where('account_type', UserAccountType::Normal)->get();

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'count' => [
                'brands' => count($brands) ,
                'categories' => count($categories) ,
                'products' => count($products) ,
                'stores' => count($stores) ,
                'users' => count($users) ,
            ]
        ], 200);
    }
    
    /**
     * Return store's data count
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function storeDataCount(Request $request)
    {
        $products = StoreProduct::withTrashed()->selectRaw('id')->where('store_id', $request->user->store_id)->get();

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'count' => [
                'products' => count($products)
            ]
        ], 200);
    }

    /**
     * Return current user information
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function info(Request $request)
    {
        $user = User::find($request->user->id);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'OK' ,
            'user' => $user
        ], 200);
    }
    
    /**
     * Change user password
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function changePassword(Request $request)
    {
        $v = DataHelper::validate( response() , $request->post() , 
        [
            'old_password' => [ 'رمز عبور قدیمی', 'required|min:6' ] ,
            'new_password' => [ 'رمز عبور جدید', 'required|min:6' ] ,
        ]);
        if( $v['code'] == 400 ) return $v['response'];

        $user = User::find($request->user->id);
        $checkPassword = true;

        $checks = [
            'old_password' => [ 'رمز عبور فعلی صحیح نیست' ] ,
            'new_password' => [ 'مقدار رمز عبور جدید نمیتواند رمز فعلی باشد' ] ,
        ];

        foreach($checks as $key => $error) {
            if($user->is_password)
                $checkPassword = Hash::check($request->input($key), $user->password);
            else
                $checkPassword = $request->input($key) == $user->phone_number_primary;

            $checkPassword = $key == 'new_password' ? $checkPassword : !$checkPassword;

            if($checkPassword) {
                return response()
                ->json([ 
                    'status' => 400 ,
                    'errors' => $error ,
                ], 400);
            }
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'رمز عبور با موفقیت تغییر یافت'
        ], 200);
    }

    /**
     * Delete user's api token that sent this request with (logout)
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function logout(Request $request)
    {
        UserToken::where('token', $request->apiToken)->delete();
        
        return response()
        ->json([ 
            'status' => 200 ,
            'message' => 'Logged out successfully.' ,
        ], 200);
    }
    

}

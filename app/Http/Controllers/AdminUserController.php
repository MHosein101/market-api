<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use App\Models\UserAccountType;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\SearchHelper;
use Illuminate\Support\Facades\Hash;

/**
 * Admin panel users management
 * 
 * @author Hosein marzban
 */ 
class AdminUserController extends Controller
{

    /**
     * Return all users with filter OR one user by id
     * 
     * @see SearchHelper::dataWithFilters(array, QueryBuilder, string|null, array, string|null) : Model[]
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function getList(Request $request)
    {
        if( $request->query('id') != null ) {

            $user = User::withTrashed()->where('account_type', UserAccountType::Normal)->find( $request->query('id') );
            $status = ( $user != null ) ? 200 : 404;

            return response()
            ->json([ 
                'status' => $status ,
                'message' => ($status == 200) ? 'OK' : 'No user found.' ,
                'user' => $user
            ], $status);
        }

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            User::where('account_type', UserAccountType::Normal) , 
            null , 
            [ 
                'full_name' => null ,
                'national_code' => null ,
                'number' => null ,
            ] , 
            'filterUsers'
        );
        extract($result);

        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No user found.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'users' => $data
        ], 200);
    }

    /**
     * Create new user or Update existing user by id
     * if $signuUp equals true, then return results as array, NOT response
     * 
     * @see DataHelper::validate(Response, array) : array
     * @see DataHelper::dataImage(Request, boolean, string, Model, int, string, string) : void
     * 
     * @param Request $request
     * @param int|null $userId
     * @param boolean $signUp
     * 
     * @return Response
     */ 
    public function createOrUpdateUser(Request $request, $userId = null, $signUp = false)
    {
        $isCreate = ($userId == null) ? true : false;
        
        $uniqueIgnore = $isCreate ? '' : ",$userId,id";

        $v = DataHelper::validate( response() , $request->post() , 
        [
            'full_name' => [ 'نام و نام خانوادگی', 'required|filled|between:3,50' ] ,
            'national_code' => [ 'کد ملی', 'required|filled|numeric|digits:10|unique:users,national_code' . $uniqueIgnore ] ,
            'phone_number_primary' => [ 'شماره موبایل اول', 'required|digits_between:10,11|starts_with:09,9|unique:users,phone_number_primary' . $uniqueIgnore ] ,
            'phone_number_secondary' => [ 'شماره موبایل دوم', 'nullable|digits_between:10,11|starts_with:09,9' ] ,
            'house_number' => [ 'تلفن ثابت', 'nullable|digits_between:10,13' ] ,

            'password' => [ 'رمز عبور', 'nullable|min:6' ] ,
            // 'password_repeat' => [ 'تکرار رمز عبور', 'nullable|min:6|required_with:password|same:password' ] ,
            
            'address_province' => [ 'استان', 'nullable|min:2' ] ,
            'address_city' => [ 'شهر', 'nullable|min:2' ] ,
            'address_detail' => [ 'آدرس', 'required|min:3' ] ,
            'address_postcode' => [ 'کد پستی', 'nullable|numeric' ] ,
        ]);
        if( $v['code'] == 400 ) {
            if($signUp)
                return [
                    'ok' => false ,
                    'errors' => $v['response']
                ];
            else 
                return $v['response'];
        }

        $v = DataHelper::validate( response() , $request->file() , 
        [
            'profile_image' => [ 'عکس پروفایل', 'file|image|between:16,1024' ] ,
        ]);
        if( $v['code'] == 400 ) {
            if($signUp)
                return [
                    'ok' => false ,
                    'errors' => $v['response']
                ];
            else 
                return $v['response'];
        }

        $userData = [
            'full_name' => $request->post('full_name') ,
            'national_code' => $request->post('national_code') ,
            'phone_number_primary' => $request->post('phone_number_primary') ,
            'phone_number_secondary' => DataHelper::post('phone_number_secondary', '') ,
            'house_number' => DataHelper::post('house_number', '') ,
        ];

        if($request->post('password') != null)
            $userData['password'] = Hash::make($request->post('password'));

        $userAddress = [
            'province' => DataHelper::post('address_province', '') ,
            'city' => DataHelper::post('address_city', '') ,
            'detail' => DataHelper::post('address_detail', '') ,
            'post_code' => DataHelper::post('address_postcode', '') ,
        ];

        $user = null;

        if($isCreate) {
            $userData['account_type'] = UserAccountType::Normal;
            $user = User::create($userData);

            $userId = $user->id;
            
            $userAddress['user_id'] = $userId;
            UserAddress::create($userAddress);
        }
        else {
            User::withTrashed()
            ->where('id', $userId)
            ->update($userData);
            
            UserAddress::where('user_id', $userId)->update($userAddress);
            
            $user = User::withTrashed()->find($userId);
        }

        DataHelper::dataImage($request, $isCreate, 'users', User::class, $userId, 'profile_image', 'profile_image');

        if($signUp) {
            return [
                'ok' => true ,
                'user' => $user
            ];
        }
        else {
            $status = $isCreate ? 201 : 200;
            return response()
            ->json([ 
                'status' => $status ,
                'message' =>  $isCreate ? 'User created.' : 'User data updated.' ,
                'user' => $user
            ], $status);
        }
    }

    /**
     * Soft delete or Restore user
     * 
     * @param Request $request
     * @param int $userId
     * 
     * @return Response
     */ 
    public function changeUserState(Request $request, $userId)
    {
        $check = User::withTrashed()->find($userId);
        $msg = '';

        if($check->deleted_at == null) {
            User::where('id', $userId)->delete();
            $msg = 'User soft deleted.';
        }
        else {
            User::withTrashed()->where('id', $userId)->restore();
            $msg = 'User record restored.';
        }

        $user = User::withTrashed()->find($userId);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => $msg ,
            'user' => $user
        ], 200);
    }

}

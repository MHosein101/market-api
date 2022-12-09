<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use App\Models\UserAccountType;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\SearchHelper;
use Illuminate\Support\Facades\Hash;

/**
 * Admin panel stores management
 * 
 * @author Hosein marzban
 */ 
class AdminStoreController extends Controller
{
    
    /**
     * Return all stores with filter OR one store by id
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
            
            $store = Store::withTrashed()->find( $request->query('id') );
            $status = ( $store != null ) ? 200 : 404;

            return response()
            ->json([ 
                'status' => $status ,
                'message' => ($status == 200) ? 'OK' : 'No store found.' ,
                'store' => $store
            ], $status);
        }

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            Store::class , 
            '*' ,
            [ 
                'name' => null ,
                'economic_code' => null ,
                'number' => null ,
                'national_code' => null ,
                'province' => null ,
                'city' => null ,
            ] , 
            'filterStores'
        );

        extract($result);

        $status = ( count($data) > 0 ) ? 200 : 204;
        return response()
        ->json([ 
            'status' => $status ,
            'message' => ($status == 200) ? 'OK' : 'No store found.' ,
            'count' => $count ,
            'pagination' => $pagination ,
            'stores' => $data
        ], 200);
    }

    /**
     * Create new store and store User or Update existing store by id
     * if $signuUp equals true, then return results as array, NOT response
     * 
     * @see DataHelper::validate(Response, array) : array
     * @see DataHelper::dataImage(Request, boolean, string, Model, int, string, string) : void
     * 
     * @param Request $request
     * @param int|null $storeId
     * @param boolean $signUp
     * 
     * @return Response
     */ 
    public function createOrUpdateStore(Request $request, $storeId = null, $signUp = false)
    {
        $isCreate = ($storeId == null) ? true : false;

        $checkUniqueEC = $isCreate ? '|unique:stores,economic_code' : '';
        $checkUniqueNC = $isCreate ? '|unique:users,national_code' : '';
        $checkUniquePN = $isCreate ? '|unique:users,phone_number_primary' : '';

        $v = DataHelper::validate( response() , $request->all() , 
        [
            'name'          => [ 'نام فروشگاه', 'required|filled|between:3,50' ] ,
            'economic_code' => [ 'کد اقتصادی', 'nullable|numeric' . $checkUniqueEC ] ,

            'owner_full_name'     => [ 'نام و نام خانوادگی مالک فروشگاه', 'required|filled|between:3,50' ] ,
            'owner_phone_number'  => [ 'شماره همراه مالک فروشگاه', 'required|filled|digits_between:10,11|starts_with:09,9' . $checkUniquePN ] ,
            'owner_national_code' => [ 'کد ملی مالک فروشگاه', 'required|numeric|digits:10' . $checkUniqueNC ] ,
            'second_phone_number' => [ 'شماره همراه دوم', 'nullable|digits_between:10,11|starts_with:09,9' ] ,

            'owner_password' => [ 'رمز عبور', 'nullable|min:6' ] ,

            'province' => [ 'استان', 'nullable|between:2,50' ] ,
            'city'     => [ 'شهر', 'nullable|between:2,50' ] ,

            'office_address' => [ 'آدرس دفتر مرکزی', 'required|filled' ] ,
            'office_number'  => [ 'شماره تماس دفتر مرکزی', 'required|filled|digits_between:10,13' ] ,

            'warehouse_address' => [ 'آدرس انبار مرکزی', 'nullable' ] ,
            'warehouse_number'  => [ 'شماره تماس آنبار مرکزی', 'nullable|digits_between:10,13' ] ,

            'bank_name'         => [ 'نام بانک', 'nullable|between:3,50' ] ,
            'bank_code'         => [ 'کد شعبه بانک', 'nullable|numeric|digits:4' ] ,
            'bank_card_number'  => [ 'شماره کارت', 'nullable|numeric|digits:16' ] ,
            'bank_sheba_number' => [ 'شماره شبای حساب', 'nullable|numeric|digits:24' ] ,
            
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
            'logo_image'    => [ 'عکس لوگو', 'file|image|between:16,1024' ] ,
            'banner_image'  => [ 'عکس سر در (بنر)', 'file|image|between:64,2048' ] ,
            'license_image' => [ 'عکس مجوز', 'file|image|between:64,2048' ] ,
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

        $data = [
            'name'          => $request->input('name') ,
            'slug'          => preg_replace('/ +/', '-', $request->input('name')) ,
            'economic_code' => $request->input('economic_code', '') ,

            'owner_full_name'     => $request->input('owner_full_name') ,
            'owner_phone_number'  => $request->input('owner_phone_number') ,
            'second_phone_number' => $request->input('second_phone_number', '') ,

            'province' => $request->input('province', '') ,
            'city'     => $request->input('city', '') ,

            'office_address' => $request->input('office_address') ,
            'office_number'  => $request->input('office_number') ,

            'warehouse_address' => $request->input('warehouse_address', '') ,
            'warehouse_number'  => $request->input('warehouse_number', '') ,

            'bank_name'         => $request->input('bank_name', '') ,
            'bank_code'         => $request->input('bank_code', '') ,
            'bank_card_number'  => $request->input('bank_card_number', '') ,
            'bank_sheba_number' => $request->input('bank_sheba_number', '') ,
        ];

        $userData = [
            'full_name' => $request->input('owner_full_name') ,
            'national_code' => $request->input('owner_national_code') ,
            'phone_number_primary' => $request->input('owner_phone_number')
        ];

        $store = null;
        $user = null;

        if($isCreate) {
            $data['admin_confirmed'] = $signUp ? -1 : time();
            $store = Store::create($data);
            $storeId = $store->id;

            $userData['account_type'] = UserAccountType::Store;
            $userData['profile_image'] = $request->getSchemeAndHttpHost() . '/default.jpg';
            $userData['store_id'] = $storeId;
            
            if($request->input('owner_password') != null)
                $userData['password'] = Hash::make($request->input('owner_password'));

            $user = User::create($userData);
        }
        else {
            User::withTrashed()->where('store_id', $storeId)->update($userData);

            Store::withTrashed()->where('id', $storeId)->update($data);

            $store = Store::withTrashed()->find($storeId);
        }

        $filesKeys = ['logo_image', 'banner_image', 'license_image'];
        foreach($filesKeys as $column)
            DataHelper::dataImage($request, $isCreate, 'stores', Store::class, $storeId, $column, $column);


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
                'message' =>  $isCreate ? 'Store created.' : 'Store updated.' ,
                'store' => $store
            ], $status);
        }
    }

    /**
     * Soft delete or Restore store
     * 
     * @param Request $request
     * @param int $storeId
     * 
     * @return Response
     */ 
    public function changeStoreState(Request $request, $storeId)
    {
        $check = Store::withTrashed()->find($storeId);
        $msg = '';

        if($check->deleted_at == null) {
            Store::where('id', $storeId)->delete();
            $msg = 'Store soft deleted.';
        }
        else {
            Store::withTrashed()->where('id', $storeId)->restore();
            $msg = 'Store restored.';
        }

        $store = Store::withTrashed()->find($storeId);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => $msg ,
            'store' => $store
        ], 200);
    }

    /**
     * Confirm store by admin
     * 
     * @param Request $request
     * @param int $storeId
     * 
     * @return Response
     */ 
    public function confirmStore(Request $request, $storeId)
    {
        $check = Store::withTrashed()->find($storeId);
        $msg = '';

        if($check->is_pending) {
            Store::where('id', $storeId)->update([ 'admin_confirmed' => time() ]);
            $msg = 'Store confirmed.';
        }
        else {
            $msg = 'Store already confirmed.';
        }

        $store = Store::withTrashed()->find($storeId);

        return response()
        ->json([ 
            'status' => 200 ,
            'message' => $msg ,
            'store' => $store
        ], 200);
    }

}

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

        $uniqueIgnoreStore = $isCreate ? '' : ",$storeId,id";

        $userId = $isCreate ? -1 : User::where('store_id', $storeId)->get()->first()->id;
        $uniqueIgnoreUser = $isCreate ? '' : ",$userId,id";

        $v = DataHelper::validate( response() , $request->post() , 
        [
            'name'          => [ 'نام فروشگاه', 'required|filled|max:50' ] ,
            'economic_code' => [ 'کد اقتصادی', 'nullable|numeric|unique:stores,economic_code' . $uniqueIgnoreStore ] ,

            'owner_full_name'     => [ 'نام و نام خانوادگی مالک فروشگاه', 'required|filled|max:50' ] ,
            'owner_phone_number'  => [ 'شماره همراه مالک فروشگاه', 'required|filled|digits_between:10,11|starts_with:09,9|unique:users,phone_number_primary' . $uniqueIgnoreUser ] ,
            'owner_national_code' => [ 'کد ملی مالک فروشگاه', 'required|numeric|digits:10|unique:users,national_code' . $uniqueIgnoreUser ] ,
            'second_phone_number' => [ 'شماره همراه دوم', 'nullable|digits_between:10,11|starts_with:09,9' ] ,

            'owner_password' => [ 'رمز عبور', 'nullable|min:6' ] ,

            'province' => [ 'استان', 'nullable|max:50' ] ,
            'city'     => [ 'شهر', 'nullable|max:50' ] ,

            'office_address' => [ 'آدرس دفتر مرکزی', 'required|filled' ] ,
            'office_number'  => [ 'شماره تماس دفتر مرکزی', 'required|filled|digits_between:10,13' ] ,

            'warehouse_address' => [ 'آدرس انبار مرکزی', 'nullable' ] ,
            'warehouse_number'  => [ 'شماره تماس آنبار مرکزی', 'nullable|digits_between:10,13' ] ,

            'bank_name'         => [ 'نام بانک', 'nullable|max:50' ] ,
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
            'logo_image'    => [ 'عکس لوگو', 'file|image|between:4,1024' ] ,
            'banner_image'  => [ 'عکس سر در (بنر)', 'file|image|between:4,2048' ] ,
            'license_image' => [ 'عکس مجوز', 'file|image|between:4,2048' ] ,
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
            'name'          => $request->post('name') ,
            'slug'          => preg_replace('/ +/', '-', $request->post('name')) ,
            'economic_code' => DataHelper::post('economic_code', '') ,

            'owner_full_name'     => $request->post('owner_full_name') ,
            'owner_phone_number'  => $request->post('owner_phone_number') ,
            'second_phone_number' => DataHelper::post('second_phone_number', '') ,

            'province' => DataHelper::post('province', '') ,
            'city'     => DataHelper::post('city', '') ,

            'office_address' => $request->post('office_address') ,
            'office_number'  => $request->post('office_number') ,

            'warehouse_address' => DataHelper::post('warehouse_address', '') ,
            'warehouse_number'  => DataHelper::post('warehouse_number', '') ,

            'bank_name'         => DataHelper::post('bank_name', '') ,
            'bank_code'         => DataHelper::post('bank_code', '') ,
            'bank_card_number'  => DataHelper::post('bank_card_number', '') ,
            'bank_sheba_number' => DataHelper::post('bank_sheba_number', '') ,
        ];

        $userData = [
            'full_name' => $request->post('owner_full_name') ,
            'national_code' => $request->post('owner_national_code') ,
            'phone_number_primary' => $request->post('owner_phone_number')
        ];
        
        if($request->post('owner_password') != null)
            $userData['password'] = Hash::make($request->post('owner_password'));

        $store = null;
        $user = null;

        if($isCreate) {
            $data['admin_confirmed'] = $signUp ? -1 : time();
            $store = Store::create($data);
            $storeId = $store->id;

            $userData['account_type'] = UserAccountType::Store;
            $userData['profile_image'] = $request->getSchemeAndHttpHost() . '/default.jpg';
            $userData['store_id'] = $storeId;
            
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
            User::where('store_id', $storeId)->delete();
            $msg = 'Store soft deleted.';
        }
        else {
            Store::withTrashed()->where('id', $storeId)->restore();
            User::withTrashed()->where('store_id', $storeId)->restore();
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

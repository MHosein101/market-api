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
     * Return all stores with filters
     * if [id] query parameter is set, return a store by id
     * 
     * @see SearchHelper::dataWithFilters()
     * 
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getList(Request $request)
    {
        if( $request->query('id') != null ) 
        {
            $store = Store::withTrashed()->find( $request->query('id') );

            $status = ( $store != null ) ? 200 : 404;

            return 
                response()
                ->json(
                [ 
                    'status'  => $status ,
                    'message' => $status == 200 ? 'OK' : 'No store found.' ,
                    'store'   => $store
                ], $status);
        }

        $result = SearchHelper::dataWithFilters(
            $request->query() , 
            Store::class , 
            '*' ,
            [ 
                'name'     => null ,
                'number'   => null ,
                'province' => null ,
                'city'     => null ,
                'national_code' => null ,
                'economic_code' => null ,
            ] , 
            'filterStores'
        );

        extract($result);

        $status = count($data) > 0 ? 200 : 204;

        return 
            response()
            ->json(
            [ 
                'status'     => $status ,
                'message'    => $status == 200 ? 'OK' : 'No store found.' ,
                'count'      => $count ,
                'pagination' => $pagination ,
                'stores'     => $data
            ], 200);
    }

    /**
     * Create new store and store User or Update existing store by id
     * 
     * it also will be called from AuthController::signup()
     * if $signuUp is true, then return array, else return JsonResponse
     * 
     * @see DataHelper::validate()
     * @see DataHelper::dataImage()
     * 
     * @param \Illuminate\Http\Request
     * @param int|null $storeId
     * @param boolean $signUp
     * 
     * @return \Illuminate\Http\JsonResponse|array
     */ 
    public function createOrUpdateStore(Request $request, $storeId = null, $signUp = false)
    {
        $isCreate = $storeId == null;

        $uniqueIgnoreStore = $isCreate ? '' : ",$storeId,id";

        // it will be used in update
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
        if( $v['code'] == 400 ) 
        {
            if($signUp)
            {
                return 
                [
                    'ok'     => false ,
                    'errors' => $v['response']
                ];
            }
            else 
            {
                return $v['response'];
            }
        }

        $v = DataHelper::validate( response() , $request->file() , 
        [
            'logo_image'    => [ 'عکس لوگو', 'file|image|between:4,1024' ] ,
            'banner_image'  => [ 'عکس سر در (بنر)', 'file|image|between:4,2048' ] ,
            'license_image' => [ 'عکس مجوز', 'file|image|between:4,2048' ] ,
        ]);

        if( $v['code'] == 400 ) 
        {
            if($signUp)
            {
                return 
                [
                    'ok'     => false ,
                    'errors' => $v['response']
                ];
            }
            else 
            {
                return $v['response'];
            }
        }

        $data = 
        [
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

        $userData = 
        [
            'full_name' => $request->post('owner_full_name') ,
            'national_code' => $request->post('owner_national_code') ,
            'phone_number_primary' => $request->post('owner_phone_number')
        ];
        
        if($request->post('owner_password') != null) 
        {
            $userData['password'] = Hash::make($request->post('owner_password'));
        }

        $store = null;
        $user = null;

        if($isCreate) 
        {
            $data['admin_confirmed'] = $signUp ? -1 : time();

            $store = Store::create($data);
            $storeId = $store->id;

            $userData['account_type'] = UserAccountType::Store;
            $userData['profile_image'] = $request->getSchemeAndHttpHost() . '/default.jpg';
            $userData['store_id'] = $storeId;
            
            $user = User::create($userData);
        }
        else 
        {
            User::withTrashed()->where('store_id', $storeId)->update($userData);

            Store::withTrashed()->where('id', $storeId)->update($data);

            $store = Store::withTrashed()->find($storeId);
        }

        // process images
        foreach(['logo_image', 'banner_image', 'license_image'] as $column)
        {
            DataHelper::dataImage($request, $isCreate, 'stores', Store::class, $storeId, $column, $column);
        }


        if($signUp) 
        {
            return 
            [
                'ok'   => true ,
                'user' => $user
            ];
        }
        else 
        {
            $status = $isCreate ? 201 : 200;

            return 
                response()
                ->json(
                [ 
                    'status'   => $status ,
                    'message' =>  $isCreate ? 'Store created.' : 'Store updated.' ,
                    'store'   => $store
                ], $status);
        }
    }

    /**
     * Soft delete or Restore store
     * 
     * @param \Illuminate\Http\Request
     * @param int $storeId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function changeStoreState(Request $request, $storeId)
    {
        $store = Store::withTrashed()->find($storeId);

        if($store->deleted_at == null) 
        {
            $store->delete();

            User::where('store_id', $storeId)->delete();
        }
        else 
        {
            $store->restore();

            User::withTrashed()->where('store_id', $storeId)->restore();
        }

        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'OK' ,
                'store'   => $store
            ], 200);
    }

    /**
     * Confirm store by admin
     * 
     * @param \Illuminate\Http\Request
     * @param int $storeId
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function confirmStore(Request $request, $storeId)
    {
        $store = Store::withTrashed()->find($storeId);

        if($store->is_pending) 
        {
            $store->admin_confirmed = time();
            $store->save();
        }

        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'OK' ,
                'store'   => $store
            ], 200);
    }

}

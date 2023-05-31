<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Models\UserAccountType;
use App\Http\Helpers\CartHelper;
use App\Http\Helpers\DataHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Control requests for signup and login
 * 
 * @author Hosein marzban
 */ 
class AuthController extends Controller
{
    
    /**
     * Sign up a new user
     * if type is [user] it will create a normal user
     * if type is [store] it will create a store user
     * 
     * @see AdminUserController::createOrUpdateUser()
     * @see AdminStoreController::createOrUpdateStore()
     * @see User->generateApiToken()
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function signup(Request $request, $type)
    {
        $result = null;

        if( $type == 'user' )
        {
            $result = (new AdminUserController)->createOrUpdateUser($request, null, true);
        }
        else if($type == 'store')
        {
            $result = (new AdminStoreController)->createOrUpdateStore($request, null, true);
        }
        else
        {
            return 
                response()
                ->json(
                [
                    'status'  => 401 ,
                    'message' => 'Invalid request'
                ], 401);
        }

        if( !$result['ok'] )
        {
            return $result['errors'];
        }

        $user = $result['user'];

        $apiToken = $user->generateApiToken();

        return 
            response()
            ->json(
            [
                'status'       => 200 ,
                'message'      => 'حساب شما ایجاد شد' ,
                'phone_number' => $user->phone_number_primary ,
                'is_password'  => $user->is_password ,
                'API_TOKEN'    => $apiToken ,
            ], 200);
    }

    /**
     * All users can login with username and password
     * 
     * @see DataHelper::validate()
     * @see User->generateApiToken() : string
     * 
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function loginCredentials(Request $request)
    {
        $v = DataHelper::validate( response() , $request->post() , 
        [
            'national_code' => [ 'کد ملی', 'required|filled|digits:10' ] ,
            'password'      => [ 'رمز عبور', 'required|filled|min:6' ]
        ]);

        if( $v['code'] == 400 )
        {
            return $v['response'];
        }

        $user = User::where('national_code', $request->input('national_code'))->first();
        
        if($user == null) 
        {
            return 
                response()
                ->json(
                [ 
                    'status'  => 401 ,
                    'message' => 'Login failed' ,
                    'errors' => [ 'نام کاربری یا رمز عبور اشتباه است' ]
                ], 401); 
        }

        $checkPassword = true;

        if($user->is_password)
        {
            $checkPassword = Hash::check($request->input('password') , $user->password);
        }
        else // this else part should be deleted
        {
            $checkPassword = $request->input('password') == $user->phone_number_primary;
        }

        // if(Hash::check($request->input('password') , $user->password)) { // this condition is better
        if(!$checkPassword) 
        {
            return 
                response()
                ->json(
                [ 
                    'status'  => 401 ,
                    'message' => 'Login failed' ,
                    'errors' => [ 'نام کاربری یا رمز عبور اشتباه است' ]
                ], 401); 
        }

        $apiToken = $user->generateApiToken();

        $cart = CartHelper::cartSummary($user->id);
        
        return 
            response()
            ->json(
            [
                'status'       => 200 ,
                'message'      => 'با موفقیت وارد شدید' ,
                'phone_number' => $user->phone_number_primary ,
                'is_password'  => $user->is_password ,
                'API_TOKEN'    => $apiToken ,
                'user'         => $user ,
                'cart_count'   => $cart['cart_count'] ,
                'cart'         => $cart['cart'] ,
                ''
            ], 200)
            ->cookie('API_TOKEN', $apiToken, 60 * 24 * 14, null, 'http://localhost:3000', true, false);
    }

    
    /**
     * Return all users mobile numbers
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function getNumbers(Request $request)
    {
        $mobileNumbers = User::pluck('phone_number_primary')->all();

        $mobile2Numbers = User::pluck('phone_number_secondary')->all();

        $houseNumbers = User::pluck('house_number')->all();

        $warehouseNumbers = Store::pluck('warehouse_number')->all();

        $officeNumbers = Store::pluck('office_number')->all();

        $allNumbers = array_merge (
            $mobileNumbers, 
            $mobile2Numbers, 
            $houseNumbers, 
            $warehouseNumbers, 
            $officeNumbers
        );

        $numbers = [];

        // remove empty members that created because of users  that did not set their numbers
        foreach($allNumbers as $n)
        {
            if($n != '')
            {
                $numbers[] = $n;
            }
        }

        return 
            response()
            ->json(
            [ 
                'status'  => 200 ,
                'message' => 'OK' ,
                'numbers' => $numbers
            ], 200);
    }

    /**
     * Debug helper for developer
     * 
     * @see DataHelper::readLog()
     *
     * @param \Illuminate\Http\Request
     * 
     * @return void
     */ 
    public function debug(Request $request)
    {
        if( $request->query('db') != null ) // directly execute sql statements from url
        {
            $r = DB::select($request->query('db'));

            dd($r);
        }
        else if( $request->query('log') != null )
        {
            dd( DataHelper::readLog($request->query('log')) );
        }
    }

    /**
     * If user exists, give it a verification code
     * If user not exists, create new normal user then give it a verification code
     * 
     * @see DataHelper::validate()
     * @see User->generateVerificationCode()
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function loginByCode(Request $request)
    {
        $v = DataHelper::validate( response() , $request->post() , 
        [
            'phone_number' => [ 'شماره تماس', 'required|filled|digits_between:10,11|starts_with:09,9' ]
        ]);

        if( $v['code'] == 400 )
        {
            return $v['response'];
        }

        $phoneNumber = $request->input('phone_number');

        $user = User::where('phone_number_primary', $phoneNumber)->first();
        
        $message = 'کد تایید را وارد کنید';
        $isSignUp = false;
        $code = 200;

        if($user != null)
        {
            return 
                response()
                ->json(
                [ 
                    'status' => 401 ,
                    'message' => 'کاربری یافت نشد' ,
                ], 401);
        }

        if($user == null) 
        {
            $user = User::create(
            [ 
                'account_type'         => UserAccountType::Normal ,
                'full_name'            => 'user_' . \Illuminate\Support\Str::random(7) ,
                'phone_number_primary' => $phoneNumber ,
            ]);

            $message = 'حساب شما ایجاد شد';

            $isSignUp = true;

            $code = 201;
        }

        $verificationCode = $user->generateVerificationCode();

        return 
            response()
            ->json(
            [
                'status'            => $code ,
                'message'           => $message ,
                'is_signup'         => $isSignUp ,
                'verification_code' => $verificationCode , // for debug
            ], $code);

        // SHOULD SEND VERIFICATION CODE HERE BUT WE SKIP IT
    }

    /**
     * Verify users with verification code
     * If verification code is correct then give it an api token
     * 
     * @see DataHelper::validate()
     * @see User->generateApiToken()
     *
     * @param \Illuminate\Http\Request
     * 
     * @return \Illuminate\Http\JsonResponse
     */ 
    public function codeVerification(Request $request)
    {
        $v = DataHelper::validate( response() , $request->post() , 
        [
            'phone_number'      => [ 'شماره تماس', 'required|filled|digits_between:10,11|starts_with:09,9' ] ,
            'verification_code' => [ 'کداعتبارسنجی', 'required|filled|digits:4' ]
        ]);

        if( $v['code'] == 400 )
        {
            return $v['response'];
        }

        $phoneNumber = $request->input('phone_number');

        $user = User::where('phone_number_primary', $phoneNumber)->first();
        
        if($user == null) 
        {
            return 
                response()
                ->json(
                [ 
                    'status' => 401 ,
                    'message' => 'کاربری یافت نشد'
                ], 401);
        }

        if($user->verification_code != $request->input('verification_code'))
        {
            return 
                response()
                ->json(
                [ 
                    'status' => 401 ,
                    'message' => 'کد وارد شده اشتباه است' 
                ], 401); 
        }
            
        $apiToken = $user->generateApiToken();

        return 
            response()
            ->json(
            [
                'status'       => 200 ,
                'message'      => 'با موفقیت وارد شدید' ,
                'phone_number' => $phoneNumber ,
                'API_TOKEN'    => $apiToken
            ], 200)
            ->cookie('API_TOKEN', $apiToken, 60 * 24 * 14, null, 'http://localhost:3000', true, false);
    }

}

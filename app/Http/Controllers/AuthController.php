<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UserAccountType;
use App\Http\Helpers\DataHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Control requests for signup and login
 * 
 * @author Hosein marzban
 */ 
class AuthController extends Controller
{
    
    /**
     * Sign up a new user
     * if type equals 'user' it will create a normal user
     * if type equals 'store' it will create a store user
     * 
     * @see AdminUserController::createOrUpdateUser(Request, int|null, boolean) : array
     * @see AdminStoreController::createOrUpdateStore(Request, int|null, boolean) : array
     * @see User->generateApiToken() : string
     *
     * @param  Request $request
     * 
     * @return Response
     */ 
    public function signup(Request $request, $type)
    {
        $result = null;

        if($type == 'user')
            $result = (new AdminUserController)->createOrUpdateUser($request, null, true);
        else if($type == 'store')
            $result = (new AdminStoreController)->createOrUpdateStore($request, null, true);
        else
            return response()
            ->json([
                'status' => 401 ,
                'message' => 'Invalid request'
            ], 401);

        if(!$result['ok']) 
            return $result['errors'];

        $user = $result['user'];
        $apiToken = $user->generateApiToken();

        return response()
        ->json([
            'status' => 200 ,
            'message' => 'حساب شما ایجاد شد' ,
            'phone_number' => $user->phone_number_primary ,
            'is_password' => $user->is_password ,
            'API_TOKEN' => $apiToken ,
        ], 200);
    }

    /**
     * Admin and Store users login with username and password
     * 
     * @see DataHelper::validate(Response, array) : array
     * @see User->generateApiToken() : string
     * 
     * @param Request $request
     * 
     * @return Response
     */ 
    public function loginCredentials(Request $request)
    {
        $v = DataHelper::validate( response() , $request->post() , 
        [
            'national_code' => [ 'کد ملی', 'required|filled|digits:10' ] ,
            'password' => [ 'رمز عبور', 'required|filled|min:6' ]
        ]);
        if( $v['code'] == 400 ) return $v['response'];

        $user = User::where('national_code', $request->input('national_code'))->get()->first();
        
        if($user == null) {
            return response()
            ->json([ 
                'status' => 401 ,
                'message' => 'کاربری یافت نشد'
            ], 401); 
        }

        $checkPassword = true;

        if($user->is_password)
            $checkPassword = Hash::check($request->input('password') , $user->password);
        else
            $checkPassword = $request->input('password') == $user->phone_number_primary;

        if(!$checkPassword) {
            return response()
            ->json([ 
                'status' => 401 ,
                'message' => 'اطلاعات وارد شده نادرست است' // 'رمز عبور اشتباه است'
            ], 401); 
        }

        $apiToken = $user->generateApiToken();
        
        return response()
        ->json([
            'status' => 200 ,
            'message' => 'با موفقیت وارد شدید' ,
            'phone_number' => $user->phone_number_primary ,
            'is_password' => $user->is_password ,
            'API_TOKEN' => $apiToken ,
            ''
        ], 200)
        ->cookie('API_TOKEN', $apiToken, 60 * 24 * 14, null, 'http://localhost:3000', true, false);
    }

    
    /**
     * Debug helper for developer
     * 
     * @see DataHelper::readLog(string) : mixed
     *
     * @param  Request $request
     * 
     * @return Response
     */ 
    public function debug(Request $request)
    {
        if($request->query('db') != null) {
            $r = DB::select($request->query('db'));
            dd($r);
        }
        else if($request->query('log') != null) {
            dd(DataHelper::readLog($request->query('log')));
        }
    }

    /**
     * If normal user exists, give it a verification code
     * If normal user not exists, create new user then give it a verification code
     * 
     * @see DataHelper::validate(Response, array) : array
     * @see User->generateVerificationCode() : string
     *
     * @param  Request $request
     * 
     * @return Response
     */ 
    public function loginByCode(Request $request)
    {
        $v = DataHelper::validate( response() , $request->post() , 
        [
            'phone_number' => [ 'شماره تماس', 'required|filled|digits_between:10,11|starts_with:09,9' ]
        ]);
        if( $v['code'] == 400 ) return $v['response'];

        $phoneNumber = $request->input('phone_number');

        $user = User::where('phone_number_primary', $phoneNumber)->get()->first();
        
        $message = 'کد تایید را وارد کنید';
        $isSignUp = false;
        $code = 200;

        if($user != null && $user->account_type != UserAccountType::Normal)
            return response()
            ->json([ 
                'status' => 401 ,
                'message' => 'کاربری یافت نشد'
            ], 401);

        if($user == null) {

            $user = User::create([ 
                'account_type' => UserAccountType::Normal ,
                'full_name' => 'user_' . \Illuminate\Support\Str::random(7) ,
                'phone_number_primary' => $phoneNumber ,
            ]);

            $message = 'حساب شما ایجاد شد';
            $isSignUp = true;
            $code = 201;
        }

        $verificationCode = $user->generateVerificationCode();

        return response()
        ->json([
            'status' => $code ,
            'message' => $message ,
            'is_signup' => $isSignUp ,
            'verification_code' => $verificationCode , // FOR DEBUG
        ], $code);

        // * SHOULD SEND VERIFICATION CODE HERE BUT WE SKIP IT FOR DEBUG *
    }

    /**
     * Verify normal user with it's verification code
     * If verification code is correct then give it an api token
     * 
     * @see DataHelper::validate(Response, array) : array
     * @see User->generateApiToken() : string
     *
     * @param Request $request
     * 
     * @return Response
     */ 
    public function codeVerification(Request $request)
    {
        $v = DataHelper::validate( response() , $request->post() , 
        [
            'phone_number' => [ 'شماره تماس', 'required|filled|digits_between:10,11|starts_with:09,9' ] ,
            'verification_code' => [ 'کداعتبارسنجی', 'required|filled|digits:4' ]
        ]);
        if( $v['code'] == 400 ) return $v['response'];

        $phoneNumber = $request->input('phone_number');

        $user = User::where('phone_number_primary', $phoneNumber)->get()->first();
        
        if($user == null || $user->account_type != UserAccountType::Normal)
            return response()
            ->json([ 
                'status' => 401 ,
                'message' => 'کاربری یافت نشد'
            ], 401);

        if( $user->verification_code != $request->input('verification_code') )
            return response()
            ->json([ 
                'status' => 401 ,
                'message' => 'کد وارد شده اشتباه است' 
            ], 401); 
            
        $apiToken = $user->generateApiToken();

        return response()
        ->json([
            'status' => 200 ,
            'message' => 'با موفقیت وارد شدید' ,
            'phone_number' => $phoneNumber ,
            'API_TOKEN' => $apiToken
        ], 200)
        ->cookie('API_TOKEN', $apiToken, 60 * 24 * 14, null, 'http://localhost:3000', true, false);
    }

}

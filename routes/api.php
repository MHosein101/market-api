<?php

use App\Http\Controllers\AdminBrandController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminStoreController;
use App\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicSearchController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreProductController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\UserCartController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group([ 'middleware' => ['is-user', 'valid-query'] , 'prefix' => 'public' ], function () {
    Route::controller(PublicSearchController::class)->group(function() {
        Route::get('search', 'search');
        Route::get('categories', 'categories');
        Route::get('categories/{categorySlug}/path', 'categoryBreadCrump');
        Route::get('categories/{categorySlug}/sub', 'categoryChildrenTree');
        Route::get('brands', 'brands');
    });
});

Route::controller(AuthController::class)->group(function() {
    Route::get('debug', 'debug');
    
    Route::post('signup/{type}', 'signup');
    Route::post('login', 'loginByCode');
    Route::post('verify', 'codeVerification');
    Route::post('login/credentials', 'loginCredentials');
});

Route::group([ 'middleware' => ['user', 'valid-query'] , 'prefix' => 'user' ], function () {
    Route::controller(UserController::class)->group(function() {
        Route::get('/', 'info');
        Route::get('info', 'info');
        Route::post('password', 'changePassword');
        Route::delete('logout', 'logout');
    });
    Route::controller(UserActivityController::class)->group(function() {
        Route::get('favorites', 'getFavorites');
        Route::put('favorites/{productId}', 'modifyFavorites');
        Route::get('analytics', 'getAnalytics');
        Route::put('analytics/{productId}', 'modifyAnalytics');
        Route::get('history', 'getHistory');
        Route::put('history/{productId?}', 'modifyHistory');
    });
    Route::group([ 'prefix' => 'cart' ], function () {
        Route::controller(UserCartController::class)->group(function() {
            Route::get('/', 'getAll');
            Route::post('/{productId}', 'addProduct');
            Route::post('update', 'updateCart');
            Route::delete('/{itemId}', 'deleteItem');
            Route::delete('clear', 'clearCart');
        });
    });
});
Route::group([ 'middleware' => ['user', 'admin', 'valid-query'] , 'prefix' => 'admin' ], function () {
    Route::get('counter', [UserController::class,'adminDataCount']);
    
    Route::controller(AdminCategoryController::class)->group(function() {
        Route::get('categories', 'getList');
        Route::get('categories/list/{categoryId?}', 'getSimpleList');
        Route::post('categories', 'createOrUpdateCategory');
        Route::post('categories/{categoryId}/update', 'createOrUpdateCategory');
        Route::put('categories/{categoryId}/state', 'changeCategoryState');
    });
    Route::controller(AdminBrandController::class)->group(function() {
        Route::get('brands', 'getList');
        Route::post('brands', 'createOrUpdateBrand');
        Route::post('brands/{brandId}/update', 'createOrUpdateBrand');
        Route::put('brands/{brandId}/state', 'changeBrandState');
    });
    Route::controller(AdminProductController::class)->group(function() {
        Route::get('products', 'getList');
        Route::post('products', 'createOrUpdateProduct');
        Route::post('products/{productId}/update', 'createOrUpdateProduct');
        Route::put('products/{productId}/state', 'changeProductState');
        Route::delete('products/images/{imageId}', 'deleteProductImage');
    });
    Route::controller(AdminStoreController::class)->group(function() {
        Route::get('stores', 'getList');
        Route::post('stores', 'createOrUpdateStore');
        Route::post('stores/{storeId}/update', 'createOrUpdateStore');
        Route::put('stores/{storeId}/state', 'changeStoreState');
        Route::put('stores/{storeId}/confirm', 'confirmStore');
    });
    Route::controller(AdminUserController::class)->group(function() {
        Route::get('users', 'getList');
        Route::post('users', 'createOrUpdateUser');
        Route::post('users/{userId}/update', 'createOrUpdateUser');
        Route::put('users/{userId}/state', 'changeUserState');
    });
});

Route::get('/store', [StoreController::class,'info'])->middleware(['user', 'store']);

Route::group([ 'middleware' => ['user', 'store', 'store-confirmed', 'valid-query'] ], function () {

    Route::get('products/bases', [AdminProductController::class,'getList']);
    Route::get('products/categories', [AdminCategoryController::class,'getList']);
    Route::get('products/brands', [AdminBrandController::class,'getList']);

    Route::group([ 'prefix' => 'store' ], function () {
        Route::get('counter', [UserController::class,'storeDataCount']);

        Route::controller(StoreProductController::class)->group(function() {
            Route::get('products', 'getList');
            Route::post('products', 'createOrUpdateProduct');
            Route::post('products/{productId}/update', 'createOrUpdateProduct');
            Route::put('products/{productId}/state', 'changeProductState');
        });
    });

});
<?php

use App\Http\Controllers\AdminBrandController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminImageSliderController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminStoreController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\PublicHomePageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\PublicSearchController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreInvoiceController;
use App\Http\Controllers\StoreProductController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\UserCartController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserInvoiceController;

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

Route::group(
    [ 
        'middleware' => ['is-user', 'valid-query'] , 
        'prefix'     => 'public' 
    ], function () 
{
    Route::controller(PublicHomePageController::class)->group(function() 
    {
        Route::get('home', 'initialData');

        Route::get('searchbar', 'initialData');
    });
    Route::controller(PublicSearchController::class)->group(function() 
    {
        Route::get('search', 'search');
        Route::get('search/suggest', 'suggestQuery');

        Route::get('categories', 'categories');
        Route::get('categories/{categorySlug}/path', 'categoryBreadCrump');
        Route::get('categories/{categorySlug}/sub', 'categoryChildrenTree');

        Route::get('brands', 'brands');
    });
    Route::controller(PublicProductController::class)->group(function() 
    {
        Route::group(
            [ 
                'middleware' => ['valid-product'] , 
                'prefix' => 'product' 
            ], function () 
        {      
            Route::get('/{productSlug}', 'detail');
            Route::get('/{productSlug}/sales', 'sales');
            Route::get('/{productSlug}/similars', 'similars');
        });
    });
});

Route::controller(AuthController::class)->group(function() 
{
    Route::get('debug', 'debug');
    
    Route::post('signup/{type}', 'signup');
    Route::post('login/credentials', 'loginCredentials');

    Route::get ('numbers', 'getNumbers');

    Route::post('login', 'loginByCode');
    Route::post('verify', 'codeVerification');
});

Route::group(
    [ 
        'middleware' => ['user', 'valid-query'] , 
        'prefix' => 'user' 
    ], function () 
{
    Route::controller(UserController::class)->group(function() 
    {
        Route::get   ('/', 'info');
        Route::get   ('info', 'info');
        Route::post  ('password', 'changePassword');
        Route::delete('logout', 'logout');

        Route::delete('search', 'clearSearches');
    });
    Route::controller(UserActivityController::class)->group(function() 
    {
        Route::get('favorites', 'getFavorites');
        Route::put('favorites/{productId}', 'modifyFavorites');

        Route::get('analytics', 'getAnalytics');
        Route::put('analytics/{productId}', 'modifyAnalytics');

        Route::get('history', 'getHistory');
        Route::put('history/{productSlug?}', 'modifyHistory');
    });
    Route::controller(UserCartController::class)->group(function() 
    {
        Route::get   ('cart', 'getStoresSummary');
        Route::get   ('cart/store/{storeId}', 'getStoreItems');
        Route::get   ('cart/items', 'getCartSummary');

        Route::post  ('cart/{productId}', 'addProduct');
        Route::put   ('cart/store/{storeId}/product/{productId}/{type}/{isFactor?}', 'updateItemCount');
        Route::delete('cart/store/{storeId}', 'deleteStoreItems');

        Route::post  ('invoice/{storeId}', 'createInvoice');
    });
    Route::controller(UserInvoiceController::class)->group(function() 
    {
        Route::get('invoices', 'getList');

        Route::put('invoices/{invoiceId}/state', 'changeState');
    });
});
Route::group(
    [ 
        'middleware' => ['user', 'admin', 'valid-query'] , 
        'prefix'     => 'admin' 
    ], function () 
{
    Route::get('counter', [UserController::class,'adminDataCount']);
    
    Route::controller(AdminImageSliderController::class)->group(function() 
    {
        Route::get ('slider', 'getList');

        Route::post('slider', 'createOrUpdateSlide');
        Route::post('slider/{slideId}/update', 'createOrUpdateSlide');

        Route::put ('slider/{slideId}/state', 'changeSlideState');
    });
    Route::controller(AdminNotificationController::class)->group(function() 
    {
        Route::get('notifications', 'getList');
        
        Route::put('notifications', 'deleteNotifications');
    });
    Route::controller(AdminCategoryController::class)->group(function() 
    {
        Route::get ('categories', 'getList');
        Route::get ('categories/list/{categoryId?}', 'getSimpleList');

        Route::post('categories', 'createOrUpdateCategory');
        Route::post('categories/{categoryId}/update', 'createOrUpdateCategory');

        Route::put ('categories/{categoryId}/state', 'changeCategoryState');
    });
    Route::controller(AdminBrandController::class)->group(function() 
    {
        Route::get ('brands', 'getList');

        Route::post('brands', 'createOrUpdateBrand');
        Route::post('brands/{brandId}/update', 'createOrUpdateBrand');

        Route::put ('brands/{brandId}/state', 'changeBrandState');
    });
    Route::controller(AdminProductController::class)->group(function() 
    {
        Route::get   ('products', 'getList');

        Route::post  ('products', 'createOrUpdateProduct');
        Route::post  ('products/{productId}/update', 'createOrUpdateProduct');

        Route::put   ('products/{productId}/state', 'changeProductState');
        Route::delete('products/images/{imageId}', 'deleteProductImage');
    });
    Route::controller(AdminStoreController::class)->group(function() 
    {
        Route::get ('stores', 'getList');

        Route::post('stores', 'createOrUpdateStore');
        Route::post('stores/{storeId}/update', 'createOrUpdateStore');

        Route::put ('stores/{storeId}/state', 'changeStoreState');
        Route::put ('stores/{storeId}/confirm', 'confirmStore');
    });
    Route::controller(AdminUserController::class)->group(function() 
    {
        Route::get ('users', 'getList');

        Route::post('users', 'createOrUpdateUser');
        Route::post('users/{userId}/update', 'createOrUpdateUser');

        Route::put ('users/{userId}/state', 'changeUserState');
    });
});

Route::get('/store', [StoreController::class,'info'])->middleware(['user', 'store']);

Route::group(
    [ 
        'middleware' => ['user', 'store', 'store-confirmed', 'valid-query'] 
    ], function () 
{
    Route::get('products/bases', [AdminProductController::class,'getList']);
    Route::get('products/categories', [AdminCategoryController::class,'getList']);
    Route::get('products/brands', [AdminBrandController::class,'getList']);

    Route::group([ 'prefix' => 'store' ], function () 
    {
        Route::post('setting', [StoreController::class,'changeSetting']);

        Route::get('counter', [UserController::class,'storeDataCount']);

        Route::controller(StoreProductController::class)->group(function() 
        {
            Route::get ('products', 'getList');

            Route::post('products', 'createOrUpdateProduct');
            Route::post('products/{productId}/update', 'createOrUpdateProduct');
            
            Route::put ('products/{productId}/state', 'changeProductState');
        });
        Route::controller(StoreInvoiceController::class)->group(function() 
        {
            Route::get('invoices', 'getList');
            Route::put('invoices/{invoiceId}/state', 'changeState');
        });
    });
});
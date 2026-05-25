<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\front\AccountController;
use App\Http\Controllers\admin\AuthController;
use App\Http\Controllers\admin\CategoryController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\ProductController;
use App\Http\Controllers\admin\SizeController;
use App\Http\Controllers\admin\TempImageController;

use App\Http\Controllers\front\ProductController as FrontProductController;
use App\Http\Controllers\front\WishlistController;
use App\Http\Controllers\front\OrderController;

//Frontend routes
Route::get('get-latest-products', [FrontProductController::class, 'latestProducts']);
Route::get('get-featured-products', [FrontProductController::class, 'featuredProducts']);
Route::get('get-categories', [FrontProductController::class, 'getCategories']);
Route::get('get-brands', [FrontProductController::class, 'getBrands']);
Route::get('get-products', [FrontProductController::class, 'getProducts']);
Route::get('get-product-details/{id}', [FrontProductController::class, 'getProductDetails']);
//Frontend account routes
Route::post('/register', [AccountController::class, 'register']);
Route::post('/login', [AccountController::class, 'authenticate']);//->middleware('throttle:5,1');
Route::post('/logout', [AccountController::class, 'logout'])->middleware('auth:sanctum');

Route::group(['middleware' => ['auth:sanctum', 'checkCustomerRole']], function () {
    Route::post('/order', [OrderController::class, 'store']);
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist', [WishlistController::class, 'destroy']);
});

//Admin routes
Route::post('/login', [AccountController::class, 'authenticate']);//->middleware('throttle:5,1');
Route::post('/admin/login', [AuthController::class, 'authenticate']);//->middleware('throttle:5,1');
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['middleware' => ['auth:sanctum', 'checkAdminRole'], 'prefix' => 'admin'], function () {
    //Route::put('/profile', [ProfileController::class, 'updateProfile']);    // ✅ Form text fields profile parameters updates    
    //Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);    // ✅ Binary Avatar file stream uploads    
    //Route::put('/profile/password', [ProfileController::class, 'changePassword']); // ✅ Password access modifications

    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/brands', BrandController::class);
    Route::apiResource('/products', ProductController::class);

    Route::get('/sizes', [SizeController::class, 'index']);
    Route::post('/temp-images', [TempImageController::class, 'store']);    
    Route::post('/save-product-images', [ProductController::class, 'saveProductImage']);    
    Route::delete('/delete-product-image/{id}', [ProductController::class, 'deleteProductImage']);    
    Route::get('/change-product-default-image', [ProductController::class, 'updateDefaultImage']);     

});
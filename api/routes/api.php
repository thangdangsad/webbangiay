<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer auth (guard: customer, table: quanli_user)
|--------------------------------------------------------------------------
*/
Route::post('/register', [CustomerAuthController::class, 'register']);
Route::post('/login', [CustomerAuthController::class, 'login']);

Route::middleware('auth:customer')->group(function () {
    Route::post('/logout', [CustomerAuthController::class, 'logout']);
    Route::get('/me', [CustomerAuthController::class, 'me']);
});

/*
|--------------------------------------------------------------------------
| Admin/staff auth (guard: admin, table: quanly_taikhoan)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Public catalog (products + categories)
|--------------------------------------------------------------------------
*/
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/{product}/similar', [ProductController::class, 'similar']);
Route::get('/categories', [CategoryController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Admin catalog management (guard: admin, any role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:admin')->prefix('admin')->group(function () {
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::match(['put', 'patch'], '/products/{product}', [AdminProductController::class, 'update']);
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);

    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::match(['put', 'patch'], '/categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Cart (customer or guest, resolved via App\Services\CartResolver)
|--------------------------------------------------------------------------
*/
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/items', [CartController::class, 'store']);
Route::match(['put', 'patch'], '/cart/items/{cart}', [CartController::class, 'update']);
Route::delete('/cart/items/{cart}', [CartController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Checkout (COD only — see App\Services\OrderService)
|--------------------------------------------------------------------------
*/
Route::post('/checkout', [CheckoutController::class, 'store']);

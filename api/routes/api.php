<?php

use App\Modules\Auth\Http\Controllers\CustomerAuthController;
use App\Modules\Cart\Http\Controllers\CartController;
use App\Modules\Catalog\Http\Controllers\StoreCatalogController;
use App\Modules\Cms\Http\Controllers\PublicController;
use App\Modules\Order\Http\Controllers\StoreOrderController;
use App\Modules\Payment\Http\Controllers\MomoController;
use App\Modules\User\Http\Controllers\MeController;
use Illuminate\Support\Facades\Route;

/* ---------------- Storefront (guard: customer) ---------------- */
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:10,1,auth')->group(function () {
        Route::post('register', [CustomerAuthController::class, 'register']);
        Route::post('login', [CustomerAuthController::class, 'login']);
        Route::post('google', [CustomerAuthController::class, 'google']);
        Route::post('forgot-password', [CustomerAuthController::class, 'forgotPassword']);
        Route::post('reset-password', [CustomerAuthController::class, 'resetPassword']);
    });
    Route::post('verify-email', [CustomerAuthController::class, 'verifyEmail']);
    Route::middleware('origin')->group(function () {
        Route::post('refresh', [CustomerAuthController::class, 'refresh']);
        Route::post('logout', [CustomerAuthController::class, 'logout']);
    });
    Route::post('logout-all', [CustomerAuthController::class, 'logoutAll'])->middleware('auth:customer');
});

Route::middleware('auth:customer')->prefix('me')->group(function () {
    Route::get('/', [CustomerAuthController::class, 'me']);
    Route::patch('/', [CustomerAuthController::class, 'updateMe']);
    Route::put('password', [CustomerAuthController::class, 'changePassword']);
    Route::get('sessions', [CustomerAuthController::class, 'sessions']);
    Route::delete('sessions/{sid}', [CustomerAuthController::class, 'destroySession']);
    Route::get('addresses', [MeController::class, 'addresses']);
    Route::post('addresses', [MeController::class, 'storeAddress']);
    Route::put('addresses/{id}', [MeController::class, 'updateAddress']);
    Route::delete('addresses/{id}', [MeController::class, 'deleteAddress']);
    Route::get('wishlist', [MeController::class, 'wishlist']);
    Route::put('wishlist/{productId}', [MeController::class, 'addWishlist']);
    Route::delete('wishlist/{productId}', [MeController::class, 'removeWishlist']);
    Route::get('orders', [StoreOrderController::class, 'myOrders']);
    Route::get('orders/{code}', [StoreOrderController::class, 'myOrder']);
    Route::post('orders/{code}/cancel', [StoreOrderController::class, 'cancel']);
    Route::post('uploads', [StoreOrderController::class, 'uploadReturnImage'])->middleware('throttle:20,1,upload');
    Route::post('orders/{code}/returns', [StoreOrderController::class, 'requestReturn']);
});

Route::get('home', [StoreCatalogController::class, 'home']);
Route::get('categories', [StoreCatalogController::class, 'categories']);
Route::get('brands', [StoreCatalogController::class, 'brands']);
Route::get('attributes', [StoreCatalogController::class, 'attributes']);
Route::get('products', [StoreCatalogController::class, 'index']);
Route::get('products/{slug}', [StoreCatalogController::class, 'show']);
Route::get('products/{slug}/reviews', [StoreCatalogController::class, 'reviews']);
Route::post('products/{slug}/reviews', [StoreCatalogController::class, 'storeReview']);

Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::post('items', [CartController::class, 'add']);
    Route::patch('items/{variantId}', [CartController::class, 'update']);
    Route::delete('items/{variantId}', [CartController::class, 'remove']);
    Route::post('coupon', [CartController::class, 'applyCoupon']);
    Route::delete('coupon', [CartController::class, 'removeCoupon']);
});

Route::post('shipping/quote', [StoreOrderController::class, 'quote']);
Route::get('payment-methods', [StoreOrderController::class, 'paymentMethods']);
Route::post('orders', [StoreOrderController::class, 'store'])->middleware('throttle:30,1,orders');
Route::get('orders/track', [StoreOrderController::class, 'track'])->middleware('throttle:20,1,track');
Route::get('orders/{code}', [StoreOrderController::class, 'publicOrder']);
Route::post('orders/{code}/switch-cod', [StoreOrderController::class, 'switchCod']);
Route::get('orders/{code}/payment-status', [StoreOrderController::class, 'paymentStatus']);

Route::prefix('payments/momo')->group(function () {
    Route::post('create', [MomoController::class, 'create']);
    Route::post('ipn', [MomoController::class, 'ipn']);
    Route::get('return', [MomoController::class, 'back']);
    Route::get('fake/{momoOrderId}', [MomoController::class, 'fakePage']);
});

Route::get('pages', [PublicController::class, 'pages']);
Route::get('pages/{slug}', [PublicController::class, 'page']);
Route::get('store-info', [PublicController::class, 'storeInfo']);
Route::post('contact', [PublicController::class, 'contact'])->middleware('throttle:5,1,contact');
Route::post('newsletter', [PublicController::class, 'newsletter'])->middleware('throttle:5,1,contact');
Route::get('locations/provinces/{province?}/{district?}', [PublicController::class, 'locations']);

require __DIR__.'/admin.php';

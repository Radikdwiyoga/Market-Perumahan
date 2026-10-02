<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PickupController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SellerOrderController;
use App\Http\Controllers\Api\SellerPaymentController;
use App\Http\Controllers\Api\SellerProductController;
use App\Http\Controllers\Api\ShippingController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api-auth')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');
});

Route::middleware('throttle:api-public')->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('api.products.show');
    Route::get('/categories', [ProductController::class, 'categories'])->name('api.categories.index');
    Route::get('/stores', [ProductController::class, 'stores'])->name('api.stores.index');
    Route::get('/stores/{store}', [ProductController::class, 'store'])->name('api.stores.show');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

    Route::get('/cart', [CartController::class, 'index'])->name('api.cart.index');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('api.favorites.index');
    Route::get('/orders', [OrderController::class, 'index'])->name('api.orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('api.orders.show');
    Route::get('/orders/{order}/shipping', [ShippingController::class, 'show'])->name('api.orders.shipping.show');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('api.payments.show');
    Route::get('/seller/orders', [SellerOrderController::class, 'index'])->name('api.seller.orders.index');
    Route::get('/seller/orders/{sellerOrder}', [SellerOrderController::class, 'show'])->name('api.seller.orders.show');
    Route::get('/seller/products', [SellerProductController::class, 'index'])->name('api.seller.products.index');
    Route::get('/seller/products/{product}', [SellerProductController::class, 'show'])->name('api.seller.products.show');
    Route::get('/seller/payment-setting', [SellerPaymentController::class, 'show'])->name('api.seller.payment-setting.show');
    Route::get('/seller-orders/{sellerOrder}/pickup', [PickupController::class, 'info'])->name('api.seller-orders.pickup.show');

    Route::middleware('throttle:api-write')->group(function () {
        Route::post('/cart/items', [CartController::class, 'store'])->name('api.cart.items.store');
        Route::put('/cart/items/{cartItem}', [CartController::class, 'update'])->name('api.cart.items.update');
        Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('api.cart.items.destroy');
        Route::post('/favorites', [FavoriteController::class, 'store'])->name('api.favorites.store');
        Route::delete('/favorites/{product}', [FavoriteController::class, 'destroy'])->name('api.favorites.destroy');
        Route::post('/orders', [OrderController::class, 'store'])->name('api.orders.store');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('api.orders.cancel');
        Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])->name('api.orders.complete');
        Route::post('/orders/{order}/payment', [PaymentController::class, 'createForOrder'])->name('api.orders.payment.create');
        Route::post('/orders/{order}/shipping', [ShippingController::class, 'update'])->name('api.orders.shipping.update');
        Route::post('/payments/{payment}/proof', [PaymentController::class, 'uploadProof'])->name('api.payments.proof.store');
        Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('api.payments.verify');
        Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('api.payments.reject');

        Route::put('/seller/payment-setting', [SellerPaymentController::class, 'update'])->name('api.seller.payment-setting.update');
        Route::post('/seller/payment-setting/qris', [SellerPaymentController::class, 'storeQris'])->name('api.seller.payment-setting.qris.store');
        Route::delete('/seller/payment-setting/qris', [SellerPaymentController::class, 'destroyQris'])->name('api.seller.payment-setting.qris.destroy');

        Route::post('/seller/products', [SellerProductController::class, 'store'])->name('api.seller.products.store');
        Route::put('/seller/products/{product}', [SellerProductController::class, 'update'])->name('api.seller.products.update');
        Route::delete('/seller/products/{product}', [SellerProductController::class, 'destroy'])->name('api.seller.products.destroy');

        Route::post('/seller/orders/{sellerOrder}/accept', [SellerOrderController::class, 'accept'])->name('api.seller.orders.accept');
        Route::post('/seller/orders/{sellerOrder}/process', [SellerOrderController::class, 'process'])->name('api.seller.orders.process');
        Route::post('/seller/orders/{sellerOrder}/ready', [SellerOrderController::class, 'ready'])->name('api.seller.orders.ready');
        Route::post('/seller/orders/{sellerOrder}/deliver', [SellerOrderController::class, 'outForDelivery'])->name('api.seller.orders.deliver');
        Route::post('/seller/orders/{sellerOrder}/complete', [SellerOrderController::class, 'complete'])->name('api.seller.orders.complete');

        Route::post('/seller-orders/{sellerOrder}/ready', [SellerOrderController::class, 'ready'])->name('api.seller-orders.ready');
        Route::post('/seller-orders/{sellerOrder}/out-for-delivery', [SellerOrderController::class, 'outForDelivery'])->name('api.seller-orders.out_for_delivery');
        Route::post('/seller-orders/{sellerOrder}/delivered', [SellerOrderController::class, 'delivered'])->name('api.seller-orders.delivered');
        Route::post('/seller-orders/{sellerOrder}/pickup/verify', [PickupController::class, 'verify'])->name('api.seller-orders.pickup.verify');
    });
});

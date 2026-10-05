<?php

use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminComplaintController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RealtimeNotificationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SellerApplicationController;
use App\Http\Controllers\SellerDashboardController;
use App\Http\Controllers\SellerOrderController;
use App\Http\Controllers\SellerPaymentController;
use App\Http\Controllers\SellerProductController;
use App\Http\Controllers\SellerProductDescriptionAiController;
use App\Http\Controllers\SellerReportController;
use App\Http\Controllers\SellerShippingController;
use App\Http\Controllers\SellerStoreController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/stores/{store}', [StoreController::class, 'show'])->name('stores.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});

// `active` arresting akun yang dinonaktifkan pengelola; harus setelah `auth`
// supaya `$request->user()` sudah terisi.
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::put('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{product}', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{product}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{sellerOrder}/confirm', [OrderController::class, 'confirmReceived'])->name('orders.confirm');
    Route::get('/orders/{order}/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('/orders/{order}/complaints', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::post('/orders/{order}/reviews', [ReviewController::class, 'store'])->name('orders.reviews.store');
    Route::post('/payments/{payment}/proof', [PaymentProofController::class, 'store'])->name('payments.proof.store');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/stream', [RealtimeNotificationController::class, 'stream'])->name('notifications.stream')->middleware('throttle:realtime');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'store'])->name('chat.messages.store');
    Route::post('/chat/start', [ChatController::class, 'start'])->name('chat.start');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [AdminReportController::class, 'export'])->name('reports.export');
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/complaints', [AdminComplaintController::class, 'index'])->name('complaints.index');
        Route::patch('/complaints/{complaint}/status', [AdminComplaintController::class, 'updateStatus'])->name('complaints.status');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::patch('/orders/payments/{payment}/verify', [AdminOrderController::class, 'verifyPayment'])->name('orders.payments.verify');
        Route::patch('/orders/payments/{payment}/reject', [AdminOrderController::class, 'rejectPayment'])->name('orders.payments.reject');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');
        Route::patch('/users/{user}/verify-seller', [AdminUserController::class, 'verifySeller'])->name('users.verify-seller');
        Route::patch('/users/{user}/reject-seller', [AdminUserController::class, 'rejectSeller'])->name('users.reject-seller');
        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/toggle', [AdminCategoryController::class, 'toggle'])->name('categories.toggle');
    });

    Route::prefix('seller')->name('seller.')->group(function () {
        Route::get('/', [SellerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/apply', [SellerApplicationController::class, 'create'])->name('application.create');
        Route::post('/apply', [SellerApplicationController::class, 'store'])->name('application.store');
        Route::get('/reports', [SellerReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [SellerReportController::class, 'export'])->name('reports.export');
        Route::get('/products', [SellerProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [SellerProductController::class, 'create'])->name('products.create');
        Route::post('/products', [SellerProductController::class, 'store'])->name('products.store');
        Route::post('/products/ai-description', [SellerProductDescriptionAiController::class, 'store'])->middleware('throttle:ai-description')->name('products.ai-description');
        Route::get('/products/{product}/edit', [SellerProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [SellerProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [SellerProductController::class, 'destroy'])->name('products.destroy');
        Route::get('/store/edit', [SellerStoreController::class, 'edit'])->name('store.edit');
        Route::put('/store', [SellerStoreController::class, 'update'])->name('store.update');
        Route::get('/payment/edit', [SellerPaymentController::class, 'edit'])->name('payment.edit');
        Route::put('/payment', [SellerPaymentController::class, 'update'])->name('payment.update');
        Route::delete('/payment/qris', [SellerPaymentController::class, 'destroyQris'])->name('payment.qris.destroy');
        Route::get('/shipping/edit', [SellerShippingController::class, 'edit'])->name('shipping.edit');
        Route::put('/shipping', [SellerShippingController::class, 'update'])->name('shipping.update');
        Route::get('/orders', [SellerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{sellerOrder}', [SellerOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/payments/{payment}/verify', [SellerOrderController::class, 'verifyPayment'])->name('orders.payments.verify');
        Route::patch('/orders/{sellerOrder}/shipping', [SellerOrderController::class, 'updateShipping'])->name('orders.shipping.update');
        Route::post('/orders/{sellerOrder}/pickup', [SellerOrderController::class, 'verifyPickup'])->name('orders.pickup');
    });
});

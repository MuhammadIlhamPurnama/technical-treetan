<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\OrderHistoryController;
use App\Http\Controllers\Api\XenditWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

// Product routes (Public access for viewing products)
Route::prefix('products')->group(function () {
    // Public routes - anyone can view products
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/active', [ProductController::class, 'active']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/{product}', [ProductController::class, 'show']);
    
    // Protected routes - admin only (for demo, we'll use auth:sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{product}', [ProductController::class, 'update']);
        Route::delete('/{product}', [ProductController::class, 'destroy']);
    });
});

// Checkout routes (Protected - authenticated users only)
Route::middleware('auth:sanctum')->prefix('checkout')->group(function () {
    Route::post('/calculate', [CheckoutController::class, 'calculateTotals']);
    Route::post('/', [CheckoutController::class, 'checkout']);
});

// Order routes (Protected - authenticated users only)
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    Route::get('/', [CheckoutController::class, 'orders']);
    Route::get('/{order}', [CheckoutController::class, 'orderDetails']);
    Route::post('/{order}/cancel', [CheckoutController::class, 'cancelOrder']);
});

// Payment API routes (Protected - authenticated users + API key)
Route::middleware(['auth:sanctum', 'api.key'])->prefix('payments')->group(function () {
    Route::post('/orders/{order}', [PaymentController::class, 'createPayment']);
    Route::get('/{payment}', [PaymentController::class, 'getPayment']);
    Route::get('/', [PaymentController::class, 'getUserPayments']);
    Route::post('/{payment}/cancel', [PaymentController::class, 'cancelPayment']);
});

// Order History API routes (Protected - authenticated users + API key)
Route::middleware(['auth:sanctum', 'api.key'])->prefix('order-history')->group(function () {
    Route::get('/', [OrderHistoryController::class, 'getOrderHistory']);
    Route::get('/summary', [OrderHistoryController::class, 'getOrderSummary']);
    Route::get('/status/{status}', [OrderHistoryController::class, 'getOrdersByStatus']);
    Route::get('/recent', [OrderHistoryController::class, 'getRecentOrders']);
    Route::get('/track/{order}', [OrderHistoryController::class, 'trackOrder']);
});

// Xendit Webhook routes (Public - with signature verification)
Route::prefix('webhooks/xendit')->group(function () {
    Route::post('/invoice', [XenditWebhookController::class, 'handleInvoiceCallback']);
    Route::post('/payment', [XenditWebhookController::class, 'handlePaymentCallback']);
    Route::post('/virtual-account', [XenditWebhookController::class, 'handlePaymentCallback']);
    Route::post('/ewallet', [XenditWebhookController::class, 'handlePaymentCallback']);
    Route::post('/credit-card', [XenditWebhookController::class, 'handlePaymentCallback']);
});
<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 1. Rute Autentikasi
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

// 2. Rute Publik Storefront E-Commerce
Route::prefix('storefront')->group(function () {
    Route::get('/products', [StorefrontController::class, 'getProducts']);
    Route::get('/recommendations', [StorefrontController::class, 'getArimaRecommendations']);
    
    // Checkout web WAJIB login (Resolusi #1)
    Route::middleware('auth:sanctum')->post('/checkout', [StorefrontController::class, 'processWebCheckout']);
});

// 3. Rute Customer Portal (Proteksi: Pengguna terautentikasi)
Route::prefix('customer')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/orders', [CustomerPortalController::class, 'getOrderHistory']);
    Route::get('/orders/{id}', [CustomerPortalController::class, 'getOrderDetail']);
    Route::post('/profile/update', [CustomerPortalController::class, 'updateProfile']);
});

// 4. Rute POS Kasir (Proteksi: Kasir terautentikasi)
Route::prefix('pos')->middleware(['auth:sanctum', 'role:kasir'])->group(function () {
    Route::get('/products/search', [PosController::class, 'searchProducts']);
    Route::post('/checkout', [PosController::class, 'processCheckout']);
    Route::get('/queue', [PosController::class, 'getOnlineQueue']);
    Route::post('/queue/{id}/validate', [PosController::class, 'validateQueue']);
    Route::post('/cart/validate-prices', [PosController::class, 'validateHeldCart']);
});

// 5. Rute Admin Panel (Proteksi: Admin terautentikasi)
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/dashboard/metrics', [App\Http\Controllers\Admin\AdminDashboardController::class, 'getMetrics']);
    Route::post('/predict-arima', [App\Http\Controllers\Admin\ArimaController::class, 'runPrediction']);
    Route::post('/predict-arima-global', [App\Http\Controllers\Admin\ArimaController::class, 'runGlobalPrediction']);
    Route::get('/arima-logs', [App\Http\Controllers\Admin\ArimaController::class, 'getLogs']);
    Route::get('/arima/config', [App\Http\Controllers\Admin\ArimaController::class, 'getConfig']);
    Route::post('/arima/config', [App\Http\Controllers\Admin\ArimaController::class, 'saveConfig']);
    Route::get('/orders/history', [App\Http\Controllers\Admin\AdminOrderController::class, 'getHistory']);
    Route::get('/sales/export', [App\Http\Controllers\Admin\AdminReportController::class, 'exportCSV']);

    Route::apiResource('products', App\Http\Controllers\Admin\AdminProductController::class);
    Route::apiResource('customers', App\Http\Controllers\Admin\AdminCustomerController::class)->only(['index', 'show']);
    Route::apiResource('staff', App\Http\Controllers\Admin\AdminStaffController::class);
});

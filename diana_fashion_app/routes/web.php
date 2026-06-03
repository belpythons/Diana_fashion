<?php

use Illuminate\Support\Facades\Route;

// POS SPA Catch-all (Proteksi: kasir terautentikasi)
Route::get('/pos/{any?}', function () {
    return view('pos.index');
})->middleware(['auth:sanctum', 'role:kasir'])->where('any', '.*');

// Admin SPA Catch-all (Proteksi: admin terautentikasi)
Route::get('/admin/{any?}', function () {
    return view('admin.app');
})->middleware(['auth:sanctum', 'role:admin'])->where('any', '.*');

// Customer Portal SPA Catch-all (Proteksi: pelanggan terautentikasi)
Route::get('/customer/{any?}', function () {
    return view('storefront.index');
})->middleware(['auth:sanctum'])->where('any', '.*');

// E-Commerce Storefront SPA Catch-all (Publik)
Route::get('/{any?}', function () {
    return view('storefront.index');
})->where('any', '^(?!api|sanctum).*$');

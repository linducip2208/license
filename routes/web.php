<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\MiscController;
use App\Http\Controllers\OrderAdminController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Checkout publik (tanpa login) — dipakai tombol beli di website statis.
// Checkout publik — TAPI hanya lewat proxy resmi (header X-Proxy-Secret).
Route::get('/buy/{slug}', [CheckoutController::class, 'show'])->middleware('proxied')->name('checkout.show');
Route::post('/buy/{slug}', [CheckoutController::class, 'store'])->middleware(['proxied', 'throttle:10,1'])->name('checkout.store');
Route::get('/order/{ref}', [CheckoutController::class, 'order'])->middleware('proxied')->name('checkout.order');
Route::get('/order/{ref}/status', [CheckoutController::class, 'status'])->middleware('proxied')->name('checkout.status');
Route::post('/order/{ref}/refresh', [CheckoutController::class, 'refresh'])->middleware(['proxied', 'throttle:6,1'])->name('checkout.refresh');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/licenses/export/csv', [LicenseController::class, 'exportCsv'])->name('licenses.csv');
    Route::get('/licenses/export/excel', [LicenseController::class, 'exportExcel'])->name('licenses.excel');
    Route::post('/licenses/bulk', [LicenseController::class, 'bulk'])->name('licenses.bulk');
    Route::resource('licenses', LicenseController::class);
    Route::post('/licenses/{license}/suspend', [LicenseController::class, 'suspend'])->name('licenses.suspend');
    Route::post('/licenses/{license}/activate', [LicenseController::class, 'activate'])->name('licenses.activate');
    Route::post('/licenses/{license}/regenerate', [LicenseController::class, 'regenerate'])->name('licenses.regenerate');
    Route::post('/licenses/{license}/extend', [LicenseController::class, 'extend'])->name('licenses.extend');
    Route::post('/licenses/{license}/activations/{activation}/revoke', [LicenseController::class, 'revokeActivation'])->name('licenses.activations.revoke');

    Route::get('/api-tokens', [ApiTokenController::class, 'index'])->name('tokens.index');
    Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('tokens.store');
    Route::delete('/api-tokens/{id}', [ApiTokenController::class, 'destroy'])->name('tokens.destroy');

    Route::get('/restore', [MiscController::class, 'restore'])->name('restore');
    Route::get('/downloads', [MiscController::class, 'downloads'])->name('downloads');
    Route::get('/logs', [MiscController::class, 'logs'])->name('logs');
    Route::get('/profile', [MiscController::class, 'profile'])->name('profile');
    Route::post('/profile', [MiscController::class, 'profileUpdate'])->name('profile.update');
    Route::get('/support', [MiscController::class, 'support'])->name('support');
});

// Admin saja: verifikasi order manual (QRIS statis) → terbitkan lisensi.
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/orders', [OrderAdminController::class, 'index'])->name('orders.index');
    Route::post('/orders/{order}/fulfill', [OrderAdminController::class, 'fulfill'])->name('orders.fulfill');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test', [SettingController::class, 'test'])->name('settings.test');
});

<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\MiscController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

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

<?php

use App\Http\Controllers\Api\VerifyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/v1/licenses/verify', [VerifyController::class, 'verify'])
    ->middleware('throttle:60,1');

Route::post('/v1/licenses/release', [VerifyController::class, 'release'])
    ->middleware(['auth:sanctum', 'throttle:60,1']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

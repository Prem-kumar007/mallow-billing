<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\UsageController;
use Illuminate\Support\Facades\Route;

Route::get('/merchants/{merchantId}/dashboard', [DashboardController::class, 'index'])
    ->middleware('merchant.api-key')
    ->name('api.merchant.dashboard');

Route::post('/usage', [UsageController::class, 'store'])
    ->middleware(['merchant.api-key', 'throttle:usage']);

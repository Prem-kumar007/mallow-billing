<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MerchantInvoiceController;
use App\Http\Controllers\MerchantLoginController;
use App\Http\Controllers\MerchantManagementController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    if (Schema::hasTable('merchants')) {
        $merchant = DB::table('merchants')->orderBy('id')->first(['id']);

        if ($merchant) {
            return redirect()->route('merchant.dashboard', [
                'merchantId' => $merchant->id,
            ]);
        }
    }

    return view('welcome');
});

Route::get('/merchants/{merchantId}/dashboard', [DashboardController::class, 'index'])
    ->middleware('merchant.dashboard')
    ->name('merchant.dashboard');

Route::get('/merchants/{merchantId}/login', [MerchantLoginController::class, 'show'])
    ->name('merchant.login');
Route::post('/merchants/{merchantId}/login', [MerchantLoginController::class, 'store'])
    ->middleware('throttle:merchant-login')
    ->name('merchant.login.store');
Route::get('/merchants/{merchantId}/logout', [MerchantLoginController::class, 'confirmLogout'])
    ->middleware('merchant.dashboard')
    ->name('merchant.logout.confirm');
Route::post('/merchants/{merchantId}/logout', [MerchantLoginController::class, 'destroy'])
    ->middleware('merchant.dashboard')
    ->name('merchant.logout');

Route::prefix('/merchants/{merchantId}')
    ->name('merchant.')
    ->middleware('merchant.dashboard')
    ->group(function () {
        Route::get('/invoices/{invoiceId}', [MerchantInvoiceController::class, 'show'])
            ->name('invoices.show');
        Route::post('/invoices/generate-due', [MerchantInvoiceController::class, 'generateDue'])
            ->name('invoices.generate-due');

        Route::get('/manage', [MerchantManagementController::class, 'index'])
            ->name('management');

        Route::post('/customers', [MerchantManagementController::class, 'storeCustomer'])
            ->name('customers.store');
        Route::put('/customers/{customerId}', [MerchantManagementController::class, 'updateCustomer'])
            ->name('customers.update');
        Route::delete('/customers/{customerId}', [MerchantManagementController::class, 'destroyCustomer'])
            ->name('customers.destroy');

        Route::post('/plans', [MerchantManagementController::class, 'storePlan'])
            ->name('plans.store');
        Route::put('/plans/{planId}', [MerchantManagementController::class, 'updatePlan'])
            ->name('plans.update');

        Route::post('/subscriptions', [MerchantManagementController::class, 'storeSubscription'])
            ->name('subscriptions.store');
        Route::put('/subscriptions/{subscriptionId}/plan', [MerchantManagementController::class, 'changeSubscriptionPlan'])
            ->name('subscriptions.change-plan');
        Route::post('/subscriptions/{subscriptionId}/cancel', [MerchantManagementController::class, 'cancelSubscription'])
            ->name('subscriptions.cancel');
    });

<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\GoatController;
use App\Http\Controllers\Api\V1\OptionController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\ScanController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Kamsya API v1
|--------------------------------------------------------------------------
|
| The JSON API mirrors the Blade application and delegates all business
| rules to the existing services, policies and gates. Every authenticated
| request uses a per-device Sanctum token.
|
*/

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('api.v1.auth.login');

    Route::middleware(['auth:sanctum', 'api.active'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::put('profile', [AuthController::class, 'updateProfile'])->name('api.v1.auth.profile');
        Route::put('password', [AuthController::class, 'updatePassword'])->name('api.v1.auth.password');
    });
});

Route::middleware(['auth:sanctum', 'api.active'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('api.v1.dashboard');
    Route::get('options', OptionController::class)->name('api.v1.options');

    // Inventaris & scan
    Route::get('goats', [GoatController::class, 'index'])->name('api.v1.goats.index');
    Route::post('goats', [GoatController::class, 'store'])->name('api.v1.goats.store');
    Route::get('goats/barcode/{barcode}', [GoatController::class, 'barcode'])->name('api.v1.goats.barcode');
    Route::get('goats/{goat}', [GoatController::class, 'show'])->name('api.v1.goats.show');
    Route::put('goats/{goat}', [GoatController::class, 'update'])->name('api.v1.goats.update');
    Route::delete('goats/{goat}', [GoatController::class, 'destroy'])->name('api.v1.goats.destroy');

    Route::get('scan', [ScanController::class, 'show'])->name('api.v1.scan.show');
    Route::get('scan/history', [ScanController::class, 'history'])->name('api.v1.scan.history');

    // Master data
    Route::get('suppliers', [SupplierController::class, 'index'])->name('api.v1.suppliers.index');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('api.v1.suppliers.store');
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('api.v1.suppliers.show');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('api.v1.suppliers.update');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('api.v1.suppliers.destroy');

    Route::get('customers', [CustomerController::class, 'index'])->name('api.v1.customers.index');
    Route::post('customers', [CustomerController::class, 'store'])->name('api.v1.customers.store');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('api.v1.customers.show');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('api.v1.customers.update');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('api.v1.customers.destroy');

    // Transaksi
    Route::get('purchases', [PurchaseController::class, 'index'])->name('api.v1.purchases.index');
    Route::post('purchases', [PurchaseController::class, 'store'])->name('api.v1.purchases.store');
    Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->name('api.v1.purchases.show');
    Route::post('purchases/{purchase}/void', [PurchaseController::class, 'void'])->name('api.v1.purchases.void');

    Route::get('sales', [SaleController::class, 'index'])->name('api.v1.sales.index');
    Route::post('sales', [SaleController::class, 'store'])->name('api.v1.sales.store');
    Route::get('sales/{sale}', [SaleController::class, 'show'])->name('api.v1.sales.show');
    Route::post('sales/{sale}/void', [SaleController::class, 'void'])->name('api.v1.sales.void');

    Route::get('payments', [PaymentController::class, 'index'])->name('api.v1.payments.index');
    Route::post('payments', [PaymentController::class, 'store'])->name('api.v1.payments.store');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('api.v1.payments.show');
    Route::post('payments/{payment}/void', [PaymentController::class, 'void'])->name('api.v1.payments.void');

    Route::get('expenses', [ExpenseController::class, 'index'])->name('api.v1.expenses.index');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('api.v1.expenses.store');
    Route::get('expenses/{expense}', [ExpenseController::class, 'show'])->name('api.v1.expenses.show');
    Route::post('expenses/{expense}/void', [ExpenseController::class, 'void'])->name('api.v1.expenses.void');

    // Laporan
    Route::get('reports', [ReportController::class, 'index'])->name('api.v1.reports.index');
    Route::get('reports/summary', [ReportController::class, 'summary'])->name('api.v1.reports.summary');

    // Sistem (Owner)
    Route::get('users', [UserController::class, 'index'])->name('api.v1.users.index');
    Route::post('users', [UserController::class, 'store'])->name('api.v1.users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('api.v1.users.show');
    Route::put('users/{user}', [UserController::class, 'update'])->name('api.v1.users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('api.v1.users.destroy');

    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('api.v1.activity.index');

    Route::get('settings', [SettingController::class, 'show'])->name('api.v1.settings.show');
    Route::put('settings', [SettingController::class, 'update'])->name('api.v1.settings.update');
    Route::get('settings/export', [SettingController::class, 'export'])->name('api.v1.settings.export');
    Route::post('settings/import', [SettingController::class, 'import'])->name('api.v1.settings.import');
});

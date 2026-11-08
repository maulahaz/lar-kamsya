<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GoatController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware(['auth', 'active'])
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Inventaris
    Route::get('/kambing', [GoatController::class, 'index'])->name('goats.index');
    Route::get('/kambing/baru', [GoatController::class, 'create'])->name('goats.create');
    Route::post('/kambing', [GoatController::class, 'store'])->name('goats.store');
    Route::get('/kambing/{goat}', [GoatController::class, 'show'])->name('goats.show');
    Route::get('/kambing/{goat}/ubah', [GoatController::class, 'edit'])->name('goats.edit');
    Route::put('/kambing/{goat}', [GoatController::class, 'update'])->name('goats.update');
    Route::delete('/kambing/{goat}', [GoatController::class, 'destroy'])->name('goats.destroy');
    Route::get('/scan', ScanController::class)->name('scan');
    Route::get('/cetak-barcode', [GoatController::class, 'labels'])->name('goats.labels');

    // Transaksi
    Route::get('/pembelian', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/pembelian/baru', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/pembelian', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/pembelian/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::post('/pembelian/{purchase}/void', [PurchaseController::class, 'void'])->name('purchases.void');

    Route::get('/penjualan', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/penjualan/baru', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/penjualan', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/penjualan/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::post('/penjualan/{sale}/void', [SaleController::class, 'void'])->name('sales.void');

    Route::post('/pembayaran', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/pembayaran/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');

    Route::get('/beban', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/beban', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::post('/beban/{expense}/void', [ExpenseController::class, 'void'])->name('expenses.void');

    // Data pendukung
    Route::get('/supplier', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/supplier', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/supplier/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/supplier/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    Route::get('/customer', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customer', [CustomerController::class, 'store'])->name('customers.store');
    Route::put('/customer/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customer/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Laporan
    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/csv', [ReportController::class, 'csv'])->name('reports.csv');
    Route::get('/laporan/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

    // Sistem (Owner)
    Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
    Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/aktivitas', [ActivityLogController::class, 'index'])->name('activity.index');

    Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/pengaturan/ekspor', [SettingController::class, 'export'])->name('settings.export');
    Route::post('/pengaturan/impor', [SettingController::class, 'import'])->name('settings.import');
});

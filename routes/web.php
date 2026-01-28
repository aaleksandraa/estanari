<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentPlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    // Registration disabled
    // Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    // Route::post('register', [RegisteredUserController::class, 'store']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/dashboard/export-excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export-excel');
    
    // Unpaid (Neplaćeno)
    Route::get('/unpaid', [DashboardController::class, 'unpaid'])->name('unpaid.index');
    Route::get('/unpaid/export', [DashboardController::class, 'unpaidExport'])->name('unpaid.export');
    Route::get('/unpaid/export-excel', [DashboardController::class, 'unpaidExportExcel'])->name('unpaid.export-excel');

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::post('/payments/{payment}/mark-paid', [PaymentController::class, 'markAsPaid'])->name('payments.mark-paid');
    Route::post('/payments/{payment}/mark-unpaid', [PaymentController::class, 'markAsUnpaid'])->name('payments.mark-unpaid');
    Route::post('/payments/batch-mark-paid', [PaymentController::class, 'batchMarkAsPaid'])->name('payments.batch-mark-paid');
    Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::get('/payments/export-excel', [PaymentController::class, 'exportExcel'])->name('payments.export-excel');

    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::post('/suppliers/{supplier}/deactivate', [SupplierController::class, 'deactivate'])->name('suppliers.deactivate');
    Route::post('/suppliers/{supplier}/activate', [SupplierController::class, 'activate'])->name('suppliers.activate');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    Route::get('/suppliers/export', [SupplierController::class, 'export'])->name('suppliers.export');
    Route::get('/suppliers/export-excel', [SupplierController::class, 'exportExcel'])->name('suppliers.export-excel');
    Route::get('/suppliers/template', [SupplierController::class, 'downloadTemplate'])->name('suppliers.template');
    Route::post('/suppliers/import', [SupplierController::class, 'import'])->name('suppliers.import');

    // Branches
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::post('/branches/{branch}/deactivate', [BranchController::class, 'deactivate'])->name('branches.deactivate');
    Route::post('/branches/{branch}/activate', [BranchController::class, 'activate'])->name('branches.activate');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
    Route::get('/api/suppliers/{supplier}/branches', [BranchController::class, 'forSupplier'])->name('branches.for-supplier');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/daily', [ReportController::class, 'daily'])->name('reports.daily');
    Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('/reports/by-supplier', [ReportController::class, 'bySupplier'])->name('reports.by-supplier');
    Route::get('/reports/by-currency', [ReportController::class, 'byCurrency'])->name('reports.by-currency');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::put('/settings/exchange-rates', [SettingsController::class, 'updateExchangeRates'])->name('settings.exchange-rates');
    Route::put('/settings/company-name', [SettingsController::class, 'updateCompanyName'])->name('settings.company-name');
    Route::put('/settings/language', [SettingsController::class, 'updateLanguage'])->name('settings.language');

    // Payment Plans
    Route::get('/plans', [PaymentPlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [PaymentPlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}', [PaymentPlanController::class, 'show'])->name('plans.show');
    Route::delete('/plans/{plan}', [PaymentPlanController::class, 'destroy'])->name('plans.destroy');
    Route::post('/plans/{plan}/mark-paid', [PaymentPlanController::class, 'markAsPaid'])->name('plans.mark-paid');
    Route::post('/plans/{plan}/add-payment', [PaymentPlanController::class, 'addPayment'])->name('plans.add-payment');
    Route::post('/plans/{plan}/add-custom', [PaymentPlanController::class, 'addCustomItem'])->name('plans.add-custom');
    Route::post('/plans/{plan}/remove-payment/{payment}', [PaymentPlanController::class, 'removePayment'])->name('plans.remove-payment');
    Route::get('/plans/{plan}/export-csv', [PaymentPlanController::class, 'exportCsv'])->name('plans.export-csv');
    Route::get('/plans/{plan}/export-pdf', [PaymentPlanController::class, 'exportPdf'])->name('plans.export-pdf');
    Route::get('/plans/{plan}/export-excel', [PaymentPlanController::class, 'exportExcel'])->name('plans.export-excel');
});

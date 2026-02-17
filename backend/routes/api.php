<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePdfSettingController;
use App\Http\Controllers\OwnerAnalyticsController;
use App\Http\Controllers\OwnerApprovalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OwnerDashboardController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);

    Route::get('/pdf-settings', [InvoicePdfSettingController::class, 'show']);
    Route::post('/pdf-settings', [InvoicePdfSettingController::class, 'update']);

    // Invoice routes
    Route::get('/invoices/next-number', [InvoiceController::class, 'nextNumber']);
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices/preview-pdf', [InvoiceController::class, 'previewPdf']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);

    // Payment routes
    Route::get('/payments/search-invoices', [PaymentController::class, 'searchInvoices']);
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:admin,owner'])->group(function (): void {
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
});

Route::prefix('owner')
    ->middleware(['auth:sanctum', 'role:owner'])
    ->group(function (): void {
        Route::get('/analytics', OwnerAnalyticsController::class);
        Route::get('/dashboard', [OwnerDashboardController::class, 'index']);
        Route::get('/approvals', [OwnerApprovalController::class, 'index']);
    });

Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
    ->name('payments.receipt')
    ->middleware('signed');

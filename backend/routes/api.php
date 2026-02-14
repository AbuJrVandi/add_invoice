<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePdfSettingController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', DashboardController::class);

    Route::get('/pdf-settings', [InvoicePdfSettingController::class, 'show']);
    Route::post('/pdf-settings', [InvoicePdfSettingController::class, 'update']);

    Route::get('/invoices/next-number', [InvoiceController::class, 'nextNumber']);
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
});

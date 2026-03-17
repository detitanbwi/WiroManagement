<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::post('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    // User Management (restricted logic inside controller)
    Route::resource('users', UserController::class)->except(['show', 'edit', 'update']);

    Route::resource('clients', ClientController::class);
    Route::resource('projects', ProjectController::class);

    Route::resource('projects.invoices', InvoiceController::class)->shallow();
    Route::resource('projects.quotations', QuotationController::class)->shallow();
    Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convertToInvoice'])->name('quotations.convert');
    Route::post('projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status.update');

    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/documents/invoice/{invoice}/pdf', [DocumentController::class, 'streamInvoice'])->name('documents.invoice.pdf');
    Route::get('/documents/quotation/{quotation}/pdf', [DocumentController::class, 'streamQuotation'])->name('documents.quotation.pdf');
    Route::get('/documents/receipt/{payment}/pdf', [DocumentController::class, 'streamReceipt'])->name('documents.receipt.pdf');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});
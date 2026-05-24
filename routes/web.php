<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ProjectExpenseController;
use App\Http\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

Route::get('/test-pdf', function () {
    return response()->file(storage_path("app/public/attachments/quotations/q929Xw1DPMUZI5VThfMhnFg5lRXReJ9owRHyQz7J.pdf"));
});

Route::get('/dump-path/{path}', function ($path) {
    return 'Path is: ' . request()->path();
})->where('path', '.*');

// Route to bypass broken symlinks on shared hosting
Route::get('/storage/{path}', function ($path) {
    info("Storage route hit with path: " . $path);
    // Only allow attachments directory
    if (str_starts_with($path, 'attachments/')) {
        $fullPath = storage_path("app/public/{$path}");
        if (File::exists($fullPath)) {
            return response()->file($fullPath);
        }
    }
    abort(404);
})->where('path', '.*')->name('custom.storage');

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
    Route::post('projects/{project}/expenses', [ProjectExpenseController::class, 'store'])->name('projects.expenses.store');
    Route::put('expenses/{expense}', [ProjectExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('expenses/{expense}', [ProjectExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/documents/invoice/{invoice}/pdf', [DocumentController::class, 'streamInvoice'])->name('documents.invoice.pdf');
    Route::get('/documents/quotation/{quotation}/pdf', [DocumentController::class, 'streamQuotation'])->name('documents.quotation.pdf');
    Route::get('/documents/receipt/{payment}/pdf', [DocumentController::class, 'streamReceipt'])->name('documents.receipt.pdf');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Finance Monitoring Routes
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\FinanceController::class, 'overview'])->name('overview');
        
        Route::get('/bank-accounts', [\App\Http\Controllers\FinanceController::class, 'bankAccounts'])->name('bank-accounts');
        Route::post('/bank-accounts', [\App\Http\Controllers\FinanceController::class, 'storeBankAccount'])->name('bank-accounts.store');
        Route::put('/bank-accounts/{id}', [\App\Http\Controllers\FinanceController::class, 'updateBankAccount'])->name('bank-accounts.update');
        Route::delete('/bank-accounts/{id}', [\App\Http\Controllers\FinanceController::class, 'deleteBankAccount'])->name('bank-accounts.destroy');
        
        Route::get('/transactions', [\App\Http\Controllers\FinanceController::class, 'transactions'])->name('transactions');
    });
});

Route::fallback(function () {
    return 'Fallback route hit with path: ' . request()->path();
});
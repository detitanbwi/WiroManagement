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
use App\Http\Controllers\QcController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

// Route to bypass broken symlinks on shared hosting
Route::get('/storage/{path}', function ($path) {
    // Allow attachments and clients directories
    if (str_starts_with($path, 'attachments/') || str_starts_with($path, 'clients/')) {
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

Route::middleware(['auth', 'internal'])->group(function () {
    Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);

    // Profile Routes (Available for all authenticated internal users)
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::post('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    // User & Role Management (Super Admin only, full CRUD with multi-role support & dynamic permissions)
    Route::middleware('role:superadmin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::resource('roles', \App\Http\Controllers\RoleController::class)->except(['show']);
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // Clients Management
    Route::resource('clients', ClientController::class);
    Route::post('clients/{client}/portal-invite', [ClientController::class, 'generatePortalInvitation'])
        ->name('clients.portal-invite')
        ->middleware('role:superadmin,admin,pm');
    Route::post('clients/{client}/portal-instant-credentials', [ClientController::class, 'generateInstantCredentials'])
        ->name('clients.portal-instant-credentials')
        ->middleware('role:superadmin,admin,pm');

    // Projects Management
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status.update');
    
    // QA/QC Routes (Accessible to Superadmin, Admin, PM, QC, and Staff/Developer)
    Route::middleware('role:superadmin,admin,pm,qc,staff')->group(function () {
        Route::get('projects/{project}/qc', [ProjectController::class, 'qc'])->name('projects.qc');
        Route::get('api/projects/{project}/qc/tasks', [QcController::class, 'getTasks'])->name('api.qc.tasks');
        Route::get('api/projects/{project}/qc/test-cases', [QcController::class, 'getProjectTestCases'])->name('api.qc.project.test-cases');
        Route::post('api/projects/{project}/qc/test-cases', [QcController::class, 'storeProjectTestCase'])->name('api.qc.project.test-cases.store');
        Route::put('api/qc/test-cases/{testCase}', [QcController::class, 'updateProjectTestCase'])->name('api.qc.project.test-cases.update');
        Route::post('api/projects/{project}/qc/tasks', [QcController::class, 'storeTask'])->name('api.qc.tasks.store');
        Route::post('api/qc/tasks/{task}/move', [QcController::class, 'updateTaskColumn'])->name('api.qc.tasks.move');
        Route::post('api/qc/tasks/{task}/pass-test-cases', [QcController::class, 'passTaskTestCases'])->name('api.qc.tasks.pass-test-cases');
        Route::post('api/qc/test-cases/{testCase}/move', [QcController::class, 'moveProjectTestCase'])->name('api.qc.test-cases.move');
        Route::post('api/qc/test-cases/{testCase}/result', [QcController::class, 'submitTestResult'])->name('api.qc.test-cases.result');
        Route::delete('api/qc/test-cases/{testCase}', [QcController::class, 'destroyTestCase'])->name('api.qc.test-cases.destroy');
        Route::delete('api/qc/tasks/{task}', [QcController::class, 'destroyTask'])->name('api.qc.tasks.destroy');
        Route::get('api/projects/{project}/qc/bugs', [QcController::class, 'getProjectBugs'])->name('api.qc.project.bugs');
        Route::post('api/qc/bugs/{bug}/convert', [QcController::class, 'convertBugToTask'])->name('api.qc.bugs.convert');
        Route::post('api/projects/{project}/qc/bugs/bulk-convert', [QcController::class, 'bulkConvertBugsToTask'])->name('api.qc.bugs.bulk-convert');
        Route::delete('api/qc/bugs/{bug}', [QcController::class, 'destroyBug'])->name('api.qc.bugs.destroy');
        Route::get('api/qc/tasks/{task}/comments', [QcController::class, 'getTaskComments'])->name('api.qc.tasks.comments');
        Route::post('api/qc/tasks/{task}/comments', [QcController::class, 'storeTaskComment'])->name('api.qc.tasks.comments.store');
        Route::delete('api/qc/comments/{comment}', [QcController::class, 'destroyTaskComment'])->name('api.qc.comments.destroy');
    });

    // Invoices, Quotations, and Documents (Super Admin, Admin, Finance, PM)
    Route::middleware('role:superadmin,admin,finance,pm')->group(function () {
        Route::resource('projects.invoices', InvoiceController::class)->shallow();
        Route::resource('projects.quotations', QuotationController::class)->shallow();
        Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convertToInvoice'])->name('quotations.convert');
        Route::post('projects/{project}/expenses', [ProjectExpenseController::class, 'store'])->name('projects.expenses.store');
        Route::put('expenses/{expense}', [ProjectExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('expenses/{expense}', [ProjectExpenseController::class, 'destroy'])->name('expenses.destroy');

        Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/documents/invoice/{invoice}/pdf', [DocumentController::class, 'streamInvoice'])->name('documents.invoice.pdf');
        Route::get('/documents/quotation/{quotation}/pdf', [DocumentController::class, 'streamQuotation'])->name('documents.quotation.pdf');
        Route::get('/documents/receipt/{payment}/pdf', [DocumentController::class, 'streamReceipt'])->name('documents.receipt.pdf');
    });

    // Finance Monitoring Routes (Overview & Transactions: superadmin, admin, finance; Bank Accounts: superadmin, finance)
    Route::prefix('finance')->name('finance.')->middleware('role:superadmin,admin,finance')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\FinanceController::class, 'overview'])->name('overview');
        Route::get('/transactions', [\App\Http\Controllers\FinanceController::class, 'transactions'])->name('transactions');

        // Bank Accounts management restricted to superadmin & finance
        Route::middleware('role:superadmin,finance')->group(function () {
            Route::get('/bank-accounts', [\App\Http\Controllers\FinanceController::class, 'bankAccounts'])->name('bank-accounts');
            Route::post('/bank-accounts', [\App\Http\Controllers\FinanceController::class, 'storeBankAccount'])->name('bank-accounts.store');
            Route::put('/bank-accounts/{id}', [\App\Http\Controllers\FinanceController::class, 'updateBankAccount'])->name('bank-accounts.update');
            Route::delete('/bank-accounts/{id}', [\App\Http\Controllers\FinanceController::class, 'deleteBankAccount'])->name('bank-accounts.destroy');
        });
    });

    // AI Pricing Estimator Routes (superadmin, admin, pm, staff)
    Route::prefix('ai-pricing')->name('ai-pricing.')->middleware('role:superadmin,admin,pm,staff')->group(function () {
        Route::get('/', [\App\Http\Controllers\AiPricingController::class, 'index'])->name('index');
        Route::post('/new', [\App\Http\Controllers\AiPricingController::class, 'newSession'])->name('new');
        Route::get('/test-api', [\App\Http\Controllers\AiPricingController::class, 'testConnection'])->name('test-api');
        Route::get('/{id}', [\App\Http\Controllers\AiPricingController::class, 'show'])->name('show');
        Route::post('/{id}/chat', [\App\Http\Controllers\AiPricingController::class, 'chat'])->name('chat');
        Route::post('/{id}/update-modules', [\App\Http\Controllers\AiPricingController::class, 'updateModules'])->name('update-modules');
        Route::delete('/{id}', [\App\Http\Controllers\AiPricingController::class, 'deleteSession'])->name('delete');
    });
});
<?php

use App\Http\Controllers\Api\TrackerApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('tracker')->group(function () {
    // Accounts
    Route::get('/accounts', [TrackerApiController::class, 'getAccounts']);
    Route::post('/accounts', [TrackerApiController::class, 'storeAccount']);
    Route::put('/accounts/{id}', [TrackerApiController::class, 'updateAccount']);
    Route::delete('/accounts/{id}', [TrackerApiController::class, 'deleteAccount']);

    // Categories
    Route::get('/categories', [TrackerApiController::class, 'getCategories']);
    Route::post('/categories', [TrackerApiController::class, 'storeCategory']);
    Route::put('/categories/{id}', [TrackerApiController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [TrackerApiController::class, 'deleteCategory']);

    // Expenses
    Route::get('/expenses', [TrackerApiController::class, 'getExpenses']);
    Route::post('/expenses/sync', [TrackerApiController::class, 'syncExpenses']);
    Route::delete('/expenses/{id}', [TrackerApiController::class, 'deleteExpense']);
});

// Wiromitra Client Portal API (v1)
Route::prefix('v1/mitra')->group(function () {
    // Public / Onboarding
    Route::post('/auth/verify-token', [\App\Http\Controllers\Api\Mitra\MitraAuthController::class, 'verifyToken']);
    Route::post('/auth/set-password', [\App\Http\Controllers\Api\Mitra\MitraAuthController::class, 'setPassword']);
    Route::post('/auth/login', [\App\Http\Controllers\Api\Mitra\MitraAuthController::class, 'login']);

    // Protected (Sanctum Token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\Mitra\MitraAuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\Mitra\MitraAuthController::class, 'me']);
        Route::get('/dashboard', [\App\Http\Controllers\Api\Mitra\MitraDashboardController::class, 'index']);
        Route::get('/projects', [\App\Http\Controllers\Api\Mitra\MitraProjectController::class, 'index']);
        Route::get('/projects/{id}', [\App\Http\Controllers\Api\Mitra\MitraProjectController::class, 'show']);
    });
});

// Fixed Expenses Management API (v1)
Route::prefix('v1/fixed-expenses')->middleware(['auth:sanctum,web'])->group(function () {
    Route::middleware('permission:finance.fixed_expenses.view,finance.fixed_expenses.manage')->group(function () {
        Route::get('/monthly-summary', [\App\Http\Controllers\Api\FixedExpenseController::class, 'monthlySummary']);
        Route::get('/', [\App\Http\Controllers\Api\FixedExpenseController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\FixedExpenseController::class, 'show']);
    });

    Route::middleware('permission:finance.fixed_expenses.manage')->group(function () {
        Route::post('/generate-cycle', [\App\Http\Controllers\Api\FixedExpenseController::class, 'generateCycle']);
        Route::post('/realizations/{id}/mark-paid', [\App\Http\Controllers\Api\FixedExpenseController::class, 'markPaid']);
        Route::post('/', [\App\Http\Controllers\Api\FixedExpenseController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\FixedExpenseController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\FixedExpenseController::class, 'destroy']);
    });
});

// Wirodev-Man Mobile Companion API (v1 - Quick Actions)
Route::prefix('v1')->group(function () {
    // Public Auth
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);

    // Protected (Sanctum Token for internal staff/admin)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
        Route::get('/auth/me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);

        // Dashboard quick summary
        Route::get('/dashboard/summary', [\App\Http\Controllers\Api\V1\DashboardApiController::class, 'summary']);

        // Clients
        Route::get('/clients', [\App\Http\Controllers\Api\V1\ClientApiController::class, 'index']);
        Route::post('/clients', [\App\Http\Controllers\Api\V1\ClientApiController::class, 'store']);
        Route::get('/clients/{client}', [\App\Http\Controllers\Api\V1\ClientApiController::class, 'show']);

        // Projects
        Route::get('/projects', [\App\Http\Controllers\Api\V1\ProjectApiController::class, 'index']);
        Route::post('/projects', [\App\Http\Controllers\Api\V1\ProjectApiController::class, 'store']);
        Route::get('/projects/{project}', [\App\Http\Controllers\Api\V1\ProjectApiController::class, 'show']);
        Route::get('/projects/{project}/next-invoice-number', [\App\Http\Controllers\Api\V1\InvoiceApiController::class, 'getNextNumber']);

        // Invoices
        Route::get('/invoices', [\App\Http\Controllers\Api\V1\InvoiceApiController::class, 'index']);
        Route::post('/invoices', [\App\Http\Controllers\Api\V1\InvoiceApiController::class, 'store']);
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\Api\V1\InvoiceApiController::class, 'show']);
    });
});
